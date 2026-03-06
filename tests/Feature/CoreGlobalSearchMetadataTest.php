<?php

it('defines detailed global search metadata for key business resources', function (): void {
    $resources = [
        'src/Filament/Resources/Customers/CustomerResource.php',
        'src/Filament/Resources/Suppliers/SupplierResource.php',
        'src/Filament/Resources/Products/ProductResource.php',
        'src/Filament/Resources/Sales/SaleResource.php',
        'src/Filament/Resources/PurchaseOrders/PurchaseOrderResource.php',
        'src/Filament/Resources/Payments/PaymentResource.php',
        'src/Filament/Resources/Variations/VariationResource.php',
        'src/Filament/Resources/Attributes/AttributeResource.php',
    ];

    foreach ($resources as $resource) {
        $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$resource);

        expect($contents)
            ->toContain('getGloballySearchableAttributes(): array')
            ->toContain('getGlobalSearchResultTitle')
            ->toContain('getGlobalSearchResultDetails(Model $record): array');
    }
});

it('includes reference and local id in global search attributes where applicable', function (): void {
    $resources = [
        'src/Filament/Resources/Customers/CustomerResource.php',
        'src/Filament/Resources/Suppliers/SupplierResource.php',
        'src/Filament/Resources/Products/ProductResource.php',
        'src/Filament/Resources/Sales/SaleResource.php',
        'src/Filament/Resources/PurchaseOrders/PurchaseOrderResource.php',
        'src/Filament/Resources/Brands/BrandResource.php',
        'src/Filament/Resources/Categories/CategoryResource.php',
        'src/Filament/Resources/Units/UnitResource.php',
        'src/Filament/Resources/Variations/VariationResource.php',
        'src/Filament/Resources/Attributes/AttributeResource.php',
    ];

    foreach ($resources as $resource) {
        $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$resource);

        expect($contents)
            ->toContain("'reference'")
            ->toContain("'local_id'");
    }
});

