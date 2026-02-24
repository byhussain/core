<?php

namespace SmartTill\Core\Observers;

use Illuminate\Support\Facades\Auth;
use SmartTill\Core\Enums\CashTransactionType;
use SmartTill\Core\Enums\SalePaymentStatus;
use SmartTill\Core\Models\Sale;
use SmartTill\Core\Services\CashService;

class SaleObserver
{
    public function creating(Sale $sale): void
    {
        // Only set reference if not already set
        if (! empty($sale->reference)) {
            return;
        }
        $storeId = $sale->store_id;

        // Use safe database operation to count existing sales
        try {
            $count = Sale::where('store_id', $storeId)->count();
            $sale->reference = $count + 1;
        } catch (\Exception $e) {
            // Fallback to timestamp-based reference if database fails
            $sale->reference = 'SALE-'.time();
        }

        if ($sale->payment_status === SalePaymentStatus::Paid) {
            $sale->paid_at = now();
        }
    }

    public function created(Sale $sale): void
    {
        // Note: Sale total is calculated after creation, so cash update happens in updated() observer
    }

    public function updating(Sale $sale): void
    {
        if ($sale->isDirty('payment_status') && $sale->payment_status === SalePaymentStatus::Paid) {
            $sale->paid_at = now();
        } elseif ($sale->isDirty('payment_status') && $sale->payment_status !== SalePaymentStatus::Paid) {
            $sale->paid_at = null;
        }
    }

    public function updated(Sale $sale): void
    {
        if (! Auth::check()) {
            return;
        }

        try {
            $cashService = app(CashService::class);
            $user = Auth::user();

            if (! $user) {
                \Illuminate\Support\Facades\Log::warning('SaleObserver::updated - No authenticated user', [
                    'sale_id' => $sale->id,
                ]);

                return;
            }

            // Check if cash transaction already exists for this sale
            $existingCashTransaction = null;
            try {
                $existingCashTransaction = \SmartTill\Core\Models\CashTransaction::where('referenceable_type', Sale::class)
                    ->where('referenceable_id', $sale->id)
                    ->where('type', CashTransactionType::SalePaid->value)
                    ->first();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('SaleObserver::updated - Failed to check existing cash transaction', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);

                return;
            }

            // Handle payment status changes
            if ($sale->isDirty('payment_status')) {
                // If payment status changed to 'paid', increase cash (only if total > 0 and no transaction exists)
                if ($sale->payment_status === SalePaymentStatus::Paid && $sale->total > 0 && ! $existingCashTransaction) {
                    $cashService->increaseFromSale($user, $sale);
                }
                // If payment status changed from 'paid' to something else, decrease cash
                elseif ($sale->getOriginal('payment_status') === SalePaymentStatus::Paid->value) {
                    $cashService->decreaseFromSaleRefund($user, $sale);
                }
            }
            // If sale is paid and total was just set (or updated from 0 to > 0), ensure cash is updated
            elseif ($sale->payment_status === SalePaymentStatus::Paid && $sale->total > 0 && $sale->isDirty('total')) {
                $originalTotal = $sale->getOriginal('total') ?? 0;

                // Only create cash transaction if:
                // 1. Total changed from 0 (or null) to a positive value
                // 2. No cash transaction exists yet
                // 3. Or if existing transaction has amount 0 (needs to be updated)
                if ($originalTotal == 0 && ! $existingCashTransaction) {
                    $cashService->increaseFromSale($user, $sale);
                } elseif ($existingCashTransaction && $existingCashTransaction->amount == 0 && $sale->total > 0) {
                    // Update existing transaction with 0 amount
                    try {
                        $existingCashTransaction->delete();
                        $cashService->increaseFromSale($user, $sale);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('SaleObserver::updated - Failed to delete and recreate cash transaction', [
                            'sale_id' => $sale->id,
                            'cash_transaction_id' => $existingCashTransaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SaleObserver::updated - Unexpected error', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
