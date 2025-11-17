<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\ExpenseRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
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

    public function __construct()
    {
        // param name must match your route: /expenses/{sheet}
        $this->authorizeResource(ExpenseSheet::class, 'sheet');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ExpenseSheet::class);

        $activeYear = (int) ($request->query('year') ?: date('Y'));
        $u = Auth::user();

        // --- 1) Build the "yearOptions" dropdown (distinct years the viewer can access)
        $base = ExpenseSheet::query();
        if (!(int)$u->is_admin && $u->role !== 'consultant') {
            $base->where('user_id', $u->id);
        }

        $yearOptions = $base->select('period_year')
            ->distinct()
            ->orderBy('period_year', 'asc')
            ->pluck('period_year')
            ->toArray();

        // ensure the currently requested year is present (e.g., after opening next year)
        if (!in_array($activeYear, $yearOptions, true)) {
            $yearOptions[] = $activeYear;
            sort($yearOptions);
        }

        // --- 2) Query rows for the active year (respecting visibility)
        $query = ExpenseSheet::query()
            ->with('user')
            ->withSum('rows as total_debit', 'debit')
            ->withSum('rows as total_credit', 'credit')
            ->where('period_year', $activeYear);

        // Normal users see only their own; admins & consultants see all
        if (!(int)$u->is_admin && $u->role !== 'consultant') {
            $query->where('user_id', $u->id);
        }

        // --- 3) Totals across all visible sheets in the active year
        $visibleIds = (clone $query)->pluck('id');

        // TRUNCATE each value before summing so we don't round up
        $global = DB::table('expense_rows')
            ->whereIn('expense_sheet_id', $visibleIds)
            ->selectRaw('
                COALESCE(SUM(TRUNCATE(debit,  0)), 0) AS debit,
                COALESCE(SUM(TRUNCATE(credit, 0)), 0) AS credit
            ')
            ->first();

        $allDebit  = (int) (string) ($global->debit  ?? 0);
        $allCredit = (int) (string) ($global->credit ?? 0);

        // --- 4) Table rows (Jan → Dec)
        $sheets = $query
            ->orderBy('period_year', 'asc')
            ->orderBy('period_month', 'asc')
            ->get();

        // --- 5) Header button logic
        $mine = ExpenseSheet::query()
            ->where('user_id', $u->id)
            ->where('period_year', $activeYear);

        $hasOpen   = (clone $mine)->where('is_closed', false)->exists(); // at least one of MY sheets open
        $hasClosed = (clone $mine)->where('is_closed', true)->exists();  // at least one of MY sheets closed
        $multiYear = count($yearOptions) > 1;

        return view('expenses.index', [
            'sheets'       => $sheets,
            'allDebit'     => $allDebit,
            'allCredit'    => $allCredit,
            'activeYear'   => $activeYear,
            'yearOptions'  => $yearOptions, // use this in the dropdown
            'multiYear'    => $multiYear,   // hide dropdown if false
            'hasOpen'      => $hasOpen,     // show Close if true
            'hasClosed'    => $hasClosed,    // show Reopen when year effectively closed
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', ExpenseSheet::class);

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

        return redirect()->route('expenses.show', [$sheet, 'year' => $sheet->period_year]);
    }

    public function show(Request $request, ExpenseSheet $sheet)
    {
        $this->authorize('view', $sheet);

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
        $this->authorize('update', $sheet);
        $this->guardOpen($sheet);

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
        $this->authorize('update', $sheet);
        $this->guardOpen($sheet);

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
        $this->authorize('update', $sheet);
        $this->guardOpen($sheet);

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
        $this->authorize('update', $sheet);
        $this->guardOpen($sheet);

        // Re-resolve so it MUST belong to this sheet
        $row = $sheet->rows()->whereKey($row->getKey())->firstOrFail();

        $row->delete();
        return back()->with('status', 'Row removed.');
    }

    public function export(ExpenseSheet $sheet)
    {
        $this->authorize('export', ExpenseSheet::class);

        $periodLabel = Carbon::create($sheet->period_year, $sheet->period_month, 1)->format('F Y');
        $filename = "Expense Sheet - {$periodLabel}.xlsx"; // e.g. "Expense Sheet - August 2025.xlsx"

        return Excel::download(new ExpenseSheetExport($sheet), $filename);
    }

    // POST /expenses/close-year/{year}
    public function closeYear(int $year)
    {
        $this->authorize('closeYear', ExpenseSheet::class);

        DB::transaction(function () use ($year) {
            ExpenseSheet::where('user_id', Auth::id())
                ->where('period_year', $year)
                ->where('is_closed', false)
                ->lockForUpdate()
                ->get()
                ->each->closeNow();
        });

        // Stay on that year
        return redirect()
            ->route('expenses.index', ['year' => $year])
            ->with('ok', "Closed expense year {$year}");
    }

    // POST /expenses/open-next/{year}
    public function openNextYear(int $year)
    {
        $this->authorize('openNextYear', ExpenseSheet::class);

        $next = $year + 1;

        //  Do NOT pre-create any sheets now. Just jump to the next year and
        //  auto-open the "Add Sheet" modal so the user chooses the month.
        return redirect()
            ->route('expenses.index', ['year' => $next, 'new' => 1])
            ->with('ok', "Opened expense year {$next}. Choose a month to create the first sheet.");
    }

    // POST /expenses/reopen-year/{year}
    public function reopenYear(int $year)
    {
        $this->authorize('reopenYear', ExpenseSheet::class);

        DB::transaction(function () use ($year) {
            ExpenseSheet::where('user_id', Auth::id())
                ->where('period_year', $year)
                ->where('is_closed', true)
                ->lockForUpdate()
                ->get()
                ->each->reopen();
        });

        // Stay on that year
        return redirect()
            ->route('expenses.index', ['year' => $year])
            ->with('ok', "Reopened expense year {$year}");
    }

    private function guardOpen(ExpenseSheet $sheet): void
    {
        if ($sheet->is_closed) {
            abort(423, 'This period is closed. Reopen the year to edit.');
        }
    }

    private function computeEndingBalance(\App\Models\ExpenseSheet $sheet): ?int
    {
        // sum as integers (rupiah units), using DB to avoid float drift
        $agg = DB::table('expense_rows')
            ->where('expense_sheet_id', $sheet->id)
            ->selectRaw('
            COALESCE(SUM(TRUNCATE(debit,  0)), 0) AS debit,
            COALESCE(SUM(TRUNCATE(credit, 0)), 0) AS credit
        ')
            ->first();

        $totalDebit  = (int) (string) $agg->debit;
        $totalCredit = (int) (string) $agg->credit;

        $mutation = $totalDebit - $totalCredit;
        $begin    = $sheet->beginning_balance;

        return is_null($begin) ? null : ($begin + $mutation);
    }
}
