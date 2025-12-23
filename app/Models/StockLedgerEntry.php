<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLedgerEntry extends Model
{
    protected $table = 'stock_ledger_entries';

    protected $fillable = [
        'inventory_id',
        'item',
        'description',
        'vendor',
        'unit_price',
        'date_in',
        'qty_in',
        'unit',
        'date_out',
        'qty_out',
        'sales_channel',
        'current_stock',
        'restock',
    ];

    protected $casts = [
        'inventory_id'  => 'integer',
        'unit_price'    => 'decimal:4',
        'qty_in'        => 'decimal:4',
        'qty_out'       => 'decimal:4',
        'current_stock' => 'decimal:4',
        'date_in'       => 'date',
        'date_out'      => 'date',
    ];

    public function inventory()
    {
        return $this->belongsTo(StockLedgerInventory::class, 'inventory_id');
    }
}
