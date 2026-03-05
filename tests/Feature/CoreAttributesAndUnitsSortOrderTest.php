<?php

it('sorts attributes, units, and suppliers tables by latest id first', function (): void {
    $attributesTable = file_get_contents(dirname(__DIR__, 2).'/src/Filament/Resources/Attributes/Tables/AttributesTable.php');
    $unitsTable = file_get_contents(dirname(__DIR__, 2).'/src/Filament/Resources/Units/Tables/UnitsTable.php');
    $suppliersTable = file_get_contents(dirname(__DIR__, 2).'/src/Filament/Resources/Suppliers/Tables/SuppliersTable.php');

    expect($attributesTable)->toContain("->defaultSort('id', 'desc')");
    expect($unitsTable)->toContain("->defaultSort('id', 'desc')");
    expect($suppliersTable)->toContain("->defaultSort('id', 'desc')");
});
