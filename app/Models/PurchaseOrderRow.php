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
        'price_aed'   => 'int',    // fils
        'qty'         => 'float',
        'amount'      => 'int',    // fils (line total)
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
    protected static function booted()
    {
        static::saving(function ($row) {
            // price_aed is in fils, qty can be decimal → store amount in fils too
            $price = is_null($row->price_aed) ? 0 : (int)$row->price_aed;
            $qty   = is_null($row->qty) ? 0 : (float)$row->qty;
            $row->amount = (int) round($price * $qty);
        });
    }
    public function sheet()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_sheet_id');
    }
}
