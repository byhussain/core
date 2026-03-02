<?php

it('uses store scoped reference trait in all core resource models with references', function (): void {
    $models = [
        'src/Models/Attribute.php',
        'src/Models/Brand.php',
        'src/Models/Category.php',
        'src/Models/Customer.php',
        'src/Models/Payment.php',
        'src/Models/Product.php',
        'src/Models/PurchaseOrder.php',
        'src/Models/Sale.php',
        'src/Models/Supplier.php',
        'src/Models/Transaction.php',
        'src/Models/Unit.php',
        'src/Models/Variation.php',
        'src/Models/StoreSetting.php',
    ];

    foreach ($models as $modelPath) {
        $contents = file_get_contents(dirname(__DIR__, 2).'/'.$modelPath);

        expect($contents)
            ->toContain('use SmartTill\Core\Traits\HasStoreScopedReference;')
            ->toContain('HasStoreScopedReference');
    }
});

