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
        // 1. Tambah kolom ke suppliers jika belum ada
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'member_number')) {
                $table->string('member_number', 50)->nullable()->unique()->after('code');
            }
            if (!Schema::hasColumn('suppliers', 'join_date')) {
                $table->date('join_date')->nullable()->after('member_number');
            }
            if (!Schema::hasColumn('suppliers', 'tier_override')) {
                $table->string('tier_override', 20)->nullable()->after('join_date');
            }
        });

        // 2. Buat tabel supplier_rewards
        Schema::create('supplier_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Owner / User pemberi
            $table->string('reward_title');
            $table->string('reward_type')->default('cash'); // cash, souvenir, voucher, price_bonus, etc.
            $table->decimal('reward_value', 12, 2)->default(0);
            $table->integer('points_spent')->default(0);
            $table->date('awarded_date');
            $table->string('status')->default('given'); // given, pending, claimed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_rewards');

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'tier_override')) {
                $table->dropColumn('tier_override');
            }
            if (Schema::hasColumn('suppliers', 'join_date')) {
                $table->dropColumn('join_date');
            }
            if (Schema::hasColumn('suppliers', 'member_number')) {
                $table->dropColumn('member_number');
            }
        });
    }
};
