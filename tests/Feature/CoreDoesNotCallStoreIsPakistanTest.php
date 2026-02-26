<?php

it('does not call store isPakistan method inside core package', function () {
    $matches = shell_exec('rg -n -- "->isPakistan\\(" '.__DIR__.'/../../src 2>/dev/null');

    expect(trim((string) $matches))->toBe('');
});
