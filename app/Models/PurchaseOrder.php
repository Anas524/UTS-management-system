<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

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
    protected $casts = ['po_date' => 'date', 'ppn_rate' => 'decimal:4'];
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
        return match($this->status) {
            'closed'            => 'Closed',
            'awaiting_response' => 'Awaiting Response',
            'transferred'       => 'Transferred',
            default             => 'Open',
        };
    }
}
