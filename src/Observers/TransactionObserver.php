<?php

namespace SmartTill\Core\Observers;

use SmartTill\Core\Models\Transaction;

class TransactionObserver
{
    public function creating(Transaction $transaction): void
    {
        if ($transaction->amount_balance === null) {
            $lastAmountBalance = Transaction::query()
                ->where('transactionable_type', $transaction->transactionable_type)
                ->where('transactionable_id', $transaction->transactionable_id)
                ->latest('id')
                ->value('amount_balance') ?? 0;

            $transaction->amount_balance = $lastAmountBalance + ($transaction->amount ?? 0);
        }

        if ($transaction->quantity_balance === null) {
            $lastQtyBalance = Transaction::query()
                ->where('transactionable_type', $transaction->transactionable_type)
                ->where('transactionable_id', $transaction->transactionable_id)
                ->latest('id')
                ->value('quantity_balance') ?? 0;

            $transaction->quantity_balance = $lastQtyBalance + ($transaction->quantity ?? 0);
        }
    }
}
