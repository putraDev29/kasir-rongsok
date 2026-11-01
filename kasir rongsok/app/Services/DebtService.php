<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DebtService
{
    /**
     * Record a new debt / loan (pinjaman modal atau kasbon).
     *
     * @throws Exception
     */
    public function createDebt(array $data, User $user): Debt
    {
        return DB::transaction(function () use ($data, $user) {
            $amount = (float) ($data['initial_amount'] ?? 0);
            if ($amount <= 0) {
                throw new Exception('Nominal pinjaman/hutang modal harus lebih besar dari Rp 0.');
            }

            $date = isset($data['date']) ? Carbon::parse($data['date']) : Carbon::now();
            $debtNumber = $this->generateDebtNumber($date);

            $supplierId = $data['supplier_id'] ?? null;
            $borrowerName = $data['borrower_name'] ?? null;
            $borrowerPhone = $data['borrower_phone'] ?? null;

            if ($supplierId) {
                $supplier = Supplier::find($supplierId);
                if ($supplier) {
                    $borrowerName = $supplier->name;
                    $borrowerPhone = $supplier->phone ?? $borrowerPhone;
                }
            }

            if (empty($borrowerName)) {
                throw new Exception('Nama peminjam / orang yang berhutang modal wajib diisi.');
            }

            $debt = Debt::create([
                'debt_number' => $debtNumber,
                'supplier_id' => $supplierId,
                'borrower_name' => $borrowerName,
                'borrower_phone' => $borrowerPhone,
                'borrower_type' => $data['borrower_type'] ?? Debt::TYPE_SUPPLIER,
                'user_id' => $user->id,
                'date' => $date,
                'due_date' => !empty($data['due_date']) ? Carbon::parse($data['due_date']) : null,
                'initial_amount' => $amount,
                'paid_amount' => 0,
                'remaining_amount' => $amount,
                'status' => Debt::STATUS_UNPAID,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLog::record(
                action: 'debt_created',
                subject: $debt,
                description: "Mencatat pinjaman modal baru {$debt->debt_number} untuk {$debt->borrower_name} senilai Rp " . number_format($amount, 0, ',', '.')
            );

            return $debt;
        });
    }

    /**
     * Record payment / installment for a debt.
     *
     * @throws Exception
     */
    public function recordPayment(Debt $debt, float $amount, ?int $paymentMethodId, ?string $notes, User $user, ?int $purchaseTxId = null): DebtPayment
    {
        return DB::transaction(function () use ($debt, $amount, $paymentMethodId, $notes, $user, $purchaseTxId) {
            $debt = Debt::lockForUpdate()->findOrFail($debt->id);

            if ($debt->remaining_amount <= 0) {
                throw new Exception("Hutang {$debt->debt_number} sudah lunas.");
            }

            if ($amount <= 0) {
                throw new Exception('Nominal pembayaran harus lebih besar dari Rp 0.');
            }

            if ($amount > (float) $debt->remaining_amount) {
                $amount = (float) $debt->remaining_amount; // Cap to remaining balance
            }

            $date = Carbon::now();
            $paymentNumber = $this->generatePaymentNumber($date);

            $before = (float) $debt->remaining_amount;
            $after = max(0, $before - $amount);
            $newPaid = (float) $debt->paid_amount + $amount;

            $status = $after <= 0 ? Debt::STATUS_PAID : Debt::STATUS_PARTIAL;

            $payment = DebtPayment::create([
                'payment_number' => $paymentNumber,
                'debt_id' => $debt->id,
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'purchase_transaction_id' => $purchaseTxId,
                'payment_date' => $date,
                'amount' => $amount,
                'remaining_before' => $before,
                'remaining_after' => $after,
                'notes' => $notes,
            ]);

            $debt->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $after,
                'status' => $status,
            ]);

            ActivityLog::record(
                action: 'debt_payment_recorded',
                subject: $debt,
                description: "Pembayaran hutang modal {$debt->debt_number} sebesar Rp " . number_format($amount, 0, ',', '.') . " ({$paymentNumber}) oleh {$user->name}"
            );

            return $payment;
        });
    }

    /**
     * Deduct from supplier debts during purchase transaction (FIFO order).
     */
    public function deductDebtsForPurchase(Supplier|int $supplier, float $deductionAmount, User $user, int $purchaseTxId): float
    {
        $supplierId = $supplier instanceof Supplier ? $supplier->id : $supplier;
        if (!$supplierId || $deductionAmount <= 0) {
            return 0;
        }

        $activeDebts = Debt::where('supplier_id', $supplierId)
            ->where('remaining_amount', '>', 0)
            ->orderBy('date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remainingToDeduct = $deductionAmount;
        $totalDeducted = 0;

        foreach ($activeDebts as $debt) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $payable = min($remainingToDeduct, (float) $debt->remaining_amount);
            $this->recordPayment(
                debt: $debt,
                amount: $payable,
                paymentMethodId: null,
                notes: "Potong langsung dari nota penerimaan barang #{$purchaseTxId}",
                user: $user,
                purchaseTxId: $purchaseTxId
            );

            $remainingToDeduct -= $payable;
            $totalDeducted += $payable;
        }

        return $totalDeducted;
    }

    /**
     * Reverse all debt deductions made by a purchase transaction.
     */
    public function reversePurchaseDebtDeductions(int $purchaseTxId): void
    {
        $payments = DebtPayment::where('purchase_transaction_id', $purchaseTxId)->get();

        foreach ($payments as $payment) {
            $debt = Debt::lockForUpdate()->find($payment->debt_id);
            if ($debt) {
                $restoredRemaining = (float) $debt->remaining_amount + (float) $payment->amount;
                $restoredPaid = max(0, (float) $debt->paid_amount - (float) $payment->amount);
                $status = $restoredPaid <= 0 ? Debt::STATUS_UNPAID : Debt::STATUS_PARTIAL;

                $debt->update([
                    'paid_amount' => $restoredPaid,
                    'remaining_amount' => $restoredRemaining,
                    'status' => $status,
                ]);
            }
            $payment->delete();
        }
    }

    public function generateDebtNumber(Carbon $date): string
    {
        $prefix = 'HTG-' . $date->format('ymd') . '-';
        $latest = Debt::where('debt_number', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->value('debt_number');
        $num = $latest ? ((int) substr($latest, strlen($prefix)) + 1) : 1;
        return $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }

    public function generatePaymentNumber(Carbon $date): string
    {
        $prefix = 'BYR-' . $date->format('ymd') . '-';
        $latest = DebtPayment::where('payment_number', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->value('payment_number');
        $num = $latest ? ((int) substr($latest, strlen($prefix)) + 1) : 1;
        return $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }
}

