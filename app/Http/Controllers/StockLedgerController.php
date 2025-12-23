<?php

namespace App\Http\Controllers;

use App\Models\StockLedgerEntry;
use App\Models\StockLedgerInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockLedgerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ====== INDEX: Inventory list ======
    public function index()
    {
        $user = Auth::user();
        // adjust this logic to match your project
        $role = $user->role ?? ($user->is_admin ? 'admin' : 'user');

        $inventories = StockLedgerInventory::orderBy('created_at', 'desc')->get();

        return view('sl.index', [
            'inventories' => $inventories,
            'role'        => $role,
        ]);
    }

    // ====== Create inventory (from index modal) ======
    public function storeInventory(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $inventory = StockLedgerInventory::create($data);

        return redirect()
            ->route('sl.show', $inventory->id)
            ->with('status', 'Inventory created.');
    }

    // ====== SHOW: ledger for one inventory ======
    public function show(StockLedgerInventory $inventory)
    {
        $user = Auth::user();
        // same role detection
        $role = $user->role ?? ($user->is_admin ? 'admin' : 'user');

        $rows = $inventory->entries()
            ->orderBy('id', 'asc')   // old first, new last
            ->get();

        $totalStock = $rows->sum('current_stock');

        return view('sl.show', [
            'inventory'  => $inventory,
            'rows'       => $rows,
            'totalStock' => $totalStock,
            'role'       => $role,
        ]);
    }

    // ====== API: create row (for given inventory) ======
    public function store(StockLedgerInventory $inventory, Request $request)
    {
        $data = $this->validateRow($request);
        $data['inventory_id'] = $inventory->id;

        $entry = StockLedgerEntry::create($data);

        return response()->json([
            'status' => 'ok',
            'entry'  => $entry,
        ]);
    }

    // ====== API: update row ======
    public function update(StockLedgerInventory $inventory, StockLedgerEntry $entry, Request $request)
    {
        // make sure both are integers before comparing
        if ((int) $entry->inventory_id !== (int) $inventory->id) {
            abort(404);
        }

        $data = $this->validateRow($request);

        $entry->update($data);

        return response()->json([
            'status' => 'ok',
            'entry'  => $entry,
        ]);
    }

    // ====== API: delete row ======
    public function destroyRow(StockLedgerInventory $inventory, StockLedgerEntry $entry)
    {
        if ((int) $entry->inventory_id !== (int) $inventory->id) {
            abort(404);
        }

        $entry->delete();

        return response()->json(['status' => 'ok']);
    }

    // ====== shared validator + current_stock logic ======
    protected function validateRow(Request $request): array
    {
        $validated = $request->validate([
            'item'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'vendor'        => ['nullable', 'string', 'max:150'],
            'unit_price'    => ['nullable', 'numeric', 'min:0'],
            'date_in'       => ['nullable', 'date'],
            'qty_in'        => ['nullable', 'numeric', 'min:0'],
            'unit'          => ['required', 'in:kg,pc'],
            'date_out'      => ['nullable', 'date'],
            'qty_out'       => ['nullable', 'numeric', 'min:0'],
            'sales_channel' => ['nullable', 'string', 'max:150'],
            'restock'       => ['required', 'in:yes,no'],
        ]);

        $qtyIn      = (float)($validated['qty_in']  ?? 0);
        $qtyOut     = (float)($validated['qty_out'] ?? 0);
        $unitPrice  = (float)($validated['unit_price'] ?? 0);

        $validated['qty_in']     = $qtyIn;
        $validated['qty_out']    = $qtyOut;
        $validated['unit_price'] = $unitPrice;

        $validated['current_stock'] = $qtyIn - $qtyOut;

        return $validated;
    }

    // ====== Delete entire inventory (folder) ======
    public function destroyInventory(StockLedgerInventory $inventory)
    {
        $user = Auth::user();
        $role = $user->role ?? ($user->is_admin ? 'admin' : 'user');

        // Safety: consultants cannot delete
        if ($role === 'consultant') {
            abort(403);
        }

        // Delete all rows in this inventory first
        // (Assuming relation: $inventory->entries())
        foreach ($inventory->entries as $entry) {
            // If you later add attachments relation, you can also:
            // $entry->attachments()->delete();
            $entry->delete();
        }

        // Delete the inventory itself
        $inventory->delete();

        return redirect()
            ->route('sl.index')
            ->with('status', 'Inventory deleted successfully.');
    }
}
