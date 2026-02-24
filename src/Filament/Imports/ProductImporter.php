<?php

namespace SmartTill\Core\Filament\Imports;

use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use SmartTill\Core\Models\Brand;
use SmartTill\Core\Models\Category;
use SmartTill\Core\Models\Product;
use SmartTill\Core\Models\Unit;
use SmartTill\Core\Models\Variation;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        $columns = [
            ImportColumn::make('category')
                ->label('Category')
                ->exampleHeader('Category')
                ->examples(['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Beauty', 'Toys', 'Automotive', 'Health', 'Food'])
                ->relationship(resolveUsing: function (?string $state, array $options): ?Category {
                    if (blank($state)) {
                        return null;
                    }

                    $storeId = $options['store_id'];
                    if (blank($storeId)) {
                        throw ValidationException::withMessages([
                            'category' => 'Cannot resolve store for this import job. Please start the import from within a store context.',
                        ]);
                    }

                    $name = trim((string) $state);

                    // Check for existing category (including soft-deleted ones)
                    $category = Category::withTrashed()
                        ->where('store_id', $storeId)
                        ->where('name', $name)
                        ->first();

                    if ($category) {
                        // If soft-deleted, restore it
                        if ($category->trashed()) {
                            $category->restore();
                        }

                        return $category;
                    }

                    // Create new category if it doesn't exist
                    $category = new Category(['name' => $name]);
                    $category->store_id = $storeId;
                    $category->save();

                    return $category;
                }),

            ImportColumn::make('brand')
                ->label('Brand')
                ->exampleHeader('Brand')
                ->examples(['Samsung', 'Nike', 'Apple', 'Sony', 'Adidas', 'Canon', 'Dell', 'HP', 'LG', 'Microsoft'])
                ->relationship(resolveUsing: function (?string $state, array $options): ?Brand {
                    if (blank($state)) {
                        return null;
                    }

                    $storeId = $options['store_id'];
                    if (blank($storeId)) {
                        throw ValidationException::withMessages([
                            'brand' => 'Cannot resolve store for this import job. Please start the import from within a store context.',
                        ]);
                    }

                    $name = trim((string) $state);

                    // Check for existing brand (including soft-deleted ones)
                    $brand = Brand::withTrashed()
                        ->where('store_id', $storeId)
                        ->where('name', $name)
                        ->first();

                    if ($brand) {
                        // If soft-deleted, restore it
                        if ($brand->trashed()) {
                            $brand->restore();
                        }

                        return $brand;
                    }

                    // Create new brand if it doesn't exist
                    $brand = new Brand(['name' => $name]);
                    $brand->store_id = $storeId;
                    $brand->save();

                    return $brand;
                }),

            ImportColumn::make('name')
                ->label('Name')
                ->exampleHeader('Name')
                ->examples(['iPhone 15 Pro', 'Air Max 270', 'MacBook Pro 16"', 'PlayStation 5', 'Ultraboost 22', 'EOS R5 Camera', 'XPS 13 Laptop', 'LaserJet Pro', 'OLED 55" TV', 'Surface Pro 9'])
                ->rules(['required', 'string', 'max:255'])
                ->fillRecordUsing(function ($record, $state) {
                    return $record;
                }),

            ImportColumn::make('description')
                ->label('Description')
                ->exampleHeader('Description')
                ->examples(['Latest iPhone with advanced camera system', 'Comfortable running shoes with air cushioning', 'Professional laptop for creative work', 'Next-gen gaming console with 4K support', 'High-performance running shoes', 'Professional mirrorless camera', 'Ultra-portable business laptop', 'High-speed wireless printer', 'Premium OLED smart TV', 'Versatile 2-in-1 tablet'])
                ->rules(['nullable', 'string', 'max:1000'])
                ->fillRecordUsing(function ($record, $state) {
                    return $record;
                }),

            ImportColumn::make('sku')
                ->label('SKU')
                ->exampleHeader('SKU')
                ->examples(['IPH15PRO-128GB', 'AM270-BLK-10', 'MBP16-M3-512', 'PS5-STD', 'UB22-WHT-9', 'EOSR5-BODY', 'XPS13-I7-16', 'LJP-M404N', 'OLED55-C3', 'SP9-I5-256'])
                ->rules(['nullable', 'string', 'max:100'])
                ->fillRecordUsing(function ($record, $state) {
                    // Prevent assigning SKU to Product model (SKU belongs to Variation only)
                    return $record; // no-op
                }),

            ImportColumn::make('price')
                ->label('Price')
                ->exampleHeader('Price')
                ->examples(['999.99', '120.00', '2499.00', '499.99', '180.00', '3899.00', '1299.00', '199.99', '1299.99', '1099.00'])
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0'])
                ->fillRecordUsing(function ($record, $state) {
                    return $record;
                }),

            ImportColumn::make('sale_price')
                ->label('Sale Price')
                ->exampleHeader('Sale Price')
                ->examples(['899.99', '99.99', '2199.00', '399.99', '150.00', '3499.00', '1099.00', '159.99', '999.99', '899.00'])
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0'])
                ->fillRecordUsing(function ($record, $state) {
                    return $record;
                }),

            ImportColumn::make('pct_code')
                ->label('PCT Code')
                ->exampleHeader('PCT Code')
                ->examples(['1100.1010', '1234.5678', '8765.4321', '1122.3344', '5566.7788', '9988.7766', '4433.2211', '6655.4433', '2211.4455', '8877.6655'])
                ->rules(['nullable', 'string', 'max:9'])
                ->fillRecordUsing(function ($record, $state) {
                    return $record;
                }),

            // Simplified unit column (no relationship) - only captures free-text unit for single-variation products.
            ImportColumn::make('unit')
                ->label('Unit')
                ->exampleHeader('Unit')
                ->examples(['piece', 'pair', 'unit', 'console', 'pair', 'camera', 'laptop', 'printer', 'tv', 'tablet'])
                ->rules(['nullable', 'string', 'max:50'])
                ->fillRecordUsing(function ($record, $state) {
                    return $record;
                }),

        ];

        for ($i = 1; $i <= 5; $i++) {
            $columns[] = ImportColumn::make("attribute_{$i}_name")
                ->label("Attribute {$i} Name")
                ->exampleHeader("Attribute {$i} Name")
                ->examples($i === 1 ? ['Color', 'Size', 'Storage', 'Color', 'Size', 'Lens', 'RAM', 'Type', 'Size', 'Storage'] :
                    ($i === 2 ? ['Size', 'Color', 'RAM', 'Size', 'Color', 'Mount', 'Storage', 'Color', 'Type', 'RAM'] :
                        ($i === 3 ? ['Storage', 'Material', 'Color', 'Storage', 'Material', 'Filter', 'Color', 'Size', 'Color', 'Color'] :
                            ($i === 4 ? ['Color', 'Size', 'Material', 'Color', 'Color', 'Color', 'Material', 'Material', 'Material', 'Material'] :
                                ($i === 5 ? ['Material', 'Color', 'Size', 'Material', 'Material', 'Material', 'Size', 'Size', 'Size', 'Size'] : [])))))
                ->rules(['nullable', 'string', 'max:100'])
                ->fillRecordUsing(function ($record, $state) {
                    return $record;
                });
            $columns[] = ImportColumn::make("attribute_{$i}_values")
                ->label("Attribute {$i} Values")
                ->exampleHeader("Attribute {$i} Values")
                ->examples($i === 1 ? ['Red|Blue|Green', 'S|M|L|XL', '128GB|256GB|512GB', 'Black|White|Silver', '8|9|10|11', '24-70mm|70-200mm', '8GB|16GB|32GB', 'Inkjet|Laser', '55"|65"|75"', '128GB|256GB|512GB'] :
                    ($i === 2 ? ['S|M|L|XL', 'Red|Blue|Black', '8GB|16GB|32GB', 'S|M|L|XL', 'Red|Blue|White', 'Standard|Gimbal', '256GB|512GB|1TB', 'Black|White|Silver', 'OLED|LED|QLED', '8GB|16GB|32GB'] :
                        ($i === 3 ? ['128GB|256GB|512GB', 'Leather|Canvas|Mesh', 'Red|Blue|Green', '128GB|256GB|512GB', 'Leather|Mesh|Knit', 'UV|Polarized|Clear', 'Red|Blue|Black', 'S|M|L|XL', 'Red|Blue|Silver', 'Red|Blue|Black'] :
                            ($i === 4 ? ['Red|Blue|Black', 'S|M|L|XL', 'Leather|Canvas', 'Red|Blue|Silver', 'Red|Blue|White', 'Red|Blue|Black', 'Leather|Mesh', 'Leather|Canvas', 'Leather|Mesh', 'Leather|Canvas'] :
                                ($i === 5 ? ['Leather|Canvas|Mesh', 'Red|Blue|Black', 'S|M|L|XL', 'Leather|Canvas', 'Leather|Mesh', 'Leather|Canvas', 'S|M|L|XL', 'S|M|L|XL', 'S|M|L|XL', 'S|M|L|XL'] : [])))))
                ->rules(['nullable', 'string', 'max:500'])
                ->fillRecordUsing(function ($record, $state) {
                    return $record;
                });
        }

        return $columns;
    }

    public function resolveRecord(): Product
    {
        $storeId = $this->options['store_id'] ?? null;

        // Validate required context and fields to avoid undefined array key errors
        $name = trim((string) ($this->data['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'The Name column is required for each row.',
            ]);
        }

        if (blank($storeId)) {
            throw ValidationException::withMessages([
                'store' => 'Cannot resolve store for this import job. Please start the import from within a store context.',
            ]);
        }

        // Manually resolve brand and category by querying the database
        $brandId = null;
        $categoryId = null;

        // Get brand name from CSV data and find ID from database
        $brandName = $this->data['brand'] ?? null;
        if (! blank($brandName)) {
            $brand = Brand::withTrashed()
                ->where('store_id', $storeId)
                ->where('name', trim($brandName))
                ->first();

            if ($brand) {
                if ($brand->trashed()) {
                    $brand->restore();
                }
                $brandId = $brand->id;
            } else {
                // Create new brand if it doesn't exist
                $brand = Brand::create([
                    'store_id' => $storeId,
                    'name' => trim($brandName),
                ]);
                $brandId = $brand->id;
            }
        }

        // Get category name from CSV data and find ID from database
        $categoryName = $this->data['category'] ?? null;
        if (! blank($categoryName)) {
            $category = Category::withTrashed()
                ->where('store_id', $storeId)
                ->where('name', trim($categoryName))
                ->first();

            if ($category) {
                if ($category->trashed()) {
                    $category->restore();
                }
                $categoryId = $category->id;
            } else {
                // Create new category if it doesn't exist
                $category = Category::create([
                    'store_id' => $storeId,
                    'name' => trim($categoryName),
                ]);
                $categoryId = $category->id;
            }
        }

        // Use name, store_id, brand_id, and category_id to uniquely identify products
        // This allows products with the same name but different brands and/or categories
        $searchCriteria = [
            'name' => $name,
            'store_id' => $storeId,
        ];

        // Only add brand_id to search criteria if it's provided
        // This maintains backward compatibility for products without brands
        if ($brandId !== null) {
            $searchCriteria['brand_id'] = $brandId;
        }

        // Only add category_id to search criteria if it's provided
        // This maintains backward compatibility for products without categories
        if ($categoryId !== null) {
            $searchCriteria['category_id'] = $categoryId;
        }

        $record = Product::query()->firstOrNew($searchCriteria);

        $record->store_id = $storeId;

        return $record;
    }

    protected function afterSave(): void
    {
        /** @var Product $product */
        $product = $this->record;
        if (! $product) {
            return;
        }
        $storeId = $product->store_id;

        // Extract variation-specific numeric fields from import row (product itself doesn't store these)
        $rowPrice = $this->data['price'] ?? null;
        $rowSalePrice = $this->data['sale_price'] ?? null;
        $rowPctCode = trim((string) ($this->data['pct_code'] ?? ''));

        // Parse attribute sets from data
        $attributeSets = $this->parseAttributeSets($storeId);

        DB::transaction(function () use ($product, $attributeSets, $storeId, $rowPrice, $rowSalePrice, $rowPctCode) {
            $unitName = trim((string) ($this->data['unit'] ?? ''));
            $unitId = null;

            if (count($attributeSets) > 0) {
                // Build / sync attributes and values directly on product_attributes.values
                foreach ($attributeSets as $set) {
                    // Check for existing attribute (including soft-deleted ones)
                    $attributeModel = \SmartTill\Core\Models\Attribute::withTrashed()
                        ->where('store_id', $storeId)
                        ->where('name', $set['name'])
                        ->first();

                    if ($attributeModel) {
                        // If soft-deleted, restore it
                        if ($attributeModel->trashed()) {
                            $attributeModel->restore();
                        }
                    } else {
                        // Create new attribute if it doesn't exist
                        $attributeModel = \SmartTill\Core\Models\Attribute::create([
                            'store_id' => $storeId,
                            'name' => $set['name'],
                        ]);
                    }

                    $productAttribute = \SmartTill\Core\Models\ProductAttribute::firstOrCreate([
                        'product_id' => $product->id,
                        'attribute_id' => $attributeModel->id,
                    ]);

                    $productAttribute->update([
                        'values' => collect($set['values'])
                            ->pluck('value')
                            ->map(fn ($value) => trim((string) $value))
                            ->filter()
                            ->unique()
                            ->values()
                            ->all(),
                    ]);
                }

                $attributeValueGroups = collect($attributeSets)
                    ->map(fn (array $set) => collect($set['values'])
                        ->pluck('value')
                        ->map(fn ($value) => trim((string) $value))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all())
                    ->filter(fn (array $values) => ! empty($values))
                    ->values()
                    ->all();

                if ($attributeValueGroups) {
                    // Performance optimization: Limit combinations to prevent memory issues
                    $totalCombinations = 1;
                    foreach ($attributeValueGroups as $group) {
                        $totalCombinations *= count($group);
                    }

                    if ($totalCombinations > 1000) {
                        throw ValidationException::withMessages([
                            'attributes' => 'Too many attribute combinations ('.$totalCombinations.'). Maximum 1000 variations allowed per product.',
                        ]);
                    }

                    // Existing variation descriptions to avoid duplicates.
                    $existingMap = [];
                    $existingVariations = $product->variations()->get();
                    foreach ($existingVariations as $var) {
                        $key = trim((string) $var->description);
                        if ($key !== '') {
                            $existingMap[$key] = $var; // store variation instance for updates
                        }
                    }

                    // Cartesian product of attribute value groups.
                    $combinations = [[]];
                    foreach ($attributeValueGroups as $group) {
                        $next = [];
                        foreach ($combinations as $partial) {
                            foreach ($group as $value) {
                                $tmp = $partial;
                                $tmp[] = $value;
                                $next[] = $tmp;
                            }
                        }
                        $combinations = $next;
                    }

                    $incomingSku = trim((string) ($this->data['sku'] ?? ''));

                    foreach ($combinations as $combo) {
                        // Build parts preserving original attribute order.
                        $parts = [];
                        foreach ($combo as $index => $value) {
                            $attributeName = $attributeSets[$index]['name'] ?? 'Attribute';
                            $parts[] = $attributeName.': '.$value;
                        }

                        $description = $parts ? trim($product->name.' - '.implode(', ', $parts)) : $product->name;
                        $key = $description;

                        if (isset($existingMap[$key])) {
                            // Update existing variation (prices, percentages, unit, sku, pct_code) but leave description unchanged
                            $variation = $existingMap[$key];
                            $updates = [];
                            if ($rowPrice !== null && $rowPrice !== '') {
                                $updates['price'] = $rowPrice;
                            }
                            if ($rowSalePrice !== null && $rowSalePrice !== '') {
                                $updates['sale_price'] = $rowSalePrice;
                            }
                            if ($unitId !== null && $variation->unit_id !== $unitId) {
                                $updates['unit_id'] = $unitId;
                            }
                            if ($incomingSku !== '' && $variation->sku !== $incomingSku) {
                                $updates['sku'] = $incomingSku;
                            }
                            if ($rowPctCode !== '' && $variation->pct_code !== $rowPctCode) {
                                $updates['pct_code'] = $rowPctCode;
                            }
                            if (! empty($updates)) {
                                $variation->update($updates);
                            }

                            continue; // skip creation
                        }

                        try {
                            Variation::create([
                                'product_id' => $product->id,
                                'description' => $description,
                                'sku' => $incomingSku !== '' ? $incomingSku : null,
                                'price' => $rowPrice,
                                'sale_price' => $rowSalePrice,
                                'pct_code' => $rowPctCode !== '' ? $rowPctCode : null,
                                'unit_id' => $unitId,
                            ]);
                        } catch (\Exception $e) {
                            throw ValidationException::withMessages([
                                'variation' => 'Failed to create variation: '.$e->getMessage(),
                            ]);
                        }
                    }
                }

                $product->has_variations = true;
                $product->save();

                return; // done with attribute path
            }

            // Ensure single variation exists (create or update first one) – now also handles unit & sku and uses row values
            $incomingSku = trim((string) ($this->data['sku'] ?? ''));

            $existing = $product->variations()->first();
            $singleDescription = $product->name ?: 'Untitled Product';
            if ($existing) {
                $updateData = [
                    'description' => $singleDescription,
                    'price' => $rowPrice,
                    'sale_price' => $rowSalePrice,
                    'unit_id' => $unitId,
                ];
                if ($incomingSku !== '') {
                    $updateData['sku'] = $incomingSku;
                }
                if ($rowPctCode !== '') {
                    $updateData['pct_code'] = $rowPctCode;
                }
                $existing->update($updateData);
            } else {
                try {
                    $variation = Variation::create([
                        'product_id' => $product->id,
                        'description' => $singleDescription,
                        'sku' => $incomingSku !== '' ? $incomingSku : null,
                        'price' => $rowPrice,
                        'sale_price' => $rowSalePrice,
                        'pct_code' => $rowPctCode !== '' ? $rowPctCode : null,
                        'unit_id' => $unitId,
                    ]);
                } catch (\Exception $e) {
                    throw ValidationException::withMessages([
                        'variation' => 'Failed to create single variation: '.$e->getMessage(),
                    ]);
                }
            }
            $product->has_variations = false;
            $product->save();
        });
    }

    private function parseAttributeSets(int $storeId): array
    {
        $sets = [];
        for ($i = 1; $i <= 5; $i++) {
            try {
                $name = trim((string) ($this->data["attribute_{$i}_name"] ?? ''));
                $valuesRaw = (string) ($this->data["attribute_{$i}_values"] ?? '');

                if ($name === '' || $valuesRaw === '') {
                    continue;
                }

                // Validate attribute name length
                if (strlen($name) > 100) {
                    throw ValidationException::withMessages([
                        "attribute_{$i}_name" => 'Attribute name too long (max 100 characters)',
                    ]);
                }

                $valueTokens = array_values(array_filter(array_map(fn ($v) => trim($v), preg_split('/\|/', $valuesRaw))));

                // Validate number of values
                if (count($valueTokens) > 20) {
                    throw ValidationException::withMessages([
                        "attribute_{$i}_values" => 'Too many attribute values (max 20 per attribute)',
                    ]);
                }

                $valueRows = [];
                foreach ($valueTokens as $idx => $val) {
                    // Validate individual value length
                    if (strlen($val) > 100) {
                        throw ValidationException::withMessages([
                            "attribute_{$i}_values" => 'Attribute value too long (max 100 characters): '.$val,
                        ]);
                    }

                    $valueRows[] = [
                        'value' => $val,
                    ];
                }

                if ($valueRows) {
                    $sets[] = [
                        'name' => $name,
                        'values' => $valueRows,
                    ];
                }
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw ValidationException::withMessages([
                    "attribute_{$i}" => "Error parsing attribute {$i}: ".$e->getMessage(),
                ]);
            }
        }

        return $sets;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
