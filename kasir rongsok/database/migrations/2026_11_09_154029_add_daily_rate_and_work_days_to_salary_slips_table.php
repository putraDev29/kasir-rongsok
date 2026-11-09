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
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->decimal('work_days', 8, 1)->nullable()->after('payment_date');
            $table->decimal('daily_rate', 15, 2)->nullable()->after('work_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->dropColumn(['work_days', 'daily_rate']);
        });
    }
};
