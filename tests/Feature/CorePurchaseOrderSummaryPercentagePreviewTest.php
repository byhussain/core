<?php

it('recomputes requested supplier preview totals from percentage-first logic', function (): void {
    $contents = file_get_contents(__DIR__.'/../../src/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderForm.php');

    expect($contents)
        ->toContain("if (\$inputIsPercent === true) {")
        ->toContain("\$supplier = \$unit - (\$unit * ((float) \$supplierPercent / 100));")
        ->toContain("} elseif (\$inputIsPercent === false) {");
});

it('recomputes received supplier preview totals from percentage-first logic', function (): void {
    $contents = file_get_contents(__DIR__.'/../../src/Filament/Resources/PurchaseOrders/Schemas/ReceiveForm.php');

    expect($contents)
        ->toContain("if (\$inputIsPercent === true) {")
        ->toContain("\$supplierPrice = \$unitPrice - (\$unitPrice * ((float) \$supplierPercentage / 100));")
        ->toContain("} elseif (\$inputIsPercent === false) {");
});

it('recomputes close purchase order mount totals from percentage-first logic', function (): void {
    $contents = file_get_contents(__DIR__.'/../../src/Filament/Resources/PurchaseOrders/Pages/ClosePurchaseOrder.php');

    expect($contents)
        ->toContain("if (\$inputIsPercent === true) {")
        ->toContain("\$supplierPrice = \$unitPrice - (\$unitPrice * ((float) \$supplierPercentage / 100));")
        ->toContain("} elseif (\$inputIsPercent === false) {");
});
