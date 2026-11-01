<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrap_item_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrap_item_id')->constrained('scrap_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('old_buy_price', 12, 2)->default(0);
            $table->decimal('new_buy_price', 12, 2)->default(0);
            $table->decimal('old_sell_price', 12, 2)->default(0);
            $table->decimal('new_sell_price', 12, 2)->default(0);
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrap_item_price_histories');
    }
};

