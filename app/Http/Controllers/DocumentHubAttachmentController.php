<?php

namespace App\Http\Controllers;

use App\Models\DocumentHubAttachment;
use App\Models\DocumentHubEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DocumentHubAttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function isConsultant(Request $request): bool
    {
        $user = $request->user();
        if ($user->is_admin ?? false) return false;
        return ($user->role ?? '') === 'consultant';
    }

    protected function ensureNotConsultant(Request $request): void
    {
        if ($this->isConsultant($request)) {
            abort(403, 'Consultants cannot modify attachments.');
        }
    }

    // JSON list for viewer
    public function index(Request $request, DocumentHubEntry $entry)
    {
        $items = $entry->attachments()
            ->orderBy('created_at')
            ->get()
            ->map(function (DocumentHubAttachment $att) use ($entry) {
                return [
                    'id'           => $att->id,
                    'name'         => $att->original_name,
                    'preview_url'  => route('dh.attachments.show', [$entry, $att]),
                    'download_url' => route('dh.attachments.download', [$entry, $att]),
                    'delete_url'   => route('dh.attachments.destroy', [$entry, $att]),
                    'uploaded_at'  => optional($att->created_at)->format('d-m-Y'),
                ];
            });

        return response()->json(['items' => $items]);
    }

    // Upload files
    public function store(Request $request, DocumentHubEntry $entry)
    {
        $this->ensureNotConsultant($request);

        $request->validate([
            'files'   => ['required', 'array'],
            'files.*' => ['file', 'mimes:pdf,jpeg,jpg,png', 'max:25600'], // 25MB
        ]);

        foreach ((array) $request->file('files') as $file) {
            $path = $file->store('document-hub/' . $entry->id, 'public');

            DocumentHubAttachment::create([
                'entry_id'      => $entry->id,
                'disk'          => 'public',
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'size_kb'       => (int) round($file->getSize() / 1024),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function show(DocumentHubEntry $entry, int $attachment)
    {
        $attachment = DocumentHubAttachment::where('entry_id', $entry->id)
            ->findOrFail($attachment);

        $relativePath = ltrim($attachment->path, '/');
        $fullPath     = storage_path('app/public/' . $relativePath);

        if (! file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Content-Type'        => $attachment->mime_type ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
        ]);
    }

    public function destroy(Request $request, DocumentHubEntry $entry, int $attachment)
    {
        $this->ensureNotConsultant($request);

        $attachment = DocumentHubAttachment::where('entry_id', $entry->id)
            ->findOrFail($attachment);

        $relativePath = ltrim($attachment->path, '/');
        $fullPath     = storage_path('app/public/' . $relativePath);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        $attachment->delete();

        return response()->json(['ok' => true]);
    }

    // Download all as ZIP (Download All button)
    public function downloadAll(DocumentHubEntry $entry)
    {
        $attachments = $entry->attachments()->get();
        if ($attachments->isEmpty()) {
            abort(404, 'No attachments to download.');
        }

        $zipName = 'document-hub-' . $entry->id . '.zip';
        $zipPath = storage_path('app/tmp/' . $zipName);

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0775, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        foreach ($attachments as $att) {
            if (! Storage::disk($att->disk)->exists($att->path)) {
                continue;
            }
            $zip->addFromString(
                $att->original_name,
                Storage::disk($att->disk)->get($att->path)
            );
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    public function download(DocumentHubEntry $entry, int $attachment)
    {
        $attachment = DocumentHubAttachment::where('entry_id', $entry->id)
            ->findOrFail($attachment);

        $relativePath = ltrim($attachment->path, '/');
        $fullPath     = storage_path('app/public/' . $relativePath);

        if (! file_exists($fullPath)) {
            abort(404);
        }

        return response()->download($fullPath, $attachment->original_name);
    }
}
