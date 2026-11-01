<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->foreignId('sales_transaction_id')->nullable()->constrained('sales_transactions')->nullOnDelete();
            $table->dateTime('delivery_date')->index();
            $table->string('recipient_name')->index();
            $table->text('recipient_address')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('driver_name')->index();
            $table->string('driver_phone')->nullable();
            $table->string('vehicle_number')->index();
            $table->string('vehicle_type')->nullable(); // Truk Fuso, Tronton, Pick Up, dll
            $table->decimal('total_weight', 12, 2)->default(0);
            $table->integer('total_packages')->default(0); // ikat / karung / drum
            $table->string('status')->default('sent')->index(); // draft, sent, received, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('scrap_item_id')->nullable()->constrained('scrap_items')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('weight', 12, 2)->default(0);
            $table->integer('package_qty')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
    }
};

