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
        Schema::create('expense_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_sheet_id')->constrained('expense_sheets')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->date('date');
            $table->string('description');
            $table->string('doc_number')->nullable();
            $table->decimal('debit', 16, 2)->nullable();
            $table->decimal('credit', 16, 2)->nullable();
            $table->decimal('amount', 16, 2)->nullable();
            $table->timestamps();

            $table->index(['expense_sheet_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_rows');
    }
};
