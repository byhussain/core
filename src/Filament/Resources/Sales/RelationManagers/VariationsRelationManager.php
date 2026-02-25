<?php

namespace SmartTill\Core\Filament\Resources\Sales\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use SmartTill\Core\Filament\Resources\Variations\VariationResource;
use SmartTill\Core\Models\Stock;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('pivot.description')
                    ->label('Description')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pct_code')
                    ->label('PCT Code')
                    ->placeholder('No PCT Code')
                    ->visible(fn () => $this->getOwnerRecord()->use_fbr)
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pivot.quantity')
                    ->label('Qty')
                    ->numeric(),
                TextColumn::make('pivot.unit_price')
                    ->label('Unit Price')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->getStateUsing(function ($record) {
                        $unitPrice = $record->pivot->unit_price ?? 0;
                        // If FBR is enabled, show price excluding tax
                        if ($this->getOwnerRecord()->use_fbr) {
                            $tax = $record->pivot->tax ?? 0;

                            return $unitPrice - $tax;
                        }

                        return $unitPrice;
                    }),
                TextColumn::make('pivot.discount')
                    ->label('Discount')
                    ->getStateUsing(function ($record) {
                        $discountType = $record->pivot->discount_type ?? 'flat';
                        $discountPercentage = (float) ($record->pivot->discount_percentage ?? 0);
                        $discountAmount = (float) ($record->pivot->discount ?? 0);

                        if ($discountType === 'percentage' && $discountPercentage > 0) {
                            $formatted = rtrim(rtrim(number_format($discountPercentage, 6, '.', ''), '0'), '.') ?: '0';

                            return $formatted.'%';
                        }

                        return $discountAmount != 0 ? number_format($discountAmount, 2, '.', ',') : '-';
                    })
                    ->html(),
                TextColumn::make('pivot.tax')
                    ->label('Tax')
                    ->visible(fn () => Filament::getTenant()?->tax_enabled ?? false)
                    ->getStateUsing(function ($record) {
                        $tax = $record->pivot->tax ?? 0;
                        $qty = $record->pivot->quantity ?? 1;
                        $totalTax = $tax * $qty;
                        $taxPercentage = 0;
                        if (! empty($record->pivot->stock_id)) {
                            $taxPercentage = (float) (Stock::query()
                                ->whereKey($record->pivot->stock_id)
                                ->value('tax_percentage') ?? 0);
                        }

                        if ($totalTax <= 0) {
                            return '—';
                        }

                        $formatted = rtrim(rtrim(number_format($taxPercentage, 6, '.', ''), '0'), '.') ?: '0';

                        return $formatted.'% '.$totalTax;
                    })
                    ->html(),
                TextColumn::make('pivot.total')
                    ->label('Total')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->weight('bold'),
                TextColumn::make('pivot.supplier_price')
                    ->label('Supplier Price')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pivot.supplier_total')
                    ->label('Supplier Total')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->getStateUsing(function ($record) {
                        $pivot = $record->pivot;
                        $total = $pivot->total ?? 0;
                        $supplierTotal = $pivot->supplier_total ?? 0;

                        return $total - $supplierTotal;
                    })
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('profit_margin')
                    ->label('Profit Margin')
                    ->getStateUsing(function ($record) {
                        $pivot = $record->pivot;
                        $total = $pivot->total ?? 0;
                        $supplierTotal = $pivot->supplier_total ?? 0;
                        $profit = $total - $supplierTotal;

                        if ($total == 0) {
                            return 0;
                        }

                        return ($profit / $total) * 100;
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 4).'%')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => VariationResource::getUrl('view', [
                        'product' => $record->product,
                        'record' => $record,
                    ]))
                    ->hiddenLabel()
                    ->tooltip('View Variation Details'),
            ])
            ->paginated(false);
    }
}
