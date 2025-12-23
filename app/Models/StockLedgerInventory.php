<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLedgerInventory extends Model
{
    protected $table = 'stock_ledger_inventories';

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function entries()
    {
        return $this->hasMany(StockLedgerEntry::class, 'inventory_id');
    }
}
