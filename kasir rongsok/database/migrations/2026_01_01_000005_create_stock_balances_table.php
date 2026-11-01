<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrap_item_id')->unique()->constrained('scrap_items')->cascadeOnDelete();
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('total_in', 12, 2)->default(0);
            $table->decimal('total_out', 12, 2)->default(0);
            $table->decimal('average_buy_price', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};

