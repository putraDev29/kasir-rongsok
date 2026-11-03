<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Scrap Items: Unit Type (kg / pcs)
        Schema::table('scrap_items', function (Blueprint $table) {
            if (!Schema::hasColumn('scrap_items', 'unit_type')) {
                $table->string('unit_type')->default('kg')->after('unit'); // kg, pcs
            }
        });

        // 2. Purchase Transaction Items: unit_type, item_type_name, quantity
        Schema::table('purchase_transaction_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_transaction_items', 'unit_type')) {
                $table->string('unit_type')->default('kg')->after('item_code_snapshot');
            }
            if (!Schema::hasColumn('purchase_transaction_items', 'item_type_name')) {
                $table->string('item_type_name')->nullable()->after('item_name_snapshot');
            }
            if (!Schema::hasColumn('purchase_transaction_items', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(1)->after('net_weight');
            }
        });

        // 3. Purchase Transactions: bank_info, payment_notes
        Schema::table('purchase_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_transactions', 'bank_info')) {
                $table->string('bank_info')->nullable()->after('payment_method_id');
            }
            if (!Schema::hasColumn('purchase_transactions', 'payment_notes')) {
                $table->string('payment_notes')->nullable()->after('bank_info');
            }
        });

        // 4. Sales Transaction Items: unit_type, item_type_name, quantity
        Schema::table('sales_transaction_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_transaction_items', 'unit_type')) {
                $table->string('unit_type')->default('kg')->after('item_code_snapshot');
            }
            if (!Schema::hasColumn('sales_transaction_items', 'item_type_name')) {
                $table->string('item_type_name')->nullable()->after('item_name_snapshot');
            }
            if (!Schema::hasColumn('sales_transaction_items', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(1)->after('weight');
            }
        });

        // 5. Sales Transactions: bank_info, delivery_order_id
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_transactions', 'bank_info')) {
                $table->string('bank_info')->nullable()->after('payment_method_id');
            }
            if (!Schema::hasColumn('sales_transactions', 'delivery_order_id')) {
                $table->foreignId('delivery_order_id')->nullable()->after('buyer_address')->constrained('delivery_orders')->nullOnDelete();
            }
        });

        // 6. Delivery Order Items: unit_type, item_type_name, quantity
        Schema::table('delivery_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_order_items', 'unit_type')) {
                $table->string('unit_type')->default('kg')->after('item_name');
            }
            if (!Schema::hasColumn('delivery_order_items', 'item_type_name')) {
                $table->string('item_type_name')->nullable()->after('item_name');
            }
            if (!Schema::hasColumn('delivery_order_items', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(1)->after('weight');
            }
        });

        // 7. Payment Methods: bank_name, account_number, account_holder
        Schema::table('payment_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_methods', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('payment_methods', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('payment_methods', 'account_holder')) {
                $table->string('account_holder')->nullable()->after('account_number');
            }
        });

        // 8. Debts: agreement_number, guarantee_info, witness_name, agreement_notes
        Schema::table('debts', function (Blueprint $table) {
            if (!Schema::hasColumn('debts', 'agreement_number')) {
                $table->string('agreement_number')->nullable()->after('debt_number');
            }
            if (!Schema::hasColumn('debts', 'guarantee_info')) {
                $table->string('guarantee_info')->nullable()->after('remaining_amount');
            }
            if (!Schema::hasColumn('debts', 'witness_name')) {
                $table->string('witness_name')->nullable()->after('guarantee_info');
            }
            if (!Schema::hasColumn('debts', 'agreement_notes')) {
                $table->text('agreement_notes')->nullable()->after('witness_name');
            }
        });
    }

    public function down(): void
    {
        // Safe reversible drops
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn(['agreement_number', 'guarantee_info', 'witness_name', 'agreement_notes']);
        });
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_number', 'account_holder']);
        });
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'item_type_name', 'quantity']);
        });
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_id']);
            $table->dropColumn(['bank_info', 'delivery_order_id']);
        });
        Schema::table('sales_transaction_items', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'item_type_name', 'quantity']);
        });
        Schema::table('purchase_transactions', function (Blueprint $table) {
            $table->dropColumn(['bank_info', 'payment_notes']);
        });
        Schema::table('purchase_transaction_items', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'item_type_name', 'quantity']);
        });
        Schema::table('scrap_items', function (Blueprint $table) {
            $table->dropColumn(['unit_type']);
        });
    }
};

