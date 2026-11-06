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
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account_number')) {
                $table->string('bank_account_number', 60)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account_name')) {
                $table->string('bank_account_name', 150)->nullable()->after('bank_account_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('suppliers', 'bank_account_name')) $columns[] = 'bank_account_name';
            if (Schema::hasColumn('suppliers', 'bank_account_number')) $columns[] = 'bank_account_number';
            if (Schema::hasColumn('suppliers', 'bank_name')) $columns[] = 'bank_name';
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
