<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\StockLedgerInventory;
use App\Models\StockLedgerEntry;

class SlRowAttachment extends Model
{
    protected $table = 'sl_row_attachments';

    protected $fillable = [
        'inventory_id',
        'entry_id',
        'original_name',
        'stored_name',
        'mime',
        'size',
        'note',
    ];

    public function inventory()
    {
        // links to stock_ledger_inventories table
        return $this->belongsTo(StockLedgerInventory::class, 'inventory_id');
    }

    public function entry()
    {
        // links to stock_ledger_entries table
        return $this->belongsTo(StockLedgerEntry::class, 'entry_id');
    }

    public function getSizeLabelAttribute(): string
    {
        $size = (int) ($this->size ?? 0);

        if ($size >= 1048576) { // MB
            return round($size / 1048576, 1) . ' MB';
        }

        if ($size >= 1024) { // KB
            return round($size / 1024, 1) . ' KB';
        }

        return $size . ' B';
    }
}
