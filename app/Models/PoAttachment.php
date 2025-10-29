<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoAttachment extends Model
{
    protected $table = 'po_attachments';

    // easiest:
    protected $guarded = []; 
    // or explicitly:
    // protected $fillable = ['po_id','disk','path','original_name','mime_type','size'];

    public function po()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }
}
