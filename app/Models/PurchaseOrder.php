<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrder extends Model
{
    protected $table = 'po_sheets';
    protected $fillable = [
        'user_id',
        'po_number',
        'po_date',
        'address',
        'ppn_rate',
        'tax_kind',
        'status',
        'sup_company',
        'sup_address',
        'sup_phone',
        'sup_email',
        'sup_npwp',
        'ship_to_address',
        'ship_to_phone',
        'payment_terms',
        'delivery_time',
        'delivery_terms',
        'ship_to_recipient',
        'conditions_terms',
    ];
    protected $casts = ['po_date' => 'date', 'ppn_rate' => 'decimal:4', 'is_closed' => 'boolean', 'closed_at' => 'datetime',];
    public function rows()
    {
        return $this->hasMany(PurchaseOrderRow::class, 'po_sheet_id', 'id');
    }
    public function attachments()
    {
        return $this->hasMany(\App\Models\PoAttachment::class, 'po_id');
    }
    protected function poDate(): Attribute
    {
        return Attribute::set(function ($value) {
            if (!$value) return null;
            try {
                return Carbon::parse($value);
            } catch (\Throwable $e) {
                try {
                    return Carbon::createFromFormat('d/m/Y', $value);
                } catch (\Throwable $e2) {
                    return null;
                }
            }
        });
    }
    public function getPoDateForInputAttribute(): ?string
    {
        return $this->po_date?->format('Y-m-d');
    }
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'closed'            => 'Closed',
            'awaiting_response' => 'Awaiting Response',
            'transferred'       => 'Transferred',
            default             => 'Open',
        };
    }
    // ---- Query scopes ----
    public function scopeYear(Builder $q, int $year): Builder
    {
        return $q->whereYear('po_date', $year);
    }

    public function scopeMonth(Builder $q, int $month): Builder
    {
        return $q->whereMonth('po_date', $month);
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
