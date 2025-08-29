<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\ExpenseRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\ExpenseSheetExport;

class ExpenseSheetController extends Controller
{
    /**
     * For IDR-like inputs: keep only digits and optional leading minus.
     * - "IDR 17.372" -> "17372"
     * - "17,372"     -> "17372"
     * - "" or "-"    -> null
     */
    private function normalizeRupiah(\Illuminate\Http\Request $request, array $fields): void
    {
        foreach ($fields as $f) {
            if ($request->has($f)) {
                $raw = (string) $request->input($f);

                // keep digits and minus
                $clean = preg_replace('/[^0-9\-]/', '', $raw);
                // allow only leading minus
                $clean = preg_replace('/(?!^)-/', '', $clean);

                if ($clean === '' || $clean === '-') {
                    $request->merge([$f => null]);
                } else {
                    // store as integer string (rupiah units)
                    $request->merge([$f => (string) intval($clean)]);
                }
            }
        }
    }

    public function index(Request $request)
    {
        $query = ExpenseSheet::query()->with('user');

        // Admin sees all, users see only their own
        if (!Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        // sort strictly by year then month (Jan → Dec)
        $sheets = $query
            ->orderBy('period_year', 'asc')
            ->orderBy('period_month', 'asc')
            ->paginate(10);

        return view('expenses.index', compact('sheets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'period_month' => 'required|integer|min:1|max:12',
            'period_year'  => 'required|integer|min:2000|max:2100',
        ]);

        $sheet = ExpenseSheet::create([
            'user_id'       => Auth::id(),
            'period_month'  => $data['period_month'],
            'period_year'   => $data['period_year'],
            'beginning_balance' => null,
        ]);

        return redirect()->route('expenses.show', $sheet);
    }

    public function show(Request $request, ExpenseSheet $sheet)
    {
        $this->authorizeSheet($sheet);

        $order = $request->get('order', 'desc') === 'asc' ? 'asc' : 'desc';
        // remove default "position" ordering from relation and sort by date/id
        $rows = $sheet->rows()
            ->with('attachments')
            ->reorder('date', $order)
            ->orderBy('id', $order)
            ->get();

        $totalDebit  = (float) $rows->sum(fn($r) => (float) $r->debit);
        $totalCredit = (float) $rows->sum(fn($r) => (float) $r->credit);
        $totalAmount = (float) $rows->sum(fn($r) => (float) $r->amount);

        $mutation = $totalDebit - $totalCredit;
        $begin    = $sheet->beginning_balance ?? null;
        $ending   = is_null($begin) ? null : ($begin + $mutation);

        return view('expenses.show', compact('sheet', 'rows', 'totalDebit', 'totalCredit', 'totalAmount', 'mutation', 'begin', 'ending', 'order'));
    }

    public function updateBeginning(Request $request, ExpenseSheet $sheet)
    {
        $this->authorizeSheet($sheet);

        // sanatize first
        $this->normalizeRupiah($request, ['beginning_balance']);

        $data = $request->validate([
            'beginning_balance' => 'nullable|numeric',
        ]);

        $sheet->update(['beginning_balance' => $data['beginning_balance']]);

        return back()->with('status', 'Beginning balance updated.');
    }

    public function addRow(Request $request, ExpenseSheet $sheet)
    {
        $this->authorizeSheet($sheet);

        $data = $request->validate([
            'date'        => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $position = ($sheet->rows()->max('position') ?? 0) + 1;

        ExpenseRow::create([
            'expense_sheet_id' => $sheet->id,
            'position'   => $position,
            'date'       => $data['date'],
            'description' => $data['description'],
            // doc_number, debit, credit, amount left null for user to fill later
        ]);

        return back()->with('status', 'Row added.');
    }

    public function updateRow(Request $request, ExpenseSheet $sheet, ExpenseRow $row)
    {
        $this->authorizeSheet($sheet);
        // Re-resolve so it MUST belong to this sheet
        $row = $sheet->rows()->whereKey($row->getKey())->firstOrFail();

        // sanitize currency fields BEFORE validate
        $this->normalizeRupiah($request, ['debit', 'credit', 'amount']);

        $data = $request->validate([
            'date'        => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'doc_number'  => 'nullable|string|max:255',
            'debit'       => 'nullable|numeric',
            'credit'      => 'nullable|numeric',
            'amount'      => 'nullable|numeric',
            'remarks'     => 'nullable|string|max:255',
        ]);

        // Normalize: turn empty strings into null (for text fields only)
        foreach (['description', 'doc_number', 'remarks'] as $f) {
            if ($request->has($f) && $request->input($f) === '') {
                $data[$f] = null;
            }
        }

        $row->update($data);

        return back()->with('status', 'Row updated.');
    }

    public function deleteRow(ExpenseSheet $sheet, ExpenseRow $row)
    {
        $this->authorizeSheet($sheet);
        // Re-resolve so it MUST belong to this sheet
        $row = $sheet->rows()->whereKey($row->getKey())->firstOrFail();

        $row->delete();
        return back()->with('status', 'Row removed.');
    }

    private function authorizeSheet(ExpenseSheet $sheet): void
    {
        if (Auth::user()->is_admin) return;
        if ($sheet->user_id !== Auth::id()) abort(403);
    }

    public function export(ExpenseSheet $sheet)
    {
        $this->authorizeSheet($sheet);

        $periodLabel = Carbon::create($sheet->period_year, $sheet->period_month, 1)->format('F Y');
        $filename = "Expense Sheet - {$periodLabel}.xlsx"; // e.g. "Expense Sheet - August 2025.xlsx"

        return Excel::download(new ExpenseSheetExport($sheet), $filename);
    }
}
