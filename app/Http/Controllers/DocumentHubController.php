<?php

namespace App\Http\Controllers;

use App\Models\DocumentHubEntry;
use Illuminate\Http\Request;

class DocumentHubController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function role(Request $request): string
    {
        $user = $request->user();
        // Adjust if you use a different role system
        if ($user->is_admin ?? false) return 'admin';
        return $user->role ?? 'user';
    }

    protected function ensureNotConsultant(Request $request): void
    {
        if ($this->role($request) === 'consultant') {
            abort(403, 'Consultants cannot modify Document Hub.');
        }
    }

    public function index(Request $request)
    {
        // Use your existing helper so behaviour stays same
        $role         = $this->role($request);
        $isConsultant = ($role === 'consultant');

        // Get all entries with attachment counts
        $entries = DocumentHubEntry::withCount('attachments')
            ->orderBy('folder_name')       // group visually by folder
            ->orderByDesc('created_at')    // latest first inside each folder
            ->get();

        // Group by folder_name → one card per folder on index
        $folders = $entries->groupBy('folder_name')->map(function ($group) {
            $first = $group->first();

            return (object) [
                'folder_name'     => $first->folder_name ?? 'Untitled',
                // safe slug for URL (for dh.folder route)
                'slug'            => rawurlencode($first->folder_name ?? 'Untitled'),
                'months_count'    => $group->count(),                       // how many month-rows in this folder
                'attachments_sum' => $group->sum('attachments_count'),      // total files inside this folder
                'latest_month'    => optional(
                    $group->firstWhere('month_label', '!=', null)
                )->month_label ?? optional($first->created_at)->format('M Y'),
            ];
        })->values();

        return view('dh.index', [
            'folders'      => $folders,
            'role'         => $role,
            'isConsultant' => $isConsultant,
        ]);
    }

    public function folder(Request $request, string $folder)
    {
        $role         = $this->role($request);
        $isConsultant = ($role === 'consultant');

        $folderName = urldecode($folder);

        $months = DocumentHubEntry::withCount('attachments')
            ->where('folder_name', $folderName)
            ->orderByDesc('created_at')
            ->get();

        if ($months->isEmpty()) {
            abort(404);
        }

        return view('dh.folder', [
            'folderName'    => $folderName,
            'months'        => $months,
            'role'          => $role,
            'isConsultant'  => $isConsultant,
        ]);
    }

    // Create a new empty row (date auto from created_at)
    public function store(Request $request)
    {
        $this->ensureNotConsultant($request);

        $data = $request->validate([
            'folder_name' => ['required', 'string', 'max:100'],
            'month_label' => ['nullable', 'string', 'max:50'],
        ]);

        $entry = DocumentHubEntry::create([
            'user_id'     => $request->user()->id,
            'folder_name' => $data['folder_name'],
            'month_label' => $data['month_label'] ?: now()->format('M Y'),
            // description / remarks stay null, handled in show()
        ]);

        return redirect()
            ->back()
            ->with('status', 'Record created.');
    }

    public function update(Request $request, DocumentHubEntry $entry)
    {
        $this->ensureNotConsultant($request);

        $data = $request->validate([
            'folder_name' => ['sometimes', 'string', 'max:100'],
            'month_label' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'remarks'     => ['sometimes', 'nullable', 'string'],
        ]);

        $entry->fill($data)->save();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Folder updated.');
    }

    public function show(Request $request, DocumentHubEntry $entry)
    {
        $role         = $this->role($request);
        $isConsultant = ($role === 'consultant');

        $entry->loadCount('attachments');

        return view('dh.show', [
            'entry'        => $entry,
            'role'         => $role,
            'isConsultant' => $isConsultant,
        ]);
    }

    // Delete row + cascade attachments
    public function destroy(Request $request, DocumentHubEntry $entry)
    {
        $this->ensureNotConsultant($request);

        $entry->delete(); // attachments cascade

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Row deleted.');
    }
}
