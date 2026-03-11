<?php

namespace SmartTill\Core\Services;

use Illuminate\Support\Facades\DB;
use SmartTill\Core\Enums\BatchIdentifier;
use SmartTill\Core\Enums\PurchaseOrderStatus;
use SmartTill\Core\Models\PurchaseOrder;
use SmartTill\Core\Models\Stock;
use SmartTill\Core\Models\Unit;

class PurchaseOrderTransactionService
{
    public function handlePurchaseOrderClosed(PurchaseOrder $purchaseOrder, array $receivedBarcodesByVariation = []): void
    {
        // Only act if purchase order is closed
        if ($purchaseOrder->status !== PurchaseOrderStatus::Closed) {
            return;
        }

        $purchaseOrder->recalculateTotals();
        $purchaseOrder->refresh();

        // Use safe transaction to ensure data consistency
        DB::transaction(function () use ($purchaseOrder, $receivedBarcodesByVariation) {
            $purchaseOrder->loadMissing([
                'purchaseOrderProducts.variation.unit',
                'purchaseOrderProducts.requestedUnit',
                'purchaseOrderProducts.receivedUnit',
            ]);

            // Supplier debit transaction (for supplier, with reference)
            if ($purchaseOrder->supplier) {
                $supplier = $purchaseOrder->supplier;
                $lastBalance = $supplier->transactions()->latest('id')->value('amount_balance') ?? 0;
                $amount = -abs($purchaseOrder->total_received_supplier_price); // supplier_debit is a minus entry
                $newBalance = $lastBalance + $amount; // supplier_debit decreases balance

                $supplier->transactions()
                    ->create([
                        'store_id' => $purchaseOrder->store_id,
                        'referenceable_type' => PurchaseOrder::class,
                        'referenceable_id' => $purchaseOrder->id,
                        'type' => 'supplier_credit',
                        'amount' => $amount,
                        'note' => 'Purchase order closed: supplier credit',
                        'amount_balance' => $newBalance,
                    ]);
            }

            // For each received variation, create stock-in transaction and update stock
            foreach ($purchaseOrder->purchaseOrderProducts as $pp) {
                if ($pp->received_quantity > 0) {
                    $variation = $pp->variation;
                    $variationUnit = $variation?->unit;

                    $receivedUnit = $pp->receivedUnit ?? $pp->requestedUnit ?? $variationUnit;
                    $normalizedQty = (float) $pp->received_quantity;
                    $normalizedUnitPrice = (float) $pp->received_unit_price;
                    $normalizedTaxAmount = (float) $pp->received_tax_amount;
                    $normalizedTaxPercentage = (float) $pp->received_tax_percentage;
                    $normalizedSupplierPrice = (float) $pp->received_supplier_price;

                    if ($receivedUnit && $variationUnit && $receivedUnit->dimension_id === $variationUnit->dimension_id) {
                        $normalizedQty = Unit::convertQuantity($normalizedQty, $receivedUnit, $variationUnit);
                        $normalizedUnitPrice = Unit::convertPrice($normalizedUnitPrice, $receivedUnit, $variationUnit);
                        $normalizedTaxAmount = Unit::convertPrice($normalizedTaxAmount, $receivedUnit, $variationUnit);
                        $normalizedSupplierPrice = Unit::convertPrice($normalizedSupplierPrice, $receivedUnit, $variationUnit);
                    }

                    // Note: Variation base price is NOT updated when closing PO
                    // Only stock records are updated with the received prices

                    $batchNumber = $this->nextPurchaseOrderBatchNumber($variation->id);

                    $barcodeValue = $receivedBarcodesByVariation[$variation->id] ?? '';
                    if ($barcodeValue === '') {
                        $barcodeValue = $this->getLastBarcode($variation->id) ?? $this->generateUniqueBarcode();
                    }
                    $barcode = Stock::query()
                        ->where('variation_id', $variation->id)
                        ->where('barcode', $barcodeValue)
                        ->where('batch_number', $batchNumber)
                        ->first();

                    if (! $barcode) {
                        $barcode = Stock::create([
                            'variation_id' => $variation->id,
                            'barcode' => $barcodeValue,
                            'batch_number' => $batchNumber,
                            'price' => $normalizedUnitPrice,
                            'tax_percentage' => $normalizedTaxPercentage,
                            'tax_amount' => $normalizedTaxAmount,
                            'supplier_percentage' => $pp->received_supplier_percentage,
                            'supplier_price' => $normalizedSupplierPrice,
                            'unit_id' => $variation->unit_id,
                            'stock' => 0,
                        ]);
                    }

                    $barcode->price = $normalizedUnitPrice;
                    $barcode->tax_percentage = $normalizedTaxPercentage;
                    $barcode->tax_amount = $normalizedTaxAmount;
                    $barcode->supplier_percentage = $pp->received_supplier_percentage;
                    $barcode->supplier_price = $normalizedSupplierPrice;
                    $barcode->unit_id = $variation->unit_id;
                    $barcode->stock = (float) $barcode->stock + $normalizedQty;
                    $barcode->saveQuietly();

                    $lastVariationBalance = $variation->transactions()->latest('id')->value('quantity_balance') ?? 0;
                    $newVariationBalance = $lastVariationBalance + $normalizedQty; // stock in increases balance

                    $variation->transactions()->create([
                        'store_id' => $purchaseOrder->store_id,
                        'type' => 'variation_stock_in',
                        'quantity' => $normalizedQty,
                        'note' => 'Stock in from purchase order',
                        'referenceable_type' => PurchaseOrder::class,
                        'referenceable_id' => $purchaseOrder->id,
                        'meta' => [
                            'purchase_order_id' => $purchaseOrder->id,
                            'supplier_percentage' => $pp->received_supplier_percentage,
                            'stock_id' => $barcode->id,
                            'barcode' => $barcode->barcode,
                            'batch_number' => $barcode->batch_number,
                            'received_quantity' => (float) $pp->received_quantity,
                            'received_unit_id' => $receivedUnit?->id,
                        ],
                        'quantity_balance' => $newVariationBalance,
                    ]);
                }
            }
        });
    }

