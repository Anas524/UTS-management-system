<?php

namespace App\Http\Controllers;

use App\Models\StockLedgerInventory;
use App\Models\SlRowAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class StockLedgerAttachmentController extends Controller
{
    public function index(StockLedgerInventory $inventory, int $entry)
    {
        $attachments = SlRowAttachment::where('inventory_id', $inventory->id)
            ->where('entry_id', $entry)
            ->orderByDesc('created_at')
            ->get();

        $items = $attachments->map(function (SlRowAttachment $att) use ($inventory, $entry) {
            // Download route (forces download)
            $downloadUrl = route('sl.attachments.download', [
                'inventory'  => $inventory->id,
                'entry'      => $entry,
                'attachment' => $att->id,
            ]);

            // Inline preview URL (public storage URL, no forced download)
            $previewUrl = asset('storage/' . ltrim($att->stored_name, '/'));

            return [
                'id'           => $att->id,
                'name'         => $att->original_name,
                'uploaded_at'  => optional($att->created_at)->format('d-m-Y H:i'),
                'preview_url'  => $previewUrl,
                'download_url' => $downloadUrl,
                'delete_url'   => route('sl.attachments.destroy', [
                    'inventory'  => $inventory->id,
                    'entry'      => $entry,
                    'attachment' => $att->id,
                ]),
            ];
        });

        return response()->json([
            'attachments' => $items,
        ]);
    }

    // Store
    public function store(Request $request, StockLedgerInventory $inventory, int $entry)
    {
        // optional: block consultants here as extra safety
        if (Auth::user()?->role === 'consultant') {
            abort(403);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'], // 20MB
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $data['file'];

        $path = $file->store(
            'stock-ledger/' . $inventory->id . '/' . $entry,
            'public'
        );

        $attachment = SlRowAttachment::create([
            'inventory_id'  => $inventory->id,
            'entry_id'      => $entry,
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $path,
            'mime'          => $file->getClientMimeType(),
            'size'          => $file->getSize(),
            'note'          => $data['note'] ?? null,
        ]);

        return response()->json([
            'message'    => 'Attachment uploaded',
            'attachment' => [
                'id'            => $attachment->id,
                'original_name' => $attachment->original_name,
                'size_label'    => $attachment->size_label,
            ],
        ]);
    }

    // Download / view
    public function download(StockLedgerInventory $inventory, int $entry, SlRowAttachment $attachment)
    {
        if ($attachment->inventory_id !== $inventory->id || $attachment->entry_id !== $entry) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($attachment->stored_name)) {
            abort(404, 'File not found');
        }

        // Get full path from storage
        $path = Storage::disk('public')->path($attachment->stored_name);

        return response()->download($path, $attachment->original_name);
    }

    // Delete
    public function destroy(StockLedgerInventory $inventory, int $entry, SlRowAttachment $attachment)
    {
        // optional: block consultants
        if (Auth::user()?->role === 'consultant') {
            abort(403);
        }

        if ($attachment->inventory_id !== $inventory->id || $attachment->entry_id !== $entry) {
            abort(404);
        }

        if (Storage::disk('public')->exists($attachment->stored_name)) {
            Storage::disk('public')->delete($attachment->stored_name);
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted']);
    }

    public function downloadAll(StockLedgerInventory $inventory, int $entry)
    {
        $attachments = SlRowAttachment::where('inventory_id', $inventory->id)
            ->where('entry_id', $entry)
            ->get();

        if ($attachments->isEmpty()) {
            return back()->with('status', 'No attachments found for this row.');
        }

        // temp zip path
        $zipFileName = 'stock-ledger-' . $inventory->id . '-entry-' . $entry . '-attachments.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        foreach ($attachments as $att) {
            if (Storage::disk('public')->exists($att->stored_name)) {
                $absolutePath = Storage::disk('public')->path($att->stored_name);
                $zip->addFile($absolutePath, $att->original_name);
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}
