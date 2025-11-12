<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderRow extends Model
{
    protected $table = 'po_rows';
    protected $fillable = ['po_sheet_id', 'no', 'sku', 'brand', 'description', 'price_aed', 'qty', 'unit', 'amount'];
    protected $casts = [
        'po_sheet_id' => 'int',
        'no'          => 'int',
        'price_aed'   => 'decimal:4',
        'qty'         => 'decimal:4',
        'amount'      => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
    protected static function booted()
    {
        static::saving(function ($row) {
            // Prefer string math if you have helpers, but integer result is what we store
            $price = $row->price_aed ?? 0;   // DECIMAL(18,4)
            $qty   = $row->qty ?? 0;

            // If you have Num::mul, use it to avoid float drift; otherwise plain math is fine
            if (class_exists(\App\Support\Num::class)) {
                // mul(..., 4) gives 4dp; then we round to 0 for rupiah
                $line = \App\Support\Num::mul((string)$price, (string)$qty, 4);
                $row->amount = (int) round((float)$line, 0);
            } else {
                $row->amount = (int) round(((float)$price) * ((float)$qty), 0);
            }
        });
    }
    public function sheet()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_sheet_id');
    }
}
