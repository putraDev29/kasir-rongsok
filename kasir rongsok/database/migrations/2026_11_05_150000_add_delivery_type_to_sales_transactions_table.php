<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_transactions', 'delivery_type')) {
                $table->string('delivery_type')->default('pickup')->after('buyer_address');
            }
        });

        // Update existing transactions that are linked to a delivery order
        DB::table('sales_transactions')
            ->whereNotNull('delivery_order_id')
            ->update(['delivery_type' => 'delivery']);
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn('delivery_type');
        });
    }
};

