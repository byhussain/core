<?php

it('shows exact created at timestamps in customer and supplier transaction tables', function (): void {
    $customerContents = file_get_contents(dirname(__DIR__, 2).'/src/Filament/Resources/Customers/RelationManagers/TransactionsRelationManager.php');
    $sharedTableContents = file_get_contents(dirname(__DIR__, 2).'/src/Filament/Resources/Transactions/Tables/TransactionsTable.php');

    expect($customerContents)
        ->toContain("TextColumn::make('created_at')")
        ->toContain("->dateTime('M d, Y g:i A')")
        ->not->toContain("TextColumn::make('created_at')\n                    ->label('Created at')\n                    ->since()");

    expect($sharedTableContents)
        ->toContain("TextColumn::make('created_at')")
        ->toContain("->dateTime('M d, Y g:i A')")
        ->not->toContain("TextColumn::make('created_at')\n                    ->label('Created at')\n                    ->since()");
});
