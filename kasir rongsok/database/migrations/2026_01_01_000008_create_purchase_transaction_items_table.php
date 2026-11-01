<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_transaction_id')->constrained('purchase_transactions')->cascadeOnDelete();
            $table->foreignId('scrap_item_id')->nullable()->constrained('scrap_items')->nullOnDelete();
            $table->string('item_name_snapshot');
            $table->string('item_code_snapshot');
            $table->decimal('gross_weight', 12, 2)->default(0);
            $table->decimal('tare_weight', 12, 2)->default(0);
            $table->decimal('net_weight', 12, 2)->default(0);
            $table->decimal('price_per_kg', 12, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_transaction_items');
    }
};

