<?php

namespace SmartTill\Core\Filament\Resources\Variations\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class VariationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Variation Details')
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->disabled(),
                        TextInput::make('sku')
                            ->label('SKU'),
                        Select::make('unit_id')
                            ->label('Unit')
                            ->relationship(
                                name: 'unit',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->forStoreOrGlobal(Filament::getTenant()?->getKey()),
                            )
                            ->placeholder('Select unit')
                            ->searchable()
                            ->preload(),
                        TextInput::make('pct_code')
                            ->label('PCT Code')
                            ->maxLength(9)
                            ->helperText('Pakistan Custom Tariff Code for FBR compliance')
                            ->placeholder('e.g., 1234.5678')
                            ->visible(fn () => Filament::getTenant()?->isTaxEnabled() ?? false),
                        Fieldset::make('Price & Sale Information')
                            ->columns()
                            ->schema([
                                TextInput::make('price')
                                    ->label('Base Price')
                                    ->numeric()
                                    ->inputMode('decimal')
                                    ->step(function ($record) {
                                        $store = Filament::getTenant();
                                        $currency = $record?->product?->store?->currency ?? $store?->currency;
                                        $decimalPlaces = $currency->decimal_places ?? 2;

                                        if ($decimalPlaces === 0) {
                                            return '1';
                                        }

                                        return '0.'.str_repeat('0', $decimalPlaces - 1).'1';
                                    })
                                    ->prefix(fn ($record) => $record?->product?->store?->currency?->code ?? Filament::getTenant()?->currency->code ?? 'PKR')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (mixed $state, $set, $get, $record): void {
                                        $store = Filament::getTenant();
                                        $currency = $record?->product?->store?->currency ?? $store?->currency;
                                        $decimalPlaces = $currency->decimal_places ?? 2;

                                        $price = is_numeric($state) ? round((float) $state, $decimalPlaces) : null;
                                        if ($price !== null && (float) $state !== $price) {
                                            $set('price', $price);
                                        }

                                        if ($price === null || $price <= 0) {
                                            $set('sale_price', null);
                                            $set('sale_percentage', null);

                                            return;
                                        }

                                        // Sale
                                        $salePerc = $get('sale_percentage');
                                        if (is_numeric($salePerc)) {
                                            $salePrice = $price * (1 - ((float) $salePerc) / 100);
                                            $set('sale_price', round($salePrice, $decimalPlaces));
                                        } else {
                                            $salePrice = $get('sale_price');
                                            if (is_numeric($salePrice)) {
                                                $percentage = (($price - $salePrice) / $price) * 100;
                                                $set('sale_percentage', round($percentage, 6));
                                            }
                                        }
                                    }),
                                TextInput::make('sale_price')
                                    ->prefix(fn ($record) => $record?->product?->store?->currency?->code ?? Filament::getTenant()?->currency->code ?? 'PKR')
                                    ->numeric()
                                    ->inputMode('decimal')
                                    ->step(function ($record) {
                                        $store = Filament::getTenant();
                                        $currency = $record?->product?->store?->currency ?? $store?->currency;
                                        $decimalPlaces = $currency->decimal_places ?? 2;

                                        if ($decimalPlaces === 0) {
                                            return '1';
                                        }

                                        return '0.'.str_repeat('0', $decimalPlaces - 1).'1';
                                    })
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (mixed $state, $set, $get, $record): void {
                                        $store = Filament::getTenant();
                                        $currency = $record?->product?->store?->currency ?? $store?->currency;
                                        $decimalPlaces = $currency->decimal_places ?? 2;

                                        $salePrice = is_numeric($state) ? round((float) $state, $decimalPlaces) : null;
                                        if ($salePrice !== null && (float) $state !== $salePrice) {
                                            $set('sale_price', $salePrice);
                                        }
                                        $price = $get('price');
                                        if (! is_numeric($price) || $salePrice === null) {
                                            return;
                                        }
                                        $percentage = (($price - $salePrice) / $price) * 100;
                                        $set('sale_percentage', round($percentage, 6));
                                    }),
                                TextInput::make('sale_percentage')
                                    ->prefix('%')
                                    ->numeric()
                                    ->inputMode('decimal')
                                    ->step('0.000001')
                                    ->minValue(0)
                                    ->maxValue(999.999999)
                                    ->rule('regex:/^\\d{1,3}(\\.\\d{1,6})?$/')
                                    ->validationMessages([
                                        'max' => 'Percentage cannot exceed 999.999999%.',
                                        'min' => 'Percentage cannot be negative.',
                                        'regex' => 'Please enter a valid percentage (e.g., 10.5 or 99.999999).',
                                    ])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (mixed $state, $set, $get, $record): void {
                                        if (! is_numeric($state)) {
                                            return;
                                        }
                                        $salePerc = round((float) $state, 6);
                                        // Clamp to valid range
                                        $salePerc = max(0, min(999.999999, $salePerc));
                                        if ((float) $state !== $salePerc) {
                                            $set('sale_percentage', $salePerc);
                                        }
                                        $price = $get('price');
                                        if (! is_numeric($price) || $salePerc === null) {
                                            return;
                                        }

                                        $store = Filament::getTenant();
                                        $currency = $record?->product?->store?->currency ?? $store?->currency;
                                        $decimalPlaces = $currency->decimal_places ?? 2;

                                        $salePrice = $price * (1 - $salePerc / 100);
                                        $set('sale_price', round($salePrice, $decimalPlaces));
                                    }),
                            ])
                            ->columnSpanFull(),

                    ])
                    ->columns()
                    ->columnSpanFull(),

                Section::make('Variation Images')
                    ->description('Upload one or more images for this variation')
                    ->schema([
                        FileUpload::make('image_paths')
                            ->label('Images')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->panelLayout('grid')
                            ->itemPanelAspectRatio('1:1')
                            ->imagePreviewHeight('160')
                            ->uploadButtonPosition('right bottom')
                            ->removeUploadedFileButtonPosition('left bottom')
                            ->maxParallelUploads(2)
                            ->helperText('Upload multiple images, drag to reorder, and click a thumbnail to preview.')
                            ->extraAttributes(['class' => 'fi-upload-grid-5'])
                            ->getUploadedFileUsing(function (FileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                                $disk = $component->getDisk();

                                if (! $disk->exists($file)) {
                                    return null;
                                }

                                return [
                                    'name' => Arr::get(Arr::wrap($storedFileNames), $file, basename($file)),
                                    'size' => $disk->size($file),
                                    'type' => $disk->mimeType($file),
                                    'url' => '/storage/'.ltrim($file, '/'),
                                ];
                            })
                            ->disk('public')
                            ->directory('variations/images')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
