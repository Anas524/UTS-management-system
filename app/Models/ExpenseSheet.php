<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ExpenseSheet extends Model
{
    protected $fillable = [
        'user_id','company_name','period_month','period_year','beginning_balance', 'is_closed',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    protected $attributes = [
        'is_closed' => false,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ExpenseRow::class)->orderBy('position');
    }

    // ---- Query Scopes ----
    public function scopeYear(Builder $q, int $year): Builder
    {
        return $q->where('period_year', $year);
    }

    public function scopeMonth(Builder $q, int $month): Builder
    {
        return $q->where('period_month', $month);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('is_closed', false);
    }

    public function scopeClosed(Builder $q): Builder
    {
        return $q->where('is_closed', true);
    }

    // ---- Helpers ----
    public function closeNow(): void
    {
        $this->forceFill([
            'is_closed' => true,
            'closed_at' => now(),
        ])->save();
    }

    public function reopen(): void
    {
        $this->forceFill([
            'is_closed' => false,
            'closed_at' => null,
        ])->save();
    }
}
