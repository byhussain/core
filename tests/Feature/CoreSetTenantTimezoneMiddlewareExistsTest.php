<?php

it('contains tenant timezone middleware in core package', function (): void {
    $contents = file_get_contents(__DIR__.'/../../src/Http/Middleware/SetTenantTimezone.php');

    expect($contents)
        ->toContain('namespace SmartTill\\Core\\Http\\Middleware;')
        ->toContain('class SetTenantTimezone')
        ->toContain('Filament::getTenant()?->timezone?->name');
});
