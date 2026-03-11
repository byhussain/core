<?php

it('calculates requested line total from raw percentage input before rounded supplier price', function (): void {
    $contents = file_get_contents(__DIR__.'/../../src/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderForm.php');

    expect($contents)
        ->toContain("if (is_string(\$rawInput) && str_ends_with(\$rawInput, '%')) {")
        ->toContain("\$supplierPrice = \$unitPrice - (\$unitPrice * (\$numericValue / 100));")
        ->toContain("} elseif (is_numeric(\$rawInput)) {")
        ->toContain("} elseif (is_numeric(\$get('requested_supplier_price'))) {");
});
