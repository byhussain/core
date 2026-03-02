<?php

it('uses shared store scoped reference trait in brand model', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2).'/src/Models/Brand.php');

    expect($contents)
        ->toContain('use SmartTill\Core\Traits\HasStoreScopedReference;')
        ->toContain('use HasFactory, HasStoreScopedReference');
});
