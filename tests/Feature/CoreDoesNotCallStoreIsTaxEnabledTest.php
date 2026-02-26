<?php

it('does not call store isTaxEnabled method inside core package', function () {
    $matches = shell_exec('rg -n -- "\\$store->isTaxEnabled\\(" '.__DIR__.'/../../src 2>/dev/null');

    expect(trim((string) $matches))->toBe('');
});
