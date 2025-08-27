<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseRowAttachment extends Model
{
    protected $fillable = [
        'expense_row_id','user_id','original_name','file_name','mime','size','disk','path',
    ];

    public function row(): BelongsTo { return $this->belongsTo(ExpenseRow::class, 'expense_row_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
