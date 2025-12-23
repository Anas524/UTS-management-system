<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentHubAttachmentController;
use App\Http\Controllers\DocumentHubController;
use App\Http\Controllers\ExpenseSheetController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\POAttachmentController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RowAttachmentController;
use App\Http\Controllers\StockLedgerAttachmentController;
use App\Http\Controllers\StockLedgerController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// User area
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Admin area
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.role');
});

Route::middleware(['auth', 'consultant.readonly'])->scopeBindings()->group(function () {
    Route::get('/expenses', [ExpenseSheetController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseSheetController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{sheet}', [ExpenseSheetController::class, 'show'])->name('expenses.show');

    Route::patch('/expenses/{sheet}/beginning-balance', [ExpenseSheetController::class, 'updateBeginning'])
        ->middleware('expense.open')
        ->name('expenses.updateBeginning');

    Route::post('/expenses/{sheet}/rows', [ExpenseSheetController::class, 'addRow'])->middleware('expense.open')->name('expenses.rows.add');
    Route::patch('/expenses/{sheet}/rows/{row}', [ExpenseSheetController::class, 'updateRow'])->middleware('expense.open')->name('expenses.rows.update');
    Route::delete('/expenses/{sheet}/rows/{row}', [ExpenseSheetController::class, 'deleteRow'])->middleware('expense.open')->name('expenses.rows.delete');

    Route::get('/expenses/{sheet}/export', [ExpenseSheetController::class, 'export'])
        ->name('expenses.export');


    // Attachments 
    // list (optional JSON endpoint)
    Route::get('/expenses/{sheet}/rows/{row}/attachments', [RowAttachmentController::class, 'index'])
        ->name('attachments.index');

    // upload
    Route::post('/expenses/{sheet}/rows/{row}/attachments', [RowAttachmentController::class, 'store'])
        ->middleware('expense.open')
        ->name('attachments.store');

    // delete
    Route::match(['DELETE', 'POST'], '/expenses/{sheet}/rows/{row}/attachments/{att}', [RowAttachmentController::class, 'destroy'])
        ->middleware('expense.open')
        ->name('attachments.destroy');

    // view/download (not nested, easy links)
    Route::get('/attachments/{att}/download', [RowAttachmentController::class, 'download'])
        ->name('attachments.download');
    Route::get('/attachments/{att}/view', [RowAttachmentController::class, 'view'])
        ->name('attachments.view');

    Route::get('/expenses/{sheet}/rows/{row}/attachments/bundle-pdf', [RowAttachmentController::class, 'bundlePdf'])->name('attachments.bundle');

    // inline-preview that always returns a PDF page
    Route::get('/attachments/{att}/preview', [RowAttachmentController::class, 'preview'])
        ->name('attachments.preview');

    // Year close/open
    Route::post('/expenses/close-year/{year}', [ExpenseSheetController::class, 'closeYear'])
        ->name('expenses.year.close');

    Route::post('/expenses/open-next/{year}', [ExpenseSheetController::class, 'openNextYear'])
        ->name('expenses.year.openNext');

    Route::post('/expenses/reopen-year/{year}', [ExpenseSheetController::class, 'reopenYear'])
        ->name('expenses.year.reopen');


    // PO listing + create/show + rows + import
    Route::get('/po/find', [PurchaseOrderController::class, 'find'])->name('po.find');
    Route::get('/po/get',  [PurchaseOrderController::class, 'get'])->name('po.get');

    Route::get('/po',                [PurchaseOrderController::class, 'index'])->name('po.index');
    Route::get('/po/create',         [PurchaseOrderController::class, 'create'])->name('po.create');
    Route::post('/po',               [PurchaseOrderController::class, 'store'])->name('po.store');
    Route::get('/po/{po}',           [PurchaseOrderController::class, 'show'])->name('po.show');
    Route::delete('/po/{po}',        [PurchaseOrderController::class, 'destroy'])->name('po.destroy');
    Route::patch('/po/{po}',         [PurchaseOrderController::class, 'update'])->name('po.update');


    Route::post('/po/{po}/rows',     [PurchaseOrderController::class, 'addRow'])->name('po.rows.add');
    Route::patch('/po/{po}/rows/{row}', [PurchaseOrderController::class, 'updateRow'])->name('po.rows.update');
    Route::delete('/po/{po}/rows/{row}', [PurchaseOrderController::class, 'deleteRow'])->name('po.rows.delete');

    Route::post('/po/import',        [PurchaseOrderController::class, 'import'])->name('po.import');

    Route::post('/po/{po}/rows/bulk-save', [PurchaseOrderController::class, 'bulkSave'])
        ->name('po.rows.bulkSave');

    Route::get('/po/{po}/pdf', [PurchaseOrderController::class, 'exportPdf'])
        ->name('po.pdf');

    Route::get('/po/{po}/attachments', [PoAttachmentController::class, 'index'])->name('po.attachments.index');
    Route::post('/po/{po}/attachments', [PoAttachmentController::class, 'store'])->name('po.attachments.store');
    Route::delete('/po/attachments/{att}', [PoAttachmentController::class, 'destroy'])->name('po.attachments.destroy');
    Route::get('/po/attachments/{att}/download', [PoAttachmentController::class, 'download'])->name('po.attachments.download');
    Route::get('/po/attachments/{att}/view', [PoAttachmentController::class, 'view'])->name('po.attachments.view');
    Route::get('/po/{po}/attachments/bundle', [PoAttachmentController::class, 'bundle'])->name('po.attachments.bundle');

    Route::post('/po/close-year/{year}', [PurchaseOrderController::class, 'closeYear'])
        ->name('po.year.close');

    Route::post('/po/open-next/{year}', [PurchaseOrderController::class, 'openNextYear'])
        ->name('po.year.openNext');

    Route::post('/po/reopen-year/{year}', [PurchaseOrderController::class, 'reopenYear'])
        ->name('po.year.reopen');

    Route::resource('payslips', PayslipController::class)->only(['index', 'store', 'show']);

    Route::get('/payslips/{payslip}/pdf', [PayslipController::class, 'exportPdf'])
        ->name('payslips.pdf');


    // Document Hub folder view (path-based)
    Route::get('/document-hub/folder/{folder}', [DocumentHubController::class, 'folder'])
        ->name('dh.folder')
        ->where('folder', '.*'); // allow spaces, slashes, etc.

    // Document Hub main
    Route::get('/document-hub', [DocumentHubController::class, 'index'])->name('dh.index');
    Route::post('/document-hub', [DocumentHubController::class, 'store'])->name('dh.store');
    Route::get('/document-hub/{entry}', [DocumentHubController::class, 'show'])->name('dh.show');
    Route::put('/document-hub/{entry}', [DocumentHubController::class, 'update'])->name('dh.update');
    Route::delete('/document-hub/{entry}', [DocumentHubController::class, 'destroy'])->name('dh.destroy');

    // Attachments
    Route::get('/document-hub/{entry}/attachments', [DocumentHubAttachmentController::class, 'index'])
        ->name('dh.attachments.index');

    Route::post('/document-hub/{entry}/attachments', [DocumentHubAttachmentController::class, 'store'])
        ->name('dh.attachments.store');

    Route::get('/document-hub/{entry}/attachments/{attachment}', [DocumentHubAttachmentController::class, 'show'])
        ->name('dh.attachments.show');

    Route::delete('/document-hub/{entry}/attachments/{attachment}', [DocumentHubAttachmentController::class, 'destroy'])
        ->name('dh.attachments.destroy');

    Route::get('/document-hub/{entry}/attachments-download-all', [DocumentHubAttachmentController::class, 'downloadAll'])
        ->name('dh.attachments.downloadAll');

    Route::get('/document-hub/{entry}/attachments/{attachment}/download', [DocumentHubAttachmentController::class, 'download'])
        ->name('dh.attachments.download');


    // Stock Ledger summary screen for 
    // Inventory list (index)
    Route::get('/stock-ledger', [StockLedgerController::class, 'index'])
        ->name('sl.index');

    // Create a new Inventory (from modal on index)
    Route::post('/stock-ledger/inventories', [StockLedgerController::class, 'storeInventory'])
        ->name('sl.inventories.store');

    // Ledger for a specific inventory
    Route::get('/stock-ledger/{inventory}', [StockLedgerController::class, 'show'])
        ->name('sl.show');

    // INVENTORY DELETE (WHOLE FOLDER)
    Route::delete('/stock-ledger/inventories/{inventory}', [StockLedgerController::class, 'destroyInventory'])
        ->name('sl.inventories.destroy');

    // Row APIs (scoped to that inventory)
    Route::prefix('/stock-ledger/{inventory}')->group(function () {
        Route::post('/rows', [StockLedgerController::class, 'store'])
            ->name('sl.rows.store');

        Route::put('/rows/{entry}', [StockLedgerController::class, 'update'])
            ->name('sl.rows.update');

        Route::delete('/rows/{entry}', [StockLedgerController::class, 'destroyRow'])
            ->name('sl.rows.destroy');
    });

    Route::prefix('stock-ledger/{inventory}/rows/{entry}/attachments')
        ->name('sl.attachments.')
        ->group(function () {
            Route::get('/', [StockLedgerAttachmentController::class, 'index'])
                ->name('index');
            Route::post('/', [StockLedgerAttachmentController::class, 'store'])
                ->name('store');
            Route::get('{attachment}/download', [StockLedgerAttachmentController::class, 'download'])
                ->name('download');
            Route::delete('{attachment}', [StockLedgerAttachmentController::class, 'destroy'])
                ->name('destroy');
            Route::get('download-all', [StockLedgerAttachmentController::class, 'downloadAll'])
                ->name('downloadAll');
        });
});
