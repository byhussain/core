<?php

it('adds a requested line totals column and state field in purchase order form', function (): void {
    $formContents = file_get_contents(__DIR__.'/../../src/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderForm.php');

    expect($formContents)
        ->toContain("Repeater\\TableColumn::make('Totals')->width('10%')")
        ->toContain("TextInput::make('requested_line_total')")
        ->toContain("->label('Totals')")
        ->toContain('self::updateRequestedLineTotal($get, $set);')
        ->toContain('private static function updateRequestedLineTotal(callable $get, callable $set): void');
});

it('uses supplier line totals in purchase order print table rows', function (): void {
    $printContents = file_get_contents(__DIR__.'/../../resources/views/print/purchase-order.blade.php');

    expect($printContents)
        ->toContain('$reqLineSupplierTotal = $reqQty * $reqSupplierPrice;')
        ->toContain('$recLineSupplierTotal = $recQty * $recSupplierPrice;')
        ->toContain('$requestedSubtotal += $reqLineUnitTotal;')
        ->toContain('$receivedSubtotal += $recLineUnitTotal;')
        ->toContain('{{ $fmtNoRound($reqLineSupplierTotal) }} {{ $currencyCode }}')
        ->toContain('{{ $recLineSupplierTotal > 0 ? $fmtNoRound($recLineSupplierTotal).\' \'.$currencyCode : \'—\' }}');
});

it('guards customer and supplier relation manager payment actions for missing fields', function (): void {
    $customerTransactions = file_get_contents(__DIR__.'/../../src/Filament/Resources/Customers/RelationManagers/TransactionsRelationManager.php');
    $supplierTransactions = file_get_contents(__DIR__.'/../../src/Filament/Resources/Suppliers/RelationManagers/TransactionsRelationManager.php');

    expect($customerTransactions)
        ->toContain("! array_key_exists('amount', \$data) || ! array_key_exists('payment_method', \$data)")
        ->toContain("->title('Missing payment details')")
        ->toContain("->body('Please provide amount and payment method.')");

    expect($supplierTransactions)
        ->toContain("! array_key_exists('amount', \$data) || ! array_key_exists('payment_method', \$data)")
        ->toContain("->title('Missing payment details')")
        ->toContain("->body('Please provide amount and payment method.')");
});

