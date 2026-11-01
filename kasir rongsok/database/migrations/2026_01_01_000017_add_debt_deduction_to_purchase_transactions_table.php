<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_transactions', function (Blueprint $table) {
            $table->decimal('debt_deduction_amount', 14, 2)->default(0)->after('final_amount');
            $table->decimal('net_cash_paid', 14, 2)->default(0)->after('debt_deduction_amount');
            $table->decimal('remaining_debt_after', 14, 2)->nullable()->after('net_cash_paid');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_transactions', function (Blueprint $table) {
            $table->dropColumn(['debt_deduction_amount', 'net_cash_paid', 'remaining_debt_after']);
        });
    }
};

