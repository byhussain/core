<?php

namespace SmartTill\Core\Filament\Forms\Components;

use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Cache;
use SmartTill\Core\Models\Stock;
use SmartTill\Core\Models\Variation;

class ProductSearchInput extends Field
{
    use HasPlaceholder;

    protected string $view = 'smart-core::filament.forms.components.product-search-input';

    protected ?\Closure $onProductSelected = null;

    protected int $maxResults = 50;

    protected string $mode = 'variations';

    protected bool $excludePreparable = false;

    protected bool $allowCustom = false;

    public function onProductSelected(\Closure $callback): static
    {
        $this->onProductSelected = $callback;

        return $this;
    }

    public function getOnProductSelected(): ?\Closure
    {
        return $this->onProductSelected;
    }

    public function getSearchResultsUsing(\Closure $callback): static
    {
        // This method exists for compatibility but we use our own search
        return $this;
    }

    public function maxResults(int $maxResults): static
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    public function mode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function excludePreparable(bool $exclude = true): static
    {
        $this->excludePreparable = $exclude;

        return $this;
    }

    public function allowCustom(bool $allowCustom = true): static
    {
        $this->allowCustom = $allowCustom;

        return $this;
    }

    public function shouldExcludePreparable(): bool
    {
        return $this->excludePreparable;
    }

    public function allowsCustom(): bool
    {
        return $this->allowCustom;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getMaxResults(): int
    {
        return $this->maxResults;
    }

    public function getSearchResults(string $search): array
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return [];
        }

        $storeId = $tenant->id;
        $cacheVersionKey = "product_search_version_{$storeId}";
        $version = Cache::get($cacheVersionKey, 0);
        $cacheKey = $this->mode === 'barcodes'
            ? "product_barcodes_all_{$storeId}_{$version}"
            : "products_all_{$storeId}_{$version}";

        // Cache all variations or barcode rows for this store
        $ttlHours = config('products.product_search_cache_ttl_hours', 12);
        $allItems = Cache::remember($cacheKey, now()->addHours($ttlHours), function () use ($storeId) {
            if ($this->mode === 'barcodes') {
                $barcodes = Stock::query()
                    ->whereHas('variation', fn ($query) => $query->where('store_id', $storeId))
                    ->with(['variation.product:id,is_preparable'])
                    ->get()
                    ->values();

                $stockTotals = $barcodes
                    ->groupBy('variation_id')
                    ->map(fn ($group) => $group->sum('stock'));

                return $barcodes
                    ->map(function ($barcode) use ($stockTotals) {
                        $variation = $barcode->variation;
                        $sku = $variation?->sku ?? '';
                        $description = $variation?->description ?? '';
                        $barcodeValue = $barcode->barcode ?? '';
                        $batch = $barcode->batch_number ?? '';

                        return [
                            'id' => $barcode->id,
                            'variation_id' => $variation?->id,
                            'barcode' => $barcodeValue,
                            'barcode_lower' => strtolower($barcodeValue),
                            'batch_number' => $batch,
                            'batch_lower' => strtolower($batch),
                            'sku' => $sku,
                            'sku_lower' => strtolower($sku),
                            'description' => $description,
                            'description_lower' => strtolower($description),
                            'brand_name' => $variation?->brand_name,
                            'stock' => (float) ($stockTotals[$barcode->variation_id] ?? $barcode->stock ?? 0),
                            'is_preparable' => (bool) ($variation?->product?->is_preparable ?? false),
                        ];
                    })
                    ->toArray();
            }

            return Variation::query()
                ->where('store_id', $storeId)
                ->withBarcodeStock()
                ->with('product:id,is_preparable')
                ->select('id', 'sku', 'description', 'brand_name', 'product_id')
                ->get()
                ->map(function ($variation) {
                    $sku = $variation->sku ?? '';
                    $description = $variation->description ?? '';

                    return [
                        'id' => $variation->id,
                        'sku' => $sku,
                        'sku_lower' => strtolower($sku),
                        'description' => $description,
                        'description_lower' => strtolower($description),
                        'brand_name' => $variation->brand_name,
                        'stock' => $variation->stock,
                        'is_preparable' => (bool) ($variation->product?->is_preparable ?? false),
                    ];
                })
                ->toArray();
        });

        // Search from cached array with priority ordering
        $itemsCollection = collect($allItems);

        // Filter out preparable products if needed
        if ($this->excludePreparable) {
            $itemsCollection = $itemsCollection->filter(function ($item) {
                return ! ($item['is_preparable'] ?? false);
            });
        }

        $results = $this->mode === 'barcodes'
            ? $this->searchBarcodes($itemsCollection, $search)
            : $this->searchVariations($itemsCollection, $search);

