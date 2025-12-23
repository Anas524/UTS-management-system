<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\ExpenseRow;
use App\Models\ExpenseRowAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use iio\libmergepdf\Merger;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class RowAttachmentController extends Controller
{
    // List (JSON)
    public function index(ExpenseSheet $sheet, int $rowId)
    {
        // fetch row scoped to this sheet
        $row = ExpenseRow::where('expense_sheet_id', $sheet->id)
            ->findOrFail($rowId);

        $this->authorize('download', $row);

        $atts = $row->attachments()->latest()->get()->map(function ($a) use ($sheet, $row) {
            return [
                'id'          => $a->id,
                'name'        => $a->original_name,
                'size'        => $a->size,
                'mime'        => $a->mime,
                'view'        => route('attachments.preview', $a),
                'download'    => route('attachments.download', $a),
                'delete_url'  => route('attachments.destroy', [$sheet, $row->id, $a->id]),
                'uploaded_at' => optional($a->created_at)->format('d M Y H:i'),
            ];
        });

        return response()->json($atts);
    }

    // Upload (supports multiple files)
    public function store(Request $request, ExpenseSheet $sheet, int $rowId)
    {
        // row is always forced to belong to this sheet
        $row = ExpenseRow::where('expense_sheet_id', $sheet->id)
            ->findOrFail($rowId);

        $this->authorize('update', $sheet);

        // hard guard: forbid uploads when closed
        if ($sheet->is_closed) {
            abort(423, 'This period is closed. Reopen the year to edit.');
        }

        $request->validate([
            'files'   => 'required',
            'files.*' => 'file|max:20480',
        ]);

        foreach ((array) $request->file('files', []) as $file) {
            if (!$file) continue;

            $dir  = "attachments/{$sheet->id}/{$row->id}";
            $path = $file->store($dir, 'public');

            $row->attachments()->create([
                'user_id'       => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'file_name'     => basename($path),
                'mime'          => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'disk'          => 'public',
                'path'          => $path,
            ]);
        }

        return back()->with('status', 'Files uploaded.');
    }

    // Download
    public function download(ExpenseRowAttachment $att): BinaryFileResponse
    {
        $att->load('row.sheet');
        $this->authorize('download', $att->row);

        $fullPath = Storage::disk($att->disk)->path($att->path);
        return response()->download($fullPath, $att->original_name);
    }

    // View inline (images/pdf)
    public function view(ExpenseRowAttachment $att)
    {
        $att->load('row.sheet');
        $this->authorize('download', $att->row);

        $path = Storage::disk($att->disk)->path($att->path);
        return response()->file($path);
    }

    // Delete
    public function destroy(Request $request, ExpenseSheet $sheet, int $rowId, int $attId)
    {
        // Make sure the row belongs to this sheet
        $row = ExpenseRow::where('expense_sheet_id', $sheet->id)
            ->findOrFail($rowId);

        $this->authorize('update', $sheet); // permission comes from sheet

        // Find the attachment for THIS row only
        $att = ExpenseRowAttachment::where('expense_row_id', $row->id)
            ->findOrFail($attId);

        // Hard guard: no deletes when closed
        if ($sheet->is_closed) {
            abort(423, 'This period is closed. Reopen the year to edit.');
        }

        Storage::disk($att->disk)->delete($att->path);
        $att->delete();

        // If it's an AJAX/JSON request, return JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        // Otherwise, redirect back to the sheet (no pretty-print screen)
        return back()->with('status', 'Attachment deleted.');
    }

    public function bundlePdf(ExpenseSheet $sheet, int $rowId)
    {
        $row = ExpenseRow::where('expense_sheet_id', $sheet->id)
            ->findOrFail($rowId);

        $this->authorize('download', $row);

        $attachments = $row->attachments()->get();

        if ($attachments->isEmpty()) {
            return back()->with('status', 'No attachments to download.');
        }

        // Create a temporary ZIP
        $zip = new ZipArchive();
        $zipPath = storage_path('app/temp/' . Str::uuid() . '.zip');

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0775, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('status', 'Could not create ZIP file.');
        }

        foreach ($attachments as $att) {
            $full = Storage::disk($att->disk)->path($att->path);
            $name = $att->original_name ?: basename($att->path);

            // Only add file if it actually exists on disk
            if (is_file($full)) {
                $zip->addFile($full, $name);
            }
        }

        $zip->close();

        $sheetLabel = \Carbon\Carbon::create($sheet->period_year, $sheet->period_month, 1)->format('F Y');
        $filename = "Row {$row->id} Attachments - {$sheetLabel}.zip";

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    // --- helpers to generate temp pdf pages ---

    protected function makeTempPdfForImage(string $fullPath, string $title): string
    {
        $data = base64_encode(file_get_contents($fullPath));
        $mime = mime_content_type($fullPath) ?: 'image/*';
        $html = <<<HTML
        <html>
        <head><meta charset="utf-8"></head>
        <body style="margin:24px; font-family: DejaVu Sans, sans-serif;">
        <div style="font-size:14px; margin-bottom:10px;"><strong>{$this->esc($title)}</strong></div>
        <div style="text-align:center;">
            <img src="data:{$mime};base64,{$data}" style="max-width:100%; height:auto;">
        </div>
        </body>
        </html>
        HTML;

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $tmp = storage_path('app/temp/' . Str::uuid() . '.pdf');
        if (!is_dir(dirname($tmp))) mkdir(dirname($tmp), 0775, true);
        $pdf->save($tmp);
        return $tmp;
    }

    protected function makeTempPdfForText(string $fullPath, string $title): string
    {
        $text = file_get_contents($fullPath);
        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = <<<HTML
        <html>
        <head><meta charset="utf-8"></head>
        <body style="margin:24px; font-family: DejaVu Sans, monospace;">
        <div style="font-size:14px; margin-bottom:10px;"><strong>{$this->esc($title)}</strong></div>
        <pre style="white-space:pre-wrap; word-wrap:break-word; font-size:12px;">{$safe}</pre>
        </body>
        </html>
        HTML;

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $tmp = storage_path('app/temp/' . Str::uuid() . '.pdf');
        if (!is_dir(dirname($tmp))) mkdir(dirname($tmp), 0775, true);
        $pdf->save($tmp);
        return $tmp;
    }

    protected function makeTempPdfPlaceholder(string $title, string $mime): string
    {
        $html = <<<HTML
        <html>
        <head><meta charset="utf-8"></head>
        <body style="margin:24px; font-family: DejaVu Sans, sans-serif;">
        <div style="padding:18px; border:2px dashed #d1d5db; border-radius:12px;">
            <div style="font-size:16px; font-weight:700; margin-bottom:6px;">{$this->esc($title)}</div>
            <div style="font-size:12px; color:#6b7280;">Type: {$this->esc($mime)}</div>
            <p style="margin-top:10px; font-size:13px;">
            Preview not available in the merged PDF. Download the original file from the attachments list.
            </p>
        </div>
        </body>
        </html>
        HTML;

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $tmp = storage_path('app/temp/' . Str::uuid() . '.pdf');
        if (!is_dir(dirname($tmp))) mkdir(dirname($tmp), 0775, true);
        $pdf->save($tmp);
        return $tmp;
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function preview(ExpenseRowAttachment $att)
    {
        $att->load('row.sheet');
        $this->authorize('download', $att->row);

        $full  = Storage::disk($att->disk)->path($att->path);
        $mime  = $att->mime ?? mime_content_type($full);
        $title = $att->original_name;

        // 1) Images -> stream original image inline (no DomPDF)
        if (str_starts_with($mime, 'image/')) {
            return response()->file($full, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="' . $title . '"',
            ]);
        }

        // 2) PDFs -> stream original PDF
        if (str_starts_with($mime, 'application/pdf')) {
            return response()->file($full, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // 3) Text -> render a one-page PDF
        if (str_starts_with($mime, 'text/')) {
            $tmp = $this->makeTempPdfForText($full, $title);
            return response()->file($tmp, ['Content-Type' => 'application/pdf']);
        }

        // 4) Others -> placeholder one-page PDF
        $tmp = $this->makeTempPdfPlaceholder($title, $mime);
        return response()->file($tmp, ['Content-Type' => 'application/pdf']);
    }
}
