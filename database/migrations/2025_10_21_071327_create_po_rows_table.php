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
        Schema::create('po_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_sheet_id')->constrained()->cascadeOnDelete();
            $table->integer('no')->nullable();
            $table->string('sku')->nullable();
            $table->string('description')->nullable();
            $table->decimal('price_idr', 18, 0)->nullable(); // store integer rupiah
            $table->decimal('qty', 18, 2)->nullable();
            $table->string('unit')->nullable(); // e.g. kg
            $table->decimal('amount', 18, 0)->nullable();    // computed = price_idr * qty
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_rows');
    }
};
