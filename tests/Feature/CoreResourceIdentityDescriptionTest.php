<?php

it('uses sync reference helper in core resource tables', function (): void {
    $files = [
        'src/Filament/Resources/Sales/Tables/SalesTable.php',
        'src/Filament/Resources/Payments/Tables/PaymentsTable.php',
        'src/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php',
        'src/Filament/Resources/Products/Tables/ProductsTable.php',
        'src/Filament/Resources/Customers/Tables/CustomersTable.php',
        'src/Filament/Resources/Suppliers/Tables/SuppliersTable.php',
        'src/Filament/Resources/Brands/Tables/BrandsTable.php',
        'src/Filament/Resources/Categories/Tables/CategoriesTable.php',
        'src/Filament/Resources/Attributes/Tables/AttributesTable.php',
        'src/Filament/Resources/Units/Tables/UnitsTable.php',
        'src/Filament/Resources/Variations/Tables/VariationsTable.php',
        'src/Filament/Resources/Transactions/Tables/TransactionsTable.php',
        'src/Filament/Resources/Roles/Tables/RolesTable.php',
        'src/Filament/Resources/Users/Tables/UsersTable.php',
        'src/Filament/Resources/CashTransactions/Tables/CashTransactionsTable.php',
    ];

    foreach ($files as $file) {
        $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$file);
        expect($contents)->toContain('SyncReferenceColumn::make(');
    }
});
