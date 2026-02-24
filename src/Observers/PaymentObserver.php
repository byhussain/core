<?php

namespace SmartTill\Core\Observers;

use SmartTill\Core\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the Payment "creating" event.
     */
    public function creating(Payment $payment): void
    {
        if (! empty($payment->reference)) {
            return;
        }

        $storeId = $payment->store_id;

        if (! $storeId) {
            $payment->reference = 'PAY-'.time();

            return;
        }

        try {
            $count = Payment::where('store_id', $storeId)->count();
            $payment->reference = (string) ($count + 1);
        } catch (\Exception $exception) {
            $payment->reference = 'PAY-'.time();
        }
    }
}
