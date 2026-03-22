<?php

namespace SmartTill\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SmartTill\Core\Enums\PaymentMethod;
use SmartTill\Core\Models\Customer;
use SmartTill\Core\Models\Payment;
use SmartTill\Core\Models\Supplier;
use SmartTill\Core\Models\Transaction;

class PaymentService
{
    /**
     * Record a payment and create corresponding transaction entry
     */
    public function recordPayment(
        Model $payable,
        float $amount,
        PaymentMethod $paymentMethod,
        ?string $reference = null,
        ?string $note = null
    ): Payment {
        return DB::transaction(function () use ($payable, $amount, $paymentMethod, $reference, $note) {
            if (! isset($payable->store_id)) {
                throw new \InvalidArgumentException('Payable model must have a store_id.');
            }

            // Create payment record
            $payment = Payment::create([
                'store_id' => $payable->store_id,
                'payable_type' => $payable::class,
                'payable_id' => $payable->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'note' => $note,
            ]);

            if ($payable instanceof Supplier) {
                $lastBalance = $payable->transactions()->latest('id')->value('amount_balance') ?? 0;
                $transactionAmount = $amount;
                $transactionType = $amount >= 0 ? 'supplier_debit' : 'supplier_credit';
                $defaultNote = $note ? "Supplier payment: {$note}" : 'Supplier payment';
                $newBalance = $lastBalance + $transactionAmount;

                $payable->transactions()->create([
                    'store_id' => $payable->store_id,
                    'referenceable_type' => Payment::class,
                    'referenceable_id' => $payment->id,
                    'type' => $transactionType,
                    'amount' => $transactionAmount,
                    'amount_balance' => $newBalance,
                    'note' => $defaultNote,
                    'meta' => [
                        'payment_id' => $payment->id,
                        'payment_method' => $payment->payment_method->value,
                        'reference' => $reference,
                    ],
                ]);

                return $payment->fresh();
            }

            if (! $payable instanceof Customer) {
                throw new \InvalidArgumentException('Unsupported payable model for payment recording.');
            }

            $lastBalance = $this->getLatestCustomerBalance($payable);

            if ($amount >= 0) {
                $transactionAmount = -$amount;
                $transactionType = 'customer_credit';
                $defaultNote = $note ? "Payment received: {$note}" : 'Customer payment received';
            } else {
                $transactionAmount = abs($amount);
                $transactionType = 'customer_debit';
                $defaultNote = $note ? "Receivable added: {$note}" : 'Receivable added';
            }

            $newBalance = $lastBalance + $transactionAmount;

            Transaction::query()->create([
                'store_id' => $payable->store_id,
                'transactionable_type' => $payable->getMorphClass(),
                'transactionable_id' => $payable->getKey(),
                'referenceable_type' => Payment::class,
                'referenceable_id' => $payment->id,
                'type' => $transactionType,
                'amount' => $transactionAmount,
                'amount_balance' => $newBalance,
                'note' => $defaultNote,
                'meta' => [
                    'payment_id' => $payment->id,
                    'payment_method' => $payment->payment_method->value,
                    'reference' => $reference,
                ],
            ]);

            if ($paymentMethod === PaymentMethod::Cash && $amount > 0 && \Illuminate\Support\Facades\Auth::check()) {
                try {
                    $cashService = app(\SmartTill\Core\Services\CashService::class);
                    $user = \Illuminate\Support\Facades\Auth::user();
                    if ($user) {
                        $cashService->increaseFromPayment($user, $payment);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('PaymentService::recordPayment - No authenticated user for cash payment', [
                            'payment_id' => $payment->id,
                            'payable_type' => $payable::class,
                            'payable_id' => $payable->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail the payment recording
                    \Illuminate\Support\Facades\Log::error('PaymentService::recordPayment - Failed to increase cash from payment', [
                        'payment_id' => $payment->id,
                        'payable_type' => $payable::class,
                        'payable_id' => $payable->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            return $payment->fresh();
        });
    }

    protected function getLatestCustomerBalance(Customer $customer): float
    {
        return (float) (Transaction::query()
            ->where('store_id', $customer->store_id)
            ->where('transactionable_id', $customer->getKey())
            ->whereIn('transactionable_type', Customer::transactionMorphTypes())
            ->latest('id')
            ->value('amount_balance') ?? 0);
    }
}
