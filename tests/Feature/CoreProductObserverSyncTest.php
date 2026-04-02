<?php

it('syncs single-product variation descriptions and uses wasChanged checks in the product observer', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2).'/src/Observers/ProductObserver.php');

    expect($contents)
        ->toContain("if (\$product->wasChanged('brand_id'))")
        ->toContain("if (\$product->wasChanged('name') && ! \$product->has_variations)")
        ->toContain("'description' => (string) \$product->name");
});
