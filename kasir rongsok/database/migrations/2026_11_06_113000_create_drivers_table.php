<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('drivers')) {
            Schema::create('drivers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150)->index();
                $table->string('phone', 30)->nullable();
                $table->string('vehicle_number', 30)->nullable();
                $table->string('vehicle_type', 60)->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_account_number', 60)->nullable();
                $table->string('bank_account_name', 150)->nullable();
                $table->string('address', 255)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'driver_id')) {
                $table->foreignId('driver_id')->nullable()->after('sales_transaction_id')->constrained('drivers')->nullOnDelete();
            }
            if (!Schema::hasColumn('delivery_orders', 'driver_bank_name')) {
                $table->string('driver_bank_name', 100)->nullable()->after('driver_phone');
            }
            if (!Schema::hasColumn('delivery_orders', 'driver_bank_account_number')) {
                $table->string('driver_bank_account_number', 60)->nullable()->after('driver_bank_name');
            }
            if (!Schema::hasColumn('delivery_orders', 'driver_bank_account_name')) {
                $table->string('driver_bank_account_name', 150)->nullable()->after('driver_bank_account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_orders', 'driver_id')) {
                $table->dropForeign(['driver_id']);
                $table->dropColumn('driver_id');
            }
            if (Schema::hasColumn('delivery_orders', 'driver_bank_name')) {
                $table->dropColumn(['driver_bank_name', 'driver_bank_account_number', 'driver_bank_account_name']);
            }
        });

        Schema::dropIfExists('drivers');
    }
};

