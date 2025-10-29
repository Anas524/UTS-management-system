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
        'company_name',
        'po_number',
        'po_date',
        'vendor',
        'npwp',
        'address',
        'ppn_rate',
        'tax_kind',
        'prepared_by',
        'sup_company',
        'sup_address',
        'sup_phone',
        'sup_email',
        'sup_contact_person',
        'sup_contact_phone',
        'sup_contact_email',
        'currency',
        'status'
    ];
    protected $casts = ['po_date' => 'date', 'ppn_rate' => 'float'];
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
    // optional pretty label
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
