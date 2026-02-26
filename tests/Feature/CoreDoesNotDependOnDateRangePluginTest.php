<?php

it('does not reference external date range filter plugin in core resources', function () {
    $srcPath = __DIR__.'/../../src';

    $files = [
        $srcPath.'/Filament/Resources/Payments/Tables/PaymentsTable.php',
        $srcPath.'/Filament/Resources/Customers/RelationManagers/TransactionsRelationManager.php',
        $srcPath.'/Filament/Resources/Variations/RelationManagers/TransactionsRelationManager.php',
    ];

    foreach ($files as $file) {
        expect(file_get_contents($file))
            ->not->toContain('Malzariey\\FilamentDaterangepickerFilter\\Filters\\DateRangeFilter');
    }
});
