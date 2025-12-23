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
        Schema::table('document_hub_entries', function (Blueprint $table) {
            $table->string('folder_name')->default('Untitled')->after('user_id');
            $table->string('month_label')->nullable()->after('folder_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_hub_entries', function (Blueprint $table) {
            $table->dropColumn(['folder_name', 'month_label']);
        });
    }
};
