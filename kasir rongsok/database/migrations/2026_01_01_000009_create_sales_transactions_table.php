<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('buyer_name')->index();
            $table->string('buyer_phone')->nullable();
            $table->text('buyer_address')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->dateTime('transaction_date')->index();
            $table->decimal('total_weight', 12, 2)->default(0);
            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('final_amount', 14, 2)->default(0);
            $table->decimal('estimated_cogs', 14, 2)->default(0);
            $table->decimal('estimated_profit', 14, 2)->default(0);
            $table->string('payment_status')->default('paid')->index();
            $table->string('status')->default('completed')->index();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};

