<?php

namespace SmartTill\Core\Filament\Resources\Brands\Schemas;

use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BrandInfolist
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
                                Section::make('Brand Information')
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Brand Name')
                                            ->placeholder('No name provided')
                                            ->weight('semibold')
                                            ->size('lg'),
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->placeholder('No description provided')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Brand Portfolio')
                                    ->schema([
                                        TextEntry::make('products_count')
                                            ->label('Total Products')
                                            ->getStateUsing(fn ($record) => $record?->products()->count() ?? 0)
                                            ->badge()
                                            ->color('info'),
                                        TextEntry::make('variations_count')
                                            ->label('Total Variations')
                                            ->getStateUsing(fn ($record) => $record?->products()->withCount('variations')->get()->sum('variations_count') ?? 0)
                                            ->badge()
                                            ->color('info'),
                                        TextEntry::make('active_products_count')
                                            ->label('Active Products')
                                            ->getStateUsing(fn ($record) => $record?->products()->where('status', 'active')->count() ?? 0)
                                            ->badge()
                                            ->color('success'),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),

                                Section::make('Brand Performance')
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
                                Section::make('Brand Status')
                                    ->schema([
                                        TextEntry::make('status')
                                            ->label('Current Status')
                                            ->badge()
                                            ->hintIcon(fn ($r) => $r?->status?->getIcon())
                                            ->helperText(fn ($r) => $r?->status?->getDescription())
                                            ->size('lg'),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Store Information')
                                    ->schema([
                                        TextEntry::make('store.name')
                                            ->label('Store')
                                            ->placeholder('No store assigned')
                                            ->weight('medium'),
                                    ])
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
                                        TextEntry::make('deleted_at')
                                            ->label('Deleted')
                                            ->dateTime()
                                            ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                            ->hidden(fn ($r) => ! $r?->deleted_at)
                                            ->placeholder('—'),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),

                                Section::make('User Activity')
                                    ->schema([
                                        TextEntry::make('activity.created_by')
                                            ->label('Created By')
                                            ->getStateUsing(fn ($record) => $record->activity?->creator?->name ?? 'Unknown')
                                            ->placeholder('Unknown')
                                            ->icon(Heroicon::OutlinedUserPlus)
                                            ->visible(fn ($record) => $record->activity?->creator),
                                        TextEntry::make('activity.updated_by')
                                            ->label('Last Updated By')
                                            ->getStateUsing(fn ($record) => $record->activity?->updater?->name ?? 'Not updated yet')
                                            ->placeholder('Not updated yet')
                                            ->icon(Heroicon::OutlinedPencilSquare)
                                            ->visible(fn ($record) => $record->activity?->updater),
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
