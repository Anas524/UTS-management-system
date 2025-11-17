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
        Schema::table('expense_sheets', function (Blueprint $table) {
            // Close controls (per sheet = per month)
            $table->boolean('is_closed')->default(false)->after('period_year')->index();
            $table->timestamp('closed_at')->nullable()->after('is_closed');

            // OPTIONAL: prevent duplicate month rows per user/year
            // If you might have multiple companies per user, include company_name too.
            if (!Schema::hasColumn('expense_sheets', 'period_month')) {
                // (you already have this, keeping for clarity)
            }
        });

        // Add a composite unique if you don't already have one:
        Schema::table('expense_sheets', function (Blueprint $table) {
            // comment this in ONLY if you want to enforce uniqueness
            // and if a similar index doesn't already exist
            // $table->unique(['user_id','period_year','period_month'], 'ux_expense_sheets_user_year_month');
            //
            // If you need it per company as well, use:
            // $table->unique(['user_id','company_name','period_year','period_month'], 'ux_expense_sheets_user_company_year_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_sheets', function (Blueprint $table) {
            // drop optional uniques if you created them:
            // $table->dropUnique('ux_expense_sheets_user_year_month');
            // or:
            // $table->dropUnique('ux_expense_sheets_user_company_year_month');

            $table->dropColumn(['is_closed','closed_at']);
        });
    }
};
