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
        Schema::create('document_hub_entries', function (Blueprint $table) {
            $table->id();

            // Who created it (for info / filtering)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // Shown in the table (can be edited later)
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps(); // created_at is your Date column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_hub_entries');
    }
};
