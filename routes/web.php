<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseSheetController;
use App\Http\Controllers\POAttachmentController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RowAttachmentController;

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
        ->name('expenses.updateBeginning');

    Route::post('/expenses/{sheet}/rows', [ExpenseSheetController::class, 'addRow'])->name('expenses.rows.add');
    Route::patch('/expenses/{sheet}/rows/{row}', [ExpenseSheetController::class, 'updateRow'])->name('expenses.rows.update');
    Route::delete('/expenses/{sheet}/rows/{row}', [ExpenseSheetController::class, 'deleteRow'])->name('expenses.rows.delete');

    Route::get('/expenses/{sheet}/export', [ExpenseSheetController::class, 'export'])
        ->name('expenses.export');


    // Attachments 
    // list (optional JSON endpoint)
    Route::get('/expenses/{sheet}/rows/{row}/attachments', [RowAttachmentController::class, 'index'])
        ->name('attachments.index');

    // upload
    Route::post('/expenses/{sheet}/rows/{row}/attachments', [RowAttachmentController::class, 'store'])
        ->name('attachments.store');

    // delete
    Route::delete('/expenses/{sheet}/rows/{row}/attachments/{att}', [RowAttachmentController::class, 'destroy'])
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
});
