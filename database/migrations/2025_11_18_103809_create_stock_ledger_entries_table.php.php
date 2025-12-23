<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_ledger_entries', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('item', 100);                 // e.g. SMT-LOP-11022.011
            $table->text('description')->nullable();     // textarea
            $table->string('vendor', 150)->nullable();   // text

            // Money & qty
            $table->decimal('unit_price', 18, 4)->default(0);    // IDR (exc. PPN)
            $table->decimal('qty_in', 18, 4)->default(0);
            $table->decimal('qty_out', 18, 4)->default(0);
            $table->decimal('current_stock', 18, 4)->default(0); // = qty_in - qty_out

            // Dates (store as real dates, format as 20-Oct on the UI)
            $table->date('date_in')->nullable();         // received
            $table->date('date_out')->nullable();        // sale

            // Other meta
            $table->enum('unit', ['kg', 'pc'])->default('pc');
            $table->string('sales_channel', 150)->nullable();
            $table->enum('restock', ['yes', 'no'])->default('no');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledger_entries');
    }
};
