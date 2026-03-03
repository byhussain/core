<?php

it('does not assign sale reference in sale observer creating hook', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2).'/src/Observers/SaleObserver.php');

    expect($contents)
        ->toContain('public function creating(Sale $sale): void')
        ->not->toContain('Sale::where(\'store_id\', $storeId)->count()')
        ->not->toContain('$sale->reference = $count + 1');
});

it('keeps explicit reference values in store scoped reference implementations', function (): void {
    $traitContents = file_get_contents(dirname(__DIR__, 2).'/src/Traits/HasStoreScopedReference.php');
    $observerContents = file_get_contents(dirname(__DIR__, 2).'/src/Observers/StoreScopedReferenceObserver.php');

    expect($traitContents)->toContain('if ($reference !== \'\') {');
    expect($observerContents)->toContain('if ($reference !== \'\') {');
});
