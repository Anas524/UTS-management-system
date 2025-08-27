<?php

namespace App\Exports;

use App\Models\ExpenseSheet;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExpenseSheetExport implements FromView
{
    public function __construct(public ExpenseSheet $sheet) {}

    public function view(): View
    {
        $rows = $this->sheet->rows()->get();

        $totalDebit  = (float) $rows->sum(fn ($r) => (float) $r->debit);
        $totalCredit = (float) $rows->sum(fn ($r) => (float) $r->credit);
        $totalAmount = (float) $rows->sum(fn ($r) => (float) $r->amount);

        $mutation = $totalDebit - $totalCredit;
        $begin    = $this->sheet->beginning_balance;
        $ending   = is_null($begin) ? null : ($begin + $mutation);

        return view('exports.expense_sheet', [
            'sheet'        => $this->sheet,
            'rows'         => $rows,
            'totalDebit'   => $totalDebit,
            'totalCredit'  => $totalCredit,
            'totalAmount'  => $totalAmount,
            'mutation'     => $mutation,
            'begin'        => $begin,
            'ending'       => $ending,
        ]);
    }
}
