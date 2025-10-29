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
        Schema::create('po_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('po_sheets')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');      // hashed file name in storage
            $table->string('mime', 191)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_attachments');
    }
};
