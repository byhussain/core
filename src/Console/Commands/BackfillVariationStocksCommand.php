<?php

namespace SmartTill\Core\Console\Commands;

use Illuminate\Console\Command;
use SmartTill\Core\Models\Variation;
use SmartTill\Core\Services\VariationDefaultStockService;

/**
 * One-off maintenance command: give every variation that has NO stock rows a
 * default zero-quantity stock entry (with a generated barcode), matching what
 * the VariationObserver now does automatically for new variations.
 *
 * Run this on the SERVER only. The created rows propagate to each POS via the
 * normal delta-sync; running it on a POS as well would mint different barcodes
 * for the same variations and create sync conflicts.
 */
class BackfillVariationStocksCommand extends Command
{
    protected $signature = 'variations:backfill-stocks
        {--store= : Limit the backfill to a single store id}
        {--dry-run : Report how many variations would be backfilled without writing}';

    protected $description = 'Create a default zero-quantity stock/barcode row for variations that have none.';

    public function handle(VariationDefaultStockService $service): int
    {
        $storeId = $this->option('store');
        if ($storeId !== null && (! is_numeric($storeId) || (int) $storeId <= 0)) {
            $this->error('The --store option must be a positive integer.');

            return self::FAILURE;
        }

        $query = Variation::query()
            ->whereDoesntHave('stocks')
            ->when($storeId !== null, fn ($query) => $query->where('store_id', (int) $storeId));

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No variations are missing stock. Nothing to backfill.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("{$total} variation(s) would receive a default stock row. (dry run — nothing written)");

            return self::SUCCESS;
        }

        $this->info("Backfilling default stock for {$total} variation(s)...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $created = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(200, function ($variations) use ($service, &$created, &$skipped, $bar): void {
            foreach ($variations as $variation) {
                $stock = $service->createFor($variation);

                if ($stock !== null) {
                    $created++;
                } else {
                    // A stock row appeared between the count and now (e.g. a
                    // concurrent write); leave it untouched.
                    $skipped++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Created {$created} stock row(s).".($skipped > 0 ? " Skipped {$skipped} already-stocked variation(s)." : ''));

        return self::SUCCESS;
    }
}
