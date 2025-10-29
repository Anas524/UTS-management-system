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
        Schema::create('po_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('po_number')->nullable();   // from invoice "NO INVOICE"
            $table->date('po_date')->nullable();       // from invoice date
            $table->string('vendor')->nullable();
            $table->string('npwp')->nullable();
            $table->text('address')->nullable();
            $table->decimal('ppn_rate', 5, 2)->default(11.00); // user-editable %
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_sheets');
    }
};