        $results = $results
            ->take($this->maxResults)
            ->map(function ($item) {
                // Format stock: always return as string for consistent type across requests
                // This prevents Livewire checksum issues caused by type changes (int vs string)
                $stock = $item['stock'];
                $formattedStock = $stock == intval($stock)
                    ? (string) intval($stock)
                    : rtrim(rtrim(number_format($stock, 2, '.', ''), '0'), '.');

                $label = $item['brand_name']
                    ? sprintf(
                        '%s - %s - %s',
                        $item['sku'],
                        $item['brand_name'],
                        $item['description']
                    )
                    : sprintf(
                        '%s - %s',
                        $item['sku'],
                        $item['description']
                    );

                return [
                    'id' => (int) $item['id'],
                    'label' => trim($label),
                    'sku' => $item['sku'] !== null ? (string) $item['sku'] : null,
                    'description' => (string) $item['description'],
                    'brand_name' => $item['brand_name'] !== null ? (string) $item['brand_name'] : null,
                    'stock' => $formattedStock,
                    'barcode' => $item['barcode'] ?? null,
                    'batch_number' => $item['batch_number'] ?? null,
                    'variation_id' => isset($item['variation_id']) ? (int) $item['variation_id'] : null,
                ];
            })
            ->values()
            ->toArray();

