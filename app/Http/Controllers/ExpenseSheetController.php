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
    public function index(Request $request)
    {
        $query = ExpenseSheet::query()->with('user')->latest();

        // Admin sees all, users see only their own
        if (!Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        $sheets = $query->paginate(10);

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

    public function show(ExpenseSheet $sheet)
    {
        $this->authorizeSheet($sheet);

        $rows = $sheet->rows()->with('attachments')->get();

        $totalDebit  = (float) $rows->sum(fn($r) => (float) $r->debit);
        $totalCredit = (float) $rows->sum(fn($r) => (float) $r->credit);
        $totalAmount = (float) $rows->sum(fn($r) => (float) $r->amount);

        $mutation = $totalDebit - $totalCredit;
        $begin    = $sheet->beginning_balance ?? null;
        $ending   = is_null($begin) ? null : ($begin + $mutation);

        return view('expenses.show', compact('sheet', 'rows', 'totalDebit', 'totalCredit', 'totalAmount', 'mutation', 'begin', 'ending'));
    }

    public function updateBeginning(Request $request, ExpenseSheet $sheet)
    {
        $this->authorizeSheet($sheet);

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
        abort_if($row->expense_sheet_id !== $sheet->id, 404);

        $data = $request->validate([
            'date'        => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'doc_number'  => 'nullable|string|max:255',
            'debit'       => 'nullable|numeric',
            'credit'      => 'nullable|numeric',
            'amount'      => 'nullable|numeric',
            'remarks'     => 'nullable|string|max:255',
        ]);

        // Normalize: turn empty strings into null (so clearing a field really clears it)
        foreach (['description', 'doc_number', 'remarks'] as $f) {
            if ($request->has($f) && $request->input($f) === '') {
                $data[$f] = null;
            }
        }
        foreach (['debit', 'credit', 'amount'] as $f) {
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
        abort_if($row->expense_sheet_id !== $sheet->id, 404);

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
