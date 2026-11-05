<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_order_items', 'price_per_kg')) {
                $table->decimal('price_per_kg', 12, 2)->default(0)->after('package_qty');
            }
            if (!Schema::hasColumn('delivery_order_items', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('price_per_kg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropColumn(['price_per_kg', 'subtotal']);
        });
    }
};

