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
        Schema::table('po_sheets', function (Blueprint $table) {
            // Ship To
            $table->string('ship_to_recipient', 255)->nullable()
              ->after('ship_to_address'); // adjust position if you prefer after phone

            // User-entered list (one per line)
            $table->text('conditions_terms')->nullable()
              ->after('delivery_terms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_sheets', function (Blueprint $table) {
            $table->dropColumn(['ship_to_recipient', 'conditions_terms']);
        });
    }
};
