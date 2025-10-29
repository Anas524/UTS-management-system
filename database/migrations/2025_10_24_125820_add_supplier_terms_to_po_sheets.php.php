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
        Schema::table('po_sheets', function (Blueprint $t) {
            // Supplier (left column)
            $t->string('sup_company')->nullable();
            $t->text('sup_address')->nullable();
            $t->string('sup_phone')->nullable();
            $t->string('sup_email')->nullable();
            $t->string('sup_contact_person')->nullable();
            $t->string('sup_contact_phone')->nullable();
            $t->string('sup_contact_email')->nullable();

            // Terms (right column)
            $t->string('payment_terms')->nullable()->default('100% advance payment before dispatch');
            $t->string('delivery_time')->nullable()->default('14 working days from order date');
            $t->string('delivery_terms')->nullable()->default('Ex-works');
            
            // (optional) currency label shown next to Unit Price/Total
            $t->string('currency')->nullable()->default('USD');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_sheets', function (Blueprint $t) {
            $t->dropColumn([
                'sup_company','sup_address','sup_phone','sup_email',
                'sup_contact_person','sup_contact_phone','sup_contact_email',
                'payment_terms','delivery_time','delivery_terms','currency'
            ]);
        });
    }
};
