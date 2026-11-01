<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->string('slip_number')->unique();
            $table->string('employee_name')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('period_month')->index(); // e.g. "Oktober 2026"
            $table->date('payment_date')->index();
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('allowance_amount', 14, 2)->default(0); // uang makan, transport, lembur
            $table->decimal('bonus_amount', 14, 2)->default(0); // insentif tonase timbang, kerajinan
            $table->decimal('total_income', 14, 2)->default(0);
            $table->decimal('debt_deduction', 14, 2)->default(0); // potongan kasbon / hutang
            $table->decimal('other_deduction', 14, 2)->default(0); // potongan absensi, dll
            $table->decimal('total_deduction', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0); // take home pay
            $table->string('status')->default('paid')->index(); // draft, paid
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};

