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
        Schema::create('expense_row_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_row_id')->constrained('expense_rows')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('file_name');   // stored filename
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('disk')->default('public');
            $table->string('path');        // 'attachments/{sheet}/{row}/...'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_row_attachments');
    }
};
