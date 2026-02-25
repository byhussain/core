<?php

namespace SmartTill\Core\Filament\Resources\Variations\Schemas;

use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VariationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        // LEFT / MAIN (2/3 width)
                        Grid::make()
                            ->schema([
                                Section::make('Product Information')
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Product')
                                            ->placeholder('-')
                                            ->weight('semibold')
                                            ->size('lg'),
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->placeholder('No description provided')
                                            ->columnSpanFull(),
                                        TextEntry::make('sku')
                                            ->label('SKU')
                                            ->placeholder('—')
                                            ->copyable()
                                            ->weight('medium'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Inventory Status')
                                    ->schema([
                                        TextEntry::make('stock')
                                            ->label('Current Stock')
                                            ->badge()
                                            ->color(fn ($state) => $state <= 0 ? 'danger' : ($state < 10 ? 'warning' : 'success'))
                                            ->suffix(fn ($record) => $record->unit?->symbol ? " {$record->unit?->symbol}" : '')
                                            ->size('lg'),
                                        TextEntry::make('unit.name')
                                            ->label('Unit')
                                            ->placeholder('—'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Cost Structure')
                                    ->schema([
                                        TextEntry::make('price')
                                            ->label('Base Price')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->size('lg'),
                                        TextEntry::make('sale_price')
                                            ->label('Sale Price')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->size('lg'),
                                        TextEntry::make('pct_code')
                                            ->label('PCT Code')
                                            ->placeholder('No PCT Code')
                                            ->badge()
                                            ->color('info')
                                            ->copyable()
                                            ->helperText('Pakistan Customs Tariff Code for FBR')
                                            ->visible(fn () => Filament::getTenant()?->tax_enabled ?? false),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Variation Performance')
                                    ->schema([
                                        TextEntry::make('total_revenue')
                                            ->label('Total Revenue')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->color('success')
                                            ->size('lg'),
                                        TextEntry::make('total_cost')
                                            ->label('Total Cost')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->color('danger'),
                                        TextEntry::make('total_profit')
                                            ->label('Net Profit/Loss')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                                            ->size('lg'),
                                        TextEntry::make('profit_margin')
                                            ->label('Profit Margin')
                                            ->formatStateUsing(fn ($state) => number_format($state, 4).'%')
                                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                                            ->size('lg'),
                                        TextEntry::make('total_quantity_sold')
                                            ->label('Units Sold')
                                            ->numeric()
                                            ->badge()
                                            ->color('info'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        // RIGHT SIDEBAR (1/3 width)
                        Grid::make()
                            ->schema([
                                Section::make('Product Details')
                                    ->schema([
                                        TextEntry::make('product.status')
                                            ->label('Product Status')
                                            ->badge()
                                            ->hintIcon(fn ($r) => $r?->product?->status?->getIcon())
                                            ->helperText(fn ($r) => $r?->product?->status?->getDescription()),
                                        TextEntry::make('product.brand.name')
                                            ->label('Brand')
                                            ->placeholder('No brand assigned'),
                                        TextEntry::make('product.category.name')
                                            ->label('Category')
                                            ->placeholder('No category assigned'),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),

                                Section::make('Record Information')
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Created')
                                            ->since()
                                            ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                            ->placeholder('—'),
                                        TextEntry::make('updated_at')
                                            ->label('Last Updated')
                                            ->since()
                                            ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                            ->placeholder('—'),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
