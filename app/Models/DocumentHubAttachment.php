<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentHubAttachment extends Model
{
    protected $table = 'document_hub_attachments';

    protected $fillable = [
        'entry_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_kb',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(DocumentHubEntry::class, 'entry_id');
    }
}
