<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('po_rows', function (Blueprint $table) {
            $table->decimal('price_aed', 18, 4)->default(0)->change();
            $table->decimal('qty', 18, 4)->default(0)->change();
            $table->decimal('amount', 18, 4)->default(0)->change();
        });

        Schema::table('po_sheets', function (Blueprint $table) {
            $table->decimal('ppn_rate', 18, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Round values first to avoid errors when shrinking precision/scale
        DB::statement('UPDATE po_rows SET price_aed = ROUND(price_aed, 0)');
        DB::statement('UPDATE po_rows SET qty       = ROUND(qty, 0)');
        DB::statement('UPDATE po_rows SET amount    = ROUND(amount, 2)');
        DB::statement('UPDATE po_sheets SET ppn_rate = COALESCE(ROUND(ppn_rate, 0), 0)');

        Schema::table('po_rows', function (Blueprint $table) {
            // revert to original types (adjust if your old schema differed)
            $table->integer('price_aed')->default(0)->change();
            $table->integer('qty')->default(0)->change();
            $table->decimal('amount', 18, 2)->default(0)->change();
        });

        Schema::table('po_sheets', function (Blueprint $table) {
            // revert to non-null integer with default 0 (if that’s what you had)
            $table->integer('ppn_rate')->default(0)->change();
        });
    }
};
