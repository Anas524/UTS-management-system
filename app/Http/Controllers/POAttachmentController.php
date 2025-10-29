<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PoAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PoAttachmentController extends Controller
{
    // GET /po/{po}/attachments  → JSON list
    public function index(\App\Models\PurchaseOrder $po)
    {
        $this->authorize('view', $po);

        $items = $po->attachments()->latest()->get()->map(function ($a) {
            return [
                'id'       => $a->id,
                'name'     => $a->original_name,
                'mime'     => $a->mime,
                'view'     => route('po.attachments.view', $a),       // ensure this returns INLINE
                'download' => route('po.attachments.download', $a),
            ];
        });

        return response()->json([
            'items'      => $items,
            'bundle_url' => route('po.attachments.bundle', $po),   // include if you keep Download All
        ]);
    }

    // POST /po/{po}/attachments  → upload multiple
    public function store(Request $r, PurchaseOrder $po)
    {
        $this->authorize('update', $po);

        $r->validate([
            'files'   => 'required',
            'files.*' => 'file|max:20480', // 20 MB each
        ]);

        foreach ($r->file('files', []) as $file) {
            // Store on 'public' and keep the path as stored_name
            $stored = $file->store("po/{$po->id}", 'public');

            $po->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $stored,                     // << matches migration
                'mime'          => $file->getClientMimeType(),  // << matches migration
                'size'          => $file->getSize(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // DELETE /po/attachments/{att}
    public function destroy(PoAttachment $att)
    {
        $this->authorize('update', $att->po);

        if ($att->stored_name) {
            Storage::disk('public')->delete($att->stored_name);
        }
        $att->delete();

        return response()->json(['ok' => true]);
    }

    public function view(\App\Models\PoAttachment $att)
    {
        $this->authorize('view', $att->po);
        $disk = $att->disk ?? 'public';
        $path = $att->stored_name ?? $att->path;
        if (!$path || !Storage::disk($disk)->exists($path)) abort(404);

        $full  = Storage::disk($disk)->path($path);
        $mime  = $att->mime ?? (function_exists('mime_content_type') ? mime_content_type($full) : null) ?? 'application/octet-stream';
        $title = $att->original_name ?: basename($full);

        // images -> show image inline
        if (str_starts_with($mime, 'image/')) {
            return response()->file($full, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="' . $title . '"',
                'X-Frame-Options'     => 'SAMEORIGIN',
            ]);
        }

        // pdf -> show pdf inline
        if ($mime === 'application/pdf' || preg_match('/\bpdf\b/i', $mime)) {
            return response()->file($full, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $title . '"',
                'X-Frame-Options'     => 'SAMEORIGIN',
            ]);
        }

        // text -> render minimal one-page PDF
        $text = @file_get_contents($full) ?: '';
        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = <<<HTML
        <html><head><meta charset="utf-8"></head>
        <body style="margin:24px;font-family:DejaVu Sans,monospace;">
        <div style="font-size:14px;margin-bottom:10px;"><strong>{$title}</strong></div>
        <pre style="white-space:pre-wrap;word-wrap:break-word;font-size:12px;">{$safe}</pre>
        </body></html>
        HTML;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $tmp = storage_path('app/temp/' . Str::uuid() . '.pdf');
        if (!is_dir(dirname($tmp))) mkdir(dirname($tmp), 0775, true);
        $pdf->save($tmp);

        return response()->file($tmp, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $title . '.pdf"',
            'X-Frame-Options'     => 'SAMEORIGIN',
        ]);
    }

    public function download(\App\Models\PoAttachment $att): BinaryFileResponse
    {
        $this->authorize('view', $att->po);
        $disk = $att->disk ?? 'public';
        $path = $att->stored_name ?? $att->path;
        $full = Storage::disk($disk)->path($path);
        return response()->download($full, $att->original_name ?: basename($full));
    }

    public function bundle(\App\Models\PurchaseOrder $po)
    {
        $this->authorize('view', $po);

        // eager load if needed
        $atts = $po->attachments()->get(); // adjust relation name if different

        if ($atts->isEmpty()) {
            return response('No attachments to bundle', 404);
        }

        // create a temp zip
        $zipFileName = 'PO-' . $po->po_number . '-attachments-' . now()->format('Ymd_His') . '.zip';
        $tmpPath = storage_path('app/tmp');
        if (!is_dir($tmpPath)) @mkdir($tmpPath, 0775, true);
        $zipPath = $tmpPath . DIRECTORY_SEPARATOR . Str::random(8) . '.zip';

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response('Could not create ZIP', 500);
        }

        foreach ($atts as $att) {
            $path = $att->stored_name; // e.g. 'po/123/abc.pdf'
            if (!$path) continue;
            if (!Storage::disk('public')->exists($path)) continue;

            // choose a friendly file name inside the zip
            $basename = $att->original_name ?: basename($path);
            // guard against nested paths/harmful names
            $basename = ltrim(str_replace(['\\', '..'], ['_', ''], $basename), '/');

            // stream file content into zip
            $zip->addFromString($basename, Storage::disk('public')->get($path));
        }
        $zip->close();

        // stream it and clean up the temp file after send
        return new StreamedResponse(function () use ($zipPath) {
            $stream = fopen($zipPath, 'rb');
            fpassthru($stream);
            fclose($stream);
            @unlink($zipPath);
        }, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
        ]);
    }
}
