<?php

namespace App\Http\Middleware;

use App\Models\ExpenseRow;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExpensePeriodOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        // Your route model names:
        // - {sheet} => ExpenseSheet (via authorizeResource)
        // - {row}   => ExpenseRow (optional)
        $sheet = $request->route('sheet');

        if (!$sheet) {
            // if only a row was bound, resolve its sheet
            $row = $request->route('row');
            if ($row instanceof ExpenseRow) {
                $sheet = $row->sheet ?? $row->expenseSheet ?? $row->expense_sheet ?? null;
                if (!$sheet && method_exists($row, 'sheet')) $sheet = $row->sheet;
            }
        }

        if ($sheet && $sheet->is_closed) {
            abort(423, 'This period is closed. Reopen the year to edit.');
        }

        return $next($request);
    }
}
