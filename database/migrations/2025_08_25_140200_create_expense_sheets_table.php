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
        Schema::create('expense_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->default('PT: Universal Trade Services');
            $table->unsignedTinyInteger('period_month'); // 1..12
            $table->unsignedSmallInteger('period_year'); // e.g., 2025
            $table->decimal('beginning_balance', 16, 2)->nullable(); // null => '-'
            $table->timestamps();
            
            $table->index(['period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_sheets');
    }
};