    public function handlePurchaseOrderCancelled(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::Closed) {
            return;
        }

        $purchaseOrder->recalculateTotals();
        $purchaseOrder->refresh();

        DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->loadMissing(['supplier', 'variations', 'purchaseOrderProducts']);

            foreach ($purchaseOrder->variations as $variation) {
                $transactions = $variation->transactions()
                    ->where('referenceable_type', PurchaseOrder::class)
                    ->where('referenceable_id', $purchaseOrder->id)
                    ->where('type', 'variation_stock_in')
                    ->get();

                foreach ($transactions as $transaction) {
                    $quantity = (float) ($transaction->quantity ?? 0);
                    if ($quantity <= 0) {
                        continue;
                    }

                    $stockId = $transaction->meta['stock_id'] ?? null;
                    if ($stockId) {
                        $stock = Stock::find($stockId);
                        if ($stock) {
                            $stock->stock = (float) $stock->stock - $quantity;
                            $stock->saveQuietly();
                        }
                    }

                    $lastBalance = $variation->transactions()->latest('id')->value('quantity_balance') ?? 0;
                    $delta = -abs($quantity);

                    $variation->transactions()->create([
                        'store_id' => $purchaseOrder->store_id,
                        'type' => 'variation_stock_out',
                        'quantity' => $delta,
                        'note' => 'Stock out from purchase order cancel',
                        'referenceable_type' => PurchaseOrder::class,
                        'referenceable_id' => $purchaseOrder->id,
                        'meta' => [
                            'purchase_order_id' => $purchaseOrder->id,
                            'stock_id' => $stockId,
                        ],
                        'quantity_balance' => $lastBalance + $delta,
                    ]);
                }
            }

            if ($purchaseOrder->supplier) {
                $supplier = $purchaseOrder->supplier;
                $lastBalance = $supplier->transactions()->latest('id')->value('amount_balance') ?? 0;
                $amount = abs($purchaseOrder->total_received_supplier_price);
                $newBalance = $lastBalance + $amount;

                $supplier->transactions()
                    ->create([
                        'store_id' => $purchaseOrder->store_id,
                        'referenceable_type' => PurchaseOrder::class,
                        'referenceable_id' => $purchaseOrder->id,
                        'type' => 'supplier_debit',
                        'amount' => $amount,
                        'note' => 'Purchase order cancelled: supplier debit reversal',
                        'amount_balance' => $newBalance,
                    ]);
            }

            foreach ($purchaseOrder->purchaseOrderProducts as $pp) {
                $pp->received_quantity = 0;
                $pp->received_unit_price = 0;
                $pp->received_tax_percentage = 0;
                $pp->received_tax_amount = 0;
                $pp->received_supplier_percentage = 0;
                $pp->received_supplier_price = 0;
                $pp->save();
            }
        });
    }

    private function generateUniqueBarcode(): string
    {
        do {
            $base = str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
            $barcode = $base.$this->calculateEan13Checksum($base);
        } while (Stock::query()->where('barcode', $barcode)->exists());

        return $barcode;
    }

    private function calculateEan13Checksum(string $digits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $num = (int) $digits[$i];
            $sum += ($i % 2 === 0) ? $num : $num * 3;
        }

        $mod = $sum % 10;

        return $mod === 0 ? 0 : (10 - $mod);
    }

    private function nextPurchaseOrderBatchNumber(int $variationId): string
    {
        $prefix = BatchIdentifier::PurchaseOrder->value.'-';
        $max = Stock::query()
            ->where('variation_id', $variationId)
            ->where('batch_number', 'like', $prefix.'%')
            ->get(['batch_number'])
            ->reduce(function (int $carry, Stock $stock) use ($prefix): int {
                $batch = (string) $stock->batch_number;
                if (str_starts_with($batch, $prefix)) {
                    $number = (int) substr($batch, strlen($prefix));

                    return max($carry, $number);
                }

                return $carry;
            }, 0);

        return $prefix.($max + 1);
    }

    private function getLastBarcode(int $variationId): ?string
    {
        return Stock::query()
            ->where('variation_id', $variationId)
            ->latest('id')
            ->value('barcode');
    }
}
