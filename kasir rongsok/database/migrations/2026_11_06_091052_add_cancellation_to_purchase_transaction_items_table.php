<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_transaction_items', function (Blueprint $table) {
            $table->boolean('is_cancelled')->default(false)->after('subtotal');
            $table->string('cancel_reason')->nullable()->after('is_cancelled');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('cancel_reason');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_transaction_items', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['is_cancelled', 'cancel_reason', 'cancelled_by', 'cancelled_at']);
        });
    }
};
