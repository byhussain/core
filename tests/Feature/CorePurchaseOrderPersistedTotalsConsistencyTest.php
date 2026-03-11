<?php

it('recalculates requested and received supplier totals from percentage-aware helpers', function (): void {
    $contents = file_get_contents(__DIR__.'/../../src/Models/PurchaseOrder.php');

    expect($contents)
        ->toContain('public function calculateRequestedSupplierTotal(): float')
        ->toContain('public function calculateReceivedSupplierTotal(): float')
        ->toContain('$totalRequestedSupplierPrice = $this->calculateRequestedSupplierTotal();')
        ->toContain('$totalReceivedSupplierPrice = $this->calculateReceivedSupplierTotal();')
        ->toContain('inputIsPercentage: $product->requested_supplier_is_percentage,')
        ->toContain('inputIsPercentage: $product->received_supplier_is_percentage,');
});

it('uses calculated supplier totals on the purchase order detail page', function (): void {
    $contents = file_get_contents(__DIR__.'/../../src/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderInfolist.php');

    expect($contents)
        ->toContain("TextEntry::make('calculated_total_requested_supplier_price')")
        ->toContain("->state(fn (PurchaseOrder \$record): float => \$record->calculateRequestedSupplierTotal())")
        ->toContain("TextEntry::make('calculated_total_received_supplier_price')")
        ->toContain("->state(fn (PurchaseOrder \$record): float => \$record->calculateReceivedSupplierTotal())");
});

it('uses the purchase order calculated total when displaying supplier transaction amounts', function (): void {
    $contents = file_get_contents(__DIR__.'/../../src/Filament/Resources/Transactions/Tables/TransactionsTable.php');

    expect($contents)
        ->toContain("->getStateUsing(fn (\$record) => abs(self::resolveDisplayedAmount(\$record)))")
        ->toContain("&& in_array(\$record->type, ['supplier_credit', 'supplier_debit'], true)")
        ->toContain('$purchaseOrderAmount = (float) $record->referenceable->calculateReceivedSupplierTotal();');
});

