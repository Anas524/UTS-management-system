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
        Schema::table('po_rows', function (Blueprint $table) {
            if (!Schema::hasColumn('po_rows', 'brand')) {
                $table->string('brand', 255)->nullable()->after('sku');
            }
            if (!Schema::hasColumn('po_rows', 'price_aed')) {
                // store AED in fils (like cents) as BIGINT
                $table->bigInteger('price_aed')->nullable()->after('description');
            }
            if (!Schema::hasColumn('po_rows', 'amount')) {
                // total (also in fils)
                $table->bigInteger('amount')->nullable()->after('price_aed');
            }
            if (!Schema::hasColumn('po_rows', 'unit')) {
                $table->string('unit', 50)->nullable()->after('qty');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_rows', function (Blueprint $table) {
            if (Schema::hasColumn('po_rows', 'brand')) $table->dropColumn('brand');
            if (Schema::hasColumn('po_rows', 'price_aed')) $table->dropColumn('price_aed');
            if (Schema::hasColumn('po_rows', 'amount')) $table->dropColumn('amount');
            if (Schema::hasColumn('po_rows', 'unit')) $table->dropColumn('unit');
        });
    }
};
