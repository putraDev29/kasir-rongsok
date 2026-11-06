<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ubah semua nomor bukti / kode transaksi lama agar selalu berawalan PPJ.
     */
    public function up(): void
    {
        // 1. Purchase Transactions (Nota Penerimaan / Pembelian)
        DB::statement("UPDATE purchase_transactions SET invoice_number = REPLACE(invoice_number, 'KB-', 'PPJ-') WHERE invoice_number LIKE 'KB-%'");
        DB::statement("UPDATE purchase_transactions SET invoice_number = CONCAT('PPJ-', invoice_number) WHERE invoice_number NOT LIKE 'PPJ-%'");

        // 2. Sales Transactions (Faktur Penjualan)
        DB::statement("UPDATE sales_transactions SET invoice_number = REPLACE(invoice_number, 'PJ-', 'PPJ-JL-') WHERE invoice_number LIKE 'PJ-%'");
        DB::statement("UPDATE sales_transactions SET invoice_number = CONCAT('PPJ-JL-', invoice_number) WHERE invoice_number NOT LIKE 'PPJ-%'");

        // 3. Delivery Orders (Surat Jalan Pengiriman)
        DB::statement("UPDATE delivery_orders SET delivery_number = REPLACE(delivery_number, 'SJ-', 'PPJ-SJ-') WHERE delivery_number LIKE 'SJ-%'");
        DB::statement("UPDATE delivery_orders SET delivery_number = CONCAT('PPJ-SJ-', delivery_number) WHERE delivery_number NOT LIKE 'PPJ-%'");

        // 4. Debts (Hutang Piutang / Pinjaman Modal)
        DB::statement("UPDATE debts SET debt_number = REPLACE(debt_number, 'BON-', 'PPJ-BON-') WHERE debt_number LIKE 'BON-%'");
        DB::statement("UPDATE debts SET debt_number = REPLACE(debt_number, 'HTG-', 'PPJ-HTG-') WHERE debt_number LIKE 'HTG-%' AND debt_number NOT LIKE 'PPJ-%'");
        DB::statement("UPDATE debts SET debt_number = CONCAT('PPJ-BON-', debt_number) WHERE debt_number NOT LIKE 'PPJ-%'");

        // 5. Debt Payments (Angsuran / Pembayaran Hutang)
        DB::statement("UPDATE debt_payments SET payment_number = REPLACE(payment_number, 'BAYAR-', 'PPJ-BYR-') WHERE payment_number LIKE 'BAYAR-%'");
        DB::statement("UPDATE debt_payments SET payment_number = REPLACE(payment_number, 'BAY-', 'PPJ-BYR-') WHERE payment_number LIKE 'BAY-%' AND payment_number NOT LIKE 'PPJ-%'");
        DB::statement("UPDATE debt_payments SET payment_number = CONCAT('PPJ-BYR-', payment_number) WHERE payment_number NOT LIKE 'PPJ-%'");

        // 6. Expenses (Biaya Operasional)
        DB::statement("UPDATE expenses SET expense_number = REPLACE(expense_number, 'EXP-', 'PPJ-EXP-') WHERE expense_number LIKE 'EXP-%' AND expense_number NOT LIKE 'PPJ-%'");
        DB::statement("UPDATE expenses SET expense_number = CONCAT('PPJ-EXP-', expense_number) WHERE expense_number NOT LIKE 'PPJ-%'");

        // 7. Salary Slips (Slip Gaji)
        DB::statement("UPDATE salary_slips SET slip_number = REPLACE(slip_number, 'SLIP-', 'PPJ-SLIP-') WHERE slip_number LIKE 'SLIP-%' AND slip_number NOT LIKE 'PPJ-%'");
        DB::statement("UPDATE salary_slips SET slip_number = CONCAT('PPJ-SLIP-', slip_number) WHERE slip_number NOT LIKE 'PPJ-%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap menggunakan PPJ
    }
};
