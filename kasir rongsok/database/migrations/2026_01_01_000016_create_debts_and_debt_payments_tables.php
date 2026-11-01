<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->string('debt_number')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('borrower_name')->index();
            $table->string('borrower_phone')->nullable();
            $table->string('borrower_type')->default('supplier')->index(); // supplier, karyawan, lainnya
            $table->foreignId('user_id')->constrained('users'); // pencatat pinjaman
            $table->date('date')->index();
            $table->date('due_date')->nullable();
            $table->decimal('initial_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0)->index();
            $table->string('status')->default('unpaid')->index(); // unpaid, partial, paid
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('debt_id')->constrained('debts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('purchase_transaction_id')->nullable()->constrained('purchase_transactions')->nullOnDelete();
            $table->dateTime('payment_date')->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('remaining_before', 14, 2)->default(0);
            $table->decimal('remaining_after', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('debts');
    }
};

