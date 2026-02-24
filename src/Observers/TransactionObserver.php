<?php

namespace SmartTill\Core\Observers;

use SmartTill\Core\Models\Transaction;

class TransactionObserver
{
    public function creating(Transaction $transaction): void
    {
        // Efficiently get the last balances for this transactionable
        $lastAmountBalance = Transaction::query()
            ->where('transactionable_type', $transaction->transactionable_type)
            ->where('transactionable_id', $transaction->transactionable_id)
            ->latest('id')
            ->value('amount_balance') ?? 0;
        $transaction->amount_balance = $lastAmountBalance + ($transaction->amount ?? 0);

        $lastQtyBalance = Transaction::query()
            ->where('transactionable_type', $transaction->transactionable_type)
            ->where('transactionable_id', $transaction->transactionable_id)
            ->latest('id')
            ->value('quantity_balance') ?? 0;
        $transaction->quantity_balance = $lastQtyBalance + ($transaction->quantity ?? 0);
    }
}
