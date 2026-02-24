<?php

namespace SmartTill\Core\Filament\Resources\Products\Schemas;

use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                // LEFT / MAIN (2/3 width)
                Grid::make()->schema([
                    Section::make('Product Information')
                        ->schema([
                            TextEntry::make('name')
                                ->label('Product Name')
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

                    Section::make('Product Configuration')
                        ->schema([
                            TextEntry::make('has_variations')
                                ->label('Has Variations')
                                ->badge()
                                ->color(fn ($state) => $state ? 'success' : 'gray')
                                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                            TextEntry::make('variations_count')
                                ->label('Total Variations')
                                ->getStateUsing(fn ($record) => $record?->variations()->count() ?? 0)
                                ->badge()
                                ->color('info'),
                            TextEntry::make('is_preparable')
                                ->label('Is Preparable')
                                ->badge()
                                ->color(fn ($state) => $state ? 'warning' : 'gray')
                                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                ->helperText('Can be prepared from other products'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),

                    Section::make('Product Attributes')
                        ->schema([
                            TextEntry::make('attribute_matrix')
                                ->label('Configured Attributes')
                                ->state(function ($record) {
                                    if (! $record) {
                                        return 'No attributes configured';
                                    }
                                    $attributes = $record->attributes()->with('attribute')->get();
                                    if ($attributes->isEmpty()) {
                                        return 'No attributes configured';
                                    }

                                    return $attributes->map(function ($pa) {
                                        $attrName = $pa->attribute?->name ?? 'Attribute';
                                        $vals = collect($pa->values ?? [])->implode(', ');

                                        return $attrName.': '.$vals;
                                    })->implode(' | ');
                                })
                                ->placeholder('No attributes configured')
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),

                    Section::make('Product Performance')
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
                ])->columnSpan(2),

                // RIGHT SIDEBAR (1/3 width)
                Grid::make()->schema([
                    Section::make('Product Status')
                        ->schema([
                            TextEntry::make('status')
                                ->label('Current Status')
                                ->badge()
                                ->hintIcon(fn ($r) => $r?->status?->getIcon())
                                ->helperText(fn ($r) => $r?->status?->getDescription())
                                ->size('lg'),
                        ])
                        ->columnSpanFull(),

                    Section::make('Product Classification')
                        ->schema([
                            TextEntry::make('brand.name')
                                ->label('Brand')
                                ->placeholder('No brand assigned')
                                ->weight('medium'),
                            TextEntry::make('category.name')
                                ->label('Category')
                                ->placeholder('No category assigned')
                                ->weight('medium'),
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
                ])->columnSpan(1),
            ])->columnSpanFull(),
        ]);
    }
}
