<?php

it('price cast does not hard require store currency relation eager loading', function () {
    $contents = file_get_contents(__DIR__.'/../../src/Casts/PriceCast.php');

    expect($contents)->not->toContain("Store::with('currency')");
    expect($contents)->toContain('Store::find($storeId)');
});
