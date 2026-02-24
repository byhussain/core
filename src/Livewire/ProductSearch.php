<?php

namespace SmartTill\Core\Livewire;

use Livewire\Component;
use SmartTill\Core\Models\Stock;
use SmartTill\Core\Models\Variation;

class ProductSearch extends Component
{
    public string $search = '';

    public array $results = [];

    public bool $showDropdown = false;

    public ?int $selectedVariationId = null;

    public ?string $statePath = null;

    public ?string $placeholder = null;

    public ?string $livewireId = null;

    public string $mode = 'variations';

    public bool $excludePreparable = false;

    public bool $allowCustom = false;

    public function mount(?string $statePath = null, ?string $placeholder = null, ?string $livewireId = null, ?string $mode = null, bool $excludePreparable = false, bool $allowCustom = false): void
    {
        $this->statePath = $statePath;
        $this->placeholder = $placeholder ?: 'Search by SKU / Description';
        $this->livewireId = $livewireId;
        $this->mode = $mode ?: 'variations';
        $this->excludePreparable = $excludePreparable;
        $this->allowCustom = $allowCustom;
    }

    public function performSearch(): void
    {
        if (empty($this->search)) {
            $this->results = [];
            $this->showDropdown = false;
            $this->selectedVariationId = null;

            return;
        }

        $tenant = filament()->getTenant();
        if (! $tenant) {
            $this->results = [];
            $this->showDropdown = false;

            return;
        }

        $storeId = $tenant->id;
        $cacheVersionKey = "product_search_version_{$storeId}";
        $version = cache()->get($cacheVersionKey, 0);
        $cacheKey = $this->mode === 'barcodes'
            ? "product_barcodes_all_{$storeId}_{$version}"
            : "products_all_{$storeId}_{$version}";

        // Cache all variations or barcode rows for this store
        $ttlHours = config('products.product_search_cache_ttl_hours', 12);
        $allItems = cache()->remember($cacheKey, now()->addHours($ttlHours), function () use ($storeId) {
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

        // Search from cached array
        $itemsCollection = collect($allItems);

        // Filter out preparable products if needed
        if ($this->excludePreparable) {
            $itemsCollection = $itemsCollection->filter(function ($item) {
                return ! ($item['is_preparable'] ?? false);
            });
        }

        $filtered = $this->mode === 'barcodes'
            ? $this->searchBarcodes($itemsCollection, $this->search)
            : $this->searchVariations($itemsCollection, $this->search);

        $filtered = $filtered
            ->take(50)
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

        $this->results = $filtered;
        // Show dropdown if there are results OR if custom items are allowed and search text exists
        $this->showDropdown = ! empty($filtered) || ($this->allowCustom && ! empty(trim($this->search)));

        // If single result, auto-select it
        if (count($filtered) === 1) {
            $this->selectProduct($filtered[0]['id']);
        }
    }

    public function updatedSearch(): void
    {
        // This lifecycle hook will be called automatically by Livewire
        // We'll use performSearch() method instead
        $this->performSearch();
    }

    public function selectProduct(int $variationId): void
    {
        $this->selectedVariationId = $variationId;

        // Clear search first
        $this->search = '';
        $this->results = [];
        $this->showDropdown = false;

        // Dispatch browser event for Alpine.js to handle
        $this->dispatch('update-form-state',
            livewireId: $this->livewireId,
            statePath: $this->statePath,
            value: $variationId
        );
    }

    public function addCustomItem(?string $description = null): void
    {
        if (! $this->allowCustom) {
            return;
        }

        $description = trim((string) $description);
        if ($description === '') {
            return;
        }

        $this->selectedVariationId = null;
        $this->search = '';
        $this->results = [];
        $this->showDropdown = false;

        $this->dispatch('update-form-state',
            livewireId: $this->livewireId,
            statePath: $this->statePath,
            value: [
                'type' => 'custom',
                'description' => $description,
            ]
        );
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('smart-core::livewire.product-search');
    }

    /**
     * Search variations from a cached collection.
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

        $searchLower = strtolower($search);
        $originalTermLower = strtolower($originalTerm);

        return $variations->filter(function ($variation) use ($originalTermLower, $tokens, $looksLikeSku) {
            // Use pre-processed lowercase versions from cache
            $sku = $variation['sku_lower'] ?? strtolower($variation['sku'] ?? '');
            $description = $variation['description_lower'] ?? strtolower($variation['description'] ?? '');
            // SKU-like search: exact match or starts with
            if ($looksLikeSku) {
                return $sku === $originalTermLower || str_starts_with($sku, $originalTermLower);
            }

            // Check exact matches first (highest priority)
            if ($sku === $originalTermLower || $description === $originalTermLower) {
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
        })->sortBy(function ($variation) use ($originalTermLower, $tokens) {
            // Use pre-processed lowercase versions from cache
            $sku = $variation['sku_lower'] ?? strtolower($variation['sku'] ?? '');
            $description = $variation['description_lower'] ?? strtolower($variation['description'] ?? '');
            $firstTokenLower = ! empty($tokens) ? strtolower($tokens[0]) : null;

            // Priority ordering:
            // 0. Exact SKU match (highest priority)
            if ($sku === $originalTermLower) {
                return 0;
            }
            // 1. Exact SKU match for first token
            if ($firstTokenLower && $sku === $firstTokenLower) {
                return 1;
            }
            // 2. SKU starts with first token
            if ($firstTokenLower && str_starts_with($sku, $firstTokenLower)) {
                return 2;
            }
            // 3. SKU starts with full search term
            if (str_starts_with($sku, $originalTermLower)) {
                return 1;
            }
            // 4. Description exact match
            if ($description === $originalTermLower) {
                return 3;
            }
            // 5. Description starts with search term
            if (str_starts_with($description, $originalTermLower)) {
                return 4;
            }

            // 6. Everything else
            return 5;
        })->values();
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
}