        return $results;
    }

    /**
     * Search barcode batches from a cached collection.
     */
    private function searchBarcodes($barcodes, string $search)
    {
        $search = trim($search);
        if ($search === '') {
            return $barcodes;
        }

        $originalTerm = $search;
        $rawTokens = preg_split('/\s+/', $search);
        $tokens = array_values(array_filter(array_map(function ($t) {
            $clean = preg_replace('/[^\p{L}\p{N}_-]+/u', '', $t ?? '');
            $clean = ltrim($clean, '+-~><()|"');
            $clean = rtrim($clean, '+-~><()|"');
            $clean = trim($clean, '-_');

            if ($clean === '' || preg_match('/^[-_]+$/', $clean)) {
                return null;
            }

            return $clean;
        }, $rawTokens)));

        if (empty($tokens)) {
            return $barcodes;
        }

        $originalTermLower = strtolower($originalTerm);

        return $barcodes->filter(function ($item) use ($originalTermLower, $tokens) {
            $barcode = $item['barcode_lower'] ?? strtolower($item['barcode'] ?? '');
            $sku = $item['sku_lower'] ?? strtolower($item['sku'] ?? '');
            $description = $item['description_lower'] ?? strtolower($item['description'] ?? '');
            $batch = $item['batch_lower'] ?? strtolower($item['batch_number'] ?? '');

            if ($sku === $originalTermLower || $description === $originalTermLower) {
                return true;
            }

            foreach ($tokens as $token) {
                $tokenLower = strtolower($token);
                $tokenFound = str_contains($barcode, $tokenLower)
                    || str_contains($sku, $tokenLower)
                    || str_contains($description, $tokenLower)
                    || str_contains($batch, $tokenLower);

                if (! $tokenFound) {
                    return false;
                }
            }

            return true;
        })->sortBy(function ($item) use ($originalTermLower) {
            $barcode = $item['barcode_lower'] ?? strtolower($item['barcode'] ?? '');
            $sku = $item['sku_lower'] ?? strtolower($item['sku'] ?? '');
            $description = $item['description_lower'] ?? strtolower($item['description'] ?? '');
            if ($sku === $originalTermLower || $description === $originalTermLower) {
                return 0;
            }
            if (str_starts_with($sku, $originalTermLower) || str_starts_with($description, $originalTermLower)) {
                return 1;
            }
            if ($barcode === $originalTermLower) {
                return 2;
            }
            if (str_starts_with($barcode, $originalTermLower)) {
                return 3;
            }

            return 4;
        })
            ->unique('variation_id')
            ->values();
    }

    /**
     * Search variations from a cached collection with priority ordering.
     */
    private function searchVariations($variations, string $search)
    {
        $search = trim($search);
        if ($search === '') {
            return $variations;
        }

        $originalTerm = $search;

        // Tokenize search term
        $rawTokens = preg_split('/\s+/', $search);
        $tokens = array_values(array_filter(array_map(function ($t) {
            $clean = preg_replace('/[^\p{L}\p{N}_-]+/u', '', $t ?? '');
            $clean = ltrim($clean, '+-~><()|"');
            $clean = rtrim($clean, '+-~><()|"');
            $clean = trim($clean, '-_');

            if ($clean === '' || preg_match('/^[-_]+$/', $clean)) {
                return null;
            }

            return $clean;
        }, $rawTokens)));

        if (empty($tokens)) {
            return $variations;
        }

        // Check if it looks like a SKU
        $isSingleToken = count($tokens) === 1;
        $hasSkuDelimiter = str_contains($originalTerm, '-') || str_contains($originalTerm, '_');
        $looksLikeSku = $isSingleToken && $hasSkuDelimiter && preg_match('/^[A-Za-z0-9_-]{3,}$/', $originalTerm);

        $originalTermLower = strtolower($originalTerm);
        $firstToken = ! empty($tokens) ? $tokens[0] : null;
        $firstTokenLower = $firstToken ? strtolower($firstToken) : null;

        return $variations->filter(function ($variation) use ($originalTermLower, $tokens, $looksLikeSku, $firstTokenLower) {
            $sku = $variation['sku_lower'] ?? strtolower($variation['sku'] ?? '');
            $description = $variation['description_lower'] ?? strtolower($variation['description'] ?? '');

            // SKU-like search: exact match or starts with
            if ($looksLikeSku) {
                return $sku === $originalTermLower || str_starts_with($sku, $originalTermLower);
            }

            // Check exact matches first
            if ($sku === $originalTermLower || $description === $originalTermLower) {
                return true;
            }

            // Priority: If first token exactly matches SKU, include it (even if other tokens don't match)
            // This handles cases like "901 g" where SKU "901" should appear even if "g" isn't in description
            if ($firstTokenLower && ($sku === $firstTokenLower || str_starts_with($sku, $firstTokenLower))) {
                return true;
            }

            // Check prefix matches
            if (str_starts_with($sku, $originalTermLower) || str_starts_with($description, $originalTermLower)) {
                return true;
            }

            // Check if all tokens are found in SKU or description
            foreach ($tokens as $token) {
                $tokenLower = strtolower($token);
                $tokenFound = str_contains($sku, $tokenLower)
                    || str_contains($description, $tokenLower);

                // Check dashed description (like P-O-P)
                if (! $tokenFound) {
                    $dashedTerm = str_replace(' ', '', $originalTermLower);
                    if (str_contains($dashedTerm, '-') && str_contains($description, $dashedTerm)) {
                        $tokenFound = true;
                    }
                }

                if (! $tokenFound) {
                    return false;
                }
            }

            return true;
        })->sortBy(function ($variation) use ($originalTermLower, $firstTokenLower, $tokens) {
            $sku = $variation['sku_lower'] ?? strtolower($variation['sku'] ?? '');
            $description = $variation['description_lower'] ?? strtolower($variation['description'] ?? '');

            // Check if all tokens (except first) are found in description
            $allOtherTokensMatch = true;
            if (count($tokens) > 1) {
                for ($i = 1; $i < count($tokens); $i++) {
                    $tokenLower = strtolower($tokens[$i]);
                    if (! str_contains($description, $tokenLower) && ! str_contains($sku, $tokenLower)) {
                        $allOtherTokensMatch = false;
                        break;
                    }
                }
            }

            // Priority ordering (SKU first):
            // 0. Exact SKU match for full search term (highest priority)
            if ($sku === $originalTermLower) {
                return 0;
            }

            // 1. Exact SKU match for first token + all other tokens match
            if ($firstTokenLower && $sku === $firstTokenLower && $allOtherTokensMatch) {
                return 1;
            }

            // 2. Exact SKU match for first token (but other tokens don't match)
            if ($firstTokenLower && $sku === $firstTokenLower) {
                return 2;
            }

            // 3. SKU starts with first token (but not exact match) + all other tokens match
            if ($firstTokenLower && $sku !== $firstTokenLower && str_starts_with($sku, $firstTokenLower) && $allOtherTokensMatch) {
                return 3;
            }

            // 4. SKU starts with first token (but not exact match)
            if ($firstTokenLower && $sku !== $firstTokenLower && str_starts_with($sku, $firstTokenLower)) {
                return 4;
            }

            // 5. SKU starts with full search term
            if (str_starts_with($sku, $originalTermLower)) {
                return 5;
            }

            // 6. SKU contains first token (but doesn't start with it)
            if ($firstTokenLower && ! str_starts_with($sku, $firstTokenLower) && str_contains($sku, $firstTokenLower)) {
                return 6;
            }

            // 7. Description exact match for full search term
            if ($description === $originalTermLower) {
                return 7;
            }

            // 8. Description starts with full search term
            if (str_starts_with($description, $originalTermLower)) {
                return 8;
            }

            // 9. Description contains first token
            if ($firstTokenLower && str_contains($description, $firstTokenLower)) {
                return 9;
            }

            // 10. Everything else (other token matches)
            return 10;
        })->values();
    }
}
