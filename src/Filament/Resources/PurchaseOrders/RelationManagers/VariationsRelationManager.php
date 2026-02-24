<?php

namespace SmartTill\Core\Filament\Resources\PurchaseOrders\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use SmartTill\Core\Filament\Resources\Variations\VariationResource;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    protected static ?string $title = 'Variations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pivot.requested_quantity')
                    ->label('Requested Qty')
                    ->formatStateUsing(function ($state, $record): string {
                        $value = is_numeric($state)
                            ? rtrim(rtrim(number_format((float) $state, 6, '.', ''), '0'), '.')
                            : '0';
                        $symbol = $record->pivot->requestedUnit?->symbol
                            ?? $record->pivot->receivedUnit?->symbol
                            ?? $record->unit?->symbol;

                        return $symbol ? "{$value} {$symbol}" : $value;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('purchase_order_variation.requested_quantity', $direction);
                    }),
                TextColumn::make('pivot.requested_unit_price')
                    ->label('Requested Unit Price')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('purchase_order_variation.requested_unit_price', $direction);
                    }),
                TextColumn::make('requested_tax_summary')
                    ->label('Tax')
                    ->getStateUsing(function ($record): ?string {
                        $store = Filament::getTenant();
                        if (! $store?->isTaxEnabled()) {
                            return null;
                        }

                        $percentage = (float) ($record->pivot->requested_tax_percentage ?? 0);
                        $amount = (float) ($record->pivot->requested_tax_amount ?? 0);
                        $currencyCode = $store?->currency->code ?? 'PKR';

                        if ($percentage > 0) {
                            $percentageFormatted = rtrim(rtrim(number_format($percentage, 6, '.', ''), '0'), '.') ?: '0';

                            return $percentageFormatted.'% / '.Number::currency($amount, $currencyCode);
                        }

                        return null;
                    })
                    ->visible(fn () => Filament::getTenant()?->isTaxEnabled() ?? false),
                TextColumn::make('requested_supplier_summary')
                    ->label('Supplier Price')
                    ->getStateUsing(function ($record): string {
                        $currencyCode = Filament::getTenant()?->currency->code ?? 'PKR';
                        $inputIsPercent = $record->pivot->requested_supplier_is_percentage ?? null;
                        $percentage = rtrim(rtrim(number_format((float) ($record->pivot->requested_supplier_percentage ?? 0), 6, '.', ''), '0'), '.') ?: '0';
                        $price = (float) ($record->pivot->requested_supplier_price ?? 0);
                        $percentageLabel = $inputIsPercent === false ? '—' : $percentage;

                        return $percentageLabel.'% / '.Number::currency($price, $currencyCode);
                    }),
                TextColumn::make('received_quantity')
                    ->label('Received Qty')
                    ->getStateUsing(function ($record) {
                        $quantity = $record->pivot->received_quantity ?? null;

                        return is_numeric($quantity) && (float) $quantity > 0 ? $quantity : null;
                    })
                    ->formatStateUsing(function ($state, $record): ?string {
                        if (! is_numeric($state) || (float) $state <= 0) {
                            return null;
                        }

                        $value = rtrim(rtrim(number_format((float) $state, 6, '.', ''), '0'), '.');
                        $symbol = $record->pivot->receivedUnit?->symbol
                            ?? $record->pivot->requestedUnit?->symbol
                            ?? $record->unit?->symbol;

                        return $symbol ? "{$value} {$symbol}" : $value;
                    })
                    ->color(fn ($state, $record) => ($record->pivot->received_quantity ?? 0) > 0 ? 'success' : 'gray'),
                TextColumn::make('received_unit_price')
                    ->label('Received Unit Price')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->color(fn ($state, $record) => ($record->pivot->received_unit_price ?? 0) > 0 ? 'success' : 'gray'),
                TextColumn::make('received_tax_summary')
                    ->label('Received Tax')
                    ->getStateUsing(function ($record): ?string {
                        $store = Filament::getTenant();
                        if (! $store?->isTaxEnabled()) {
                            return null;
                        }

                        $percentage = (float) ($record->pivot->received_tax_percentage ?? 0);
                        $amount = (float) ($record->pivot->received_tax_amount ?? 0);
                        $currencyCode = $store?->currency->code ?? 'PKR';

                        if ($percentage > 0 || $amount > 0) {
                            $percentageFormatted = rtrim(rtrim(number_format($percentage, 6, '.', ''), '0'), '.') ?: '0';

                            return $percentageFormatted.'% / '.Number::currency($amount, $currencyCode);
                        }

                        return null;
                    })
                    ->visible(fn () => Filament::getTenant()?->isTaxEnabled() ?? false)
                    ->color(fn ($state, $record) => (float) ($record->pivot->received_tax_amount ?? 0) > 0 ? 'success' : 'gray'),
                TextColumn::make('received_supplier_summary')
                    ->label('Received Supplier Price')
                    ->getStateUsing(function ($record): ?string {
                        $receivedQuantity = $record->pivot->received_quantity ?? null;
                        if (! is_numeric($receivedQuantity) || (float) $receivedQuantity <= 0) {
                            return null;
                        }

                        $currencyCode = Filament::getTenant()?->currency->code ?? 'PKR';
                        $inputIsPercent = $record->pivot->received_supplier_is_percentage ?? null;
                        $supplierPercentage = (float) ($record->pivot->received_supplier_percentage ?? 0);
                        $supplierPrice = (float) ($record->pivot->received_supplier_price ?? 0);

                        if ($supplierPercentage <= 0 && $supplierPrice <= 0) {
                            return null;
                        }

                        $percentage = $supplierPercentage > 0
                            ? rtrim(rtrim(number_format($supplierPercentage, 6, '.', ''), '0'), '.')
                            : '0';
                        $percentageLabel = $inputIsPercent === false ? '—' : $percentage;

                        return $percentageLabel.'% / '.Number::currency($supplierPrice, $currencyCode);
                    })
                    ->color(fn ($state, $record) => (float) ($record->pivot->received_supplier_price ?? 0) > 0 ? 'success' : 'gray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => VariationResource::getUrl('view', ['record' => $record]))
                    ->label('View')
                    ->tooltip('View Variation Details'),
            ]);
    }
}
