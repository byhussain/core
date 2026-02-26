<?php

it('uses guarded focus select handlers to avoid calling select on non-text inputs', function () {
    $corePath = __DIR__.'/../../src';

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($corePath));

    $phpFiles = collect(iterator_to_array($iterator))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile() && $file->getExtension() === 'php');

    $combinedContents = $phpFiles
        ->map(fn (SplFileInfo $file): string => file_get_contents($file->getPathname()) ?: '')
        ->implode("\n");

    expect($combinedContents)
        ->not->toContain("'x-on:focus' => '\$event.target.select()'")
        ->toContain("'x-on:focus' => '\$event.target.select && \$event.target.select()'");
});
