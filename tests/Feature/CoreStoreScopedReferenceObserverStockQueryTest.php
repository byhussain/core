<?php

it('builds stock reference query via variation store scope instead of stocks.store_id', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2).'/src/Observers/StoreScopedReferenceObserver.php');

    expect($contents)
        ->toContain("'stocks' => DB::table(\$table)->whereIn('variation_id'")
        ->toContain("->from('variations')")
        ->toContain("->where('store_id', \$storeId)");
});

