<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseRow extends Model
{
    protected $fillable = [
        'expense_sheet_id',
        'position',
        'date',
        'description',
        'doc_number',
        'debit',
        'credit',
        'amount',
        'remarks',
    ];

    protected $casts = [
        'date'   => 'date',      // <-- makes $r->date a Carbon instance
        'debit'  => 'decimal:0',  // or 'integer'
        'credit' => 'decimal:0',
        'amount' => 'decimal:0',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(ExpenseSheet::class, 'expense_sheet_id');
    }

    public function attachments()
    {
        return $this->hasMany(ExpenseRowAttachment::class, 'expense_row_id');
    }
}
