<?php

namespace SmartTill\Core\Filament\Resources\Sales\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use SmartTill\Core\Filament\Resources\Variations\VariationResource;
use SmartTill\Core\Models\Sale;
use SmartTill\Core\Models\Stock;
use SmartTill\Core\Models\Variation;


class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    public static function buildGroupedQueryForSale(Sale $sale): Builder
    {
        $groupedSaleLines = DB::table('sale_variation')
            ->selectRaw('variation_id')
            ->selectRaw('description')
            ->selectRaw('unit_price')
            ->selectRaw('discount_type')
            ->selectRaw('discount_percentage')
            ->selectRaw('is_preparable')
            ->selectRaw('MIN(stock_id) as stock_id')
            ->selectRaw('SUM(quantity) as quantity')
            ->selectRaw('SUM(discount) as discount')
            ->selectRaw('SUM(tax * quantity) as tax_total')
            ->selectRaw('SUM(total) as total')
            ->selectRaw('SUM(supplier_total) as supplier_total')
            ->selectRaw('CASE WHEN SUM(quantity) = 0 THEN MIN(supplier_price) ELSE ROUND(SUM(supplier_total) / SUM(quantity)) END as supplier_price')
            ->where('sale_id', $sale->id)
            ->whereNotNull('variation_id')
            ->groupBy([
                'variation_id',
                'description',
                'unit_price',
                'discount_type',
                'discount_percentage',
                'is_preparable',
            ]);

        return Variation::query()
            ->joinSub($groupedSaleLines, 'sale_lines', function ($join): void {
                $join->on('sale_lines.variation_id', '=', 'variations.id');
            })
            ->select('variations.*')
            ->selectRaw('sale_lines.description as sale_line_description')
            ->selectRaw('sale_lines.stock_id as sale_line_stock_id')
            ->selectRaw('sale_lines.quantity as sale_line_quantity')
            ->selectRaw('sale_lines.unit_price as sale_line_unit_price')
            ->selectRaw('sale_lines.discount as sale_line_discount')
            ->selectRaw('sale_lines.discount_type as sale_line_discount_type')
            ->selectRaw('sale_lines.discount_percentage as sale_line_discount_percentage')
            ->selectRaw('sale_lines.tax_total as sale_line_tax_total')
            ->selectRaw('sale_lines.total as sale_line_total')
            ->selectRaw('sale_lines.supplier_price as sale_line_supplier_price')
            ->selectRaw('sale_lines.supplier_total as sale_line_supplier_total')
            ->selectRaw('sale_lines.is_preparable as sale_line_is_preparable');
    }

    protected function getTableQuery(): Builder
    {
        /** @var Sale $sale */
        $sale = $this->getOwnerRecord();

        return static::buildGroupedQueryForSale($sale);
    }

    protected function calculateDisplayedUnitPrice(Variation $record): float
    {
        $unitPrice = $this->getOwnerRecord()->convertStoredAmount($record->sale_line_unit_price ?? 0);

        if (! $this->getOwnerRecord()->use_fbr) {
            return $unitPrice;
        }

        $quantity = (float) ($record->sale_line_quantity ?? 1);
        $taxTotal = $this->getOwnerRecord()->convertStoredAmount($record->sale_line_tax_total ?? 0);
        $taxPerUnit = $quantity !== 0.0 ? ($taxTotal / $quantity) : 0.0;

        return $unitPrice - $taxPerUnit;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('sale_line_description')
                    ->label('Description')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): Builder {
                            return $query
                                ->where('sale_lines.description', 'like', "%{$search}%")
                                ->orWhere('variations.sku', 'like', "%{$search}%");
                        });
                    }),
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
                TextColumn::make('sale_line_quantity')
                    ->label('Qty')
                    ->numeric(),
                TextColumn::make('sale_line_unit_price')
                    ->label('Unit Price')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->getStateUsing(fn (Variation $record): float => $this->calculateDisplayedUnitPrice($record)),
                TextColumn::make('sale_line_discount')
                    ->label('Discount')
                    ->getStateUsing(function ($record) {
                        $discountType = $record->sale_line_discount_type ?? 'flat';
                        $discountPercentage = (float) ($record->sale_line_discount_percentage ?? 0);
                        $discountAmount = $this->getOwnerRecord()->convertStoredAmount($record->sale_line_discount ?? 0);

                        if ($discountType === 'percentage' && $discountPercentage > 0) {
                            $formatted = rtrim(rtrim(number_format($discountPercentage, 6, '.', ''), '0'), '.') ?: '0';

                            return $formatted.'%';
                        }

                        return $discountAmount != 0 ? number_format($discountAmount, 2, '.', ',') : '-';
                    })
                    ->html(),
                TextColumn::make('sale_line_tax_total')
                    ->label('Tax')
                    ->visible(fn () => Filament::getTenant()?->tax_enabled ?? false)
                    ->getStateUsing(function ($record) {
                        $totalTax = $this->getOwnerRecord()->convertStoredAmount($record->sale_line_tax_total ?? 0);
                        $taxPercentage = 0;
                        if (! empty($record->sale_line_stock_id)) {
                            $taxPercentage = (float) (Stock::query()
                                ->whereKey($record->sale_line_stock_id)
                                ->value('tax_percentage') ?? 0);
                        }

                        if ($totalTax <= 0) {
                            return '—';
                        }

                        $formatted = rtrim(rtrim(number_format($taxPercentage, 6, '.', ''), '0'), '.') ?: '0';

                        return $formatted.'% '.$totalTax;
                    })
                    ->html(),
                TextColumn::make('sale_line_total')
                    ->label('Total')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->getStateUsing(fn (Variation $record): float => $this->getOwnerRecord()->convertStoredAmount($record->sale_line_total ?? 0))
                    ->weight('bold'),
                TextColumn::make('sale_line_supplier_price')
                    ->label('Supplier Price')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->getStateUsing(fn (Variation $record): float => $this->getOwnerRecord()->convertStoredAmount($record->sale_line_supplier_price ?? 0))
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sale_line_supplier_total')
                    ->label('Supplier Total')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->getStateUsing(fn (Variation $record): float => $this->getOwnerRecord()->convertStoredAmount($record->sale_line_supplier_total ?? 0))
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->getStateUsing(function ($record) {
                        $total = $this->getOwnerRecord()->convertStoredAmount($record->sale_line_total ?? 0);
                        $supplierTotal = $this->getOwnerRecord()->convertStoredAmount($record->sale_line_supplier_total ?? 0);

                        return $total - $supplierTotal;
                    })
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('profit_margin')
                    ->label('Profit Margin')
                    ->getStateUsing(function ($record) {
                        $total = $this->getOwnerRecord()->convertStoredAmount($record->sale_line_total ?? 0);
                        $supplierTotal = $this->getOwnerRecord()->convertStoredAmount($record->sale_line_supplier_total ?? 0);
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
