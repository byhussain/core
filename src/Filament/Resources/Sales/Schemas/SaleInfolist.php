<?php

namespace SmartTill\Core\Filament\Resources\Sales\Schemas;

use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->components([
                        // LEFT / MAIN (2/3 width)
                        Grid::make()
                            ->schema([
                                Section::make('Sale Information')
                                    ->schema([
                                        TextEntry::make('reference')
                                            ->label('Sale Reference')
                                            ->prefix('#')
                                            ->weight('semibold')
                                            ->size('lg'),
                                        TextEntry::make('status')
                                            ->label('Sale Status')
                                            ->badge()
                                            ->hintIcon(fn ($r) => $r?->status?->getIcon())
                                            ->helperText(fn ($r) => $r?->status?->getDescription())
                                            ->size('lg'),
                                        TextEntry::make('payment_status')
                                            ->label('Payment Status')
                                            ->badge()
                                            ->hintIcon(fn ($r) => $r?->payment_status?->getIcon())
                                            ->helperText(fn ($r) => $r?->payment_status?->getDescription()),
                                        TextEntry::make('paid_at')
                                            ->label('Payment Date')
                                            ->since()
                                            ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                            ->tooltip(function ($record) {
                                                $paidAt = $record?->paid_at;
                                                if (! $paidAt) {
                                                    return null;
                                                }

                                                $timezone = Filament::getTenant()?->timezone?->name ?? 'UTC';

                                                return $paidAt->setTimezone($timezone)->format('d-m-Y h:i A');
                                            })
                                            ->placeholder('Not paid yet'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Sale Summary')
                                    ->schema([
                                        TextEntry::make('subtotal')
                                            ->label('Subtotal')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->size('lg'),
                                        TextEntry::make('discount')
                                            ->label('Discount')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->placeholder('No discount'),
                                        TextEntry::make('tax')
                                            ->label('Tax')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->placeholder('No tax')
                                            ->visible(fn ($record) => $record->use_fbr),
                                        TextEntry::make('freight_fare')
                                            ->label('Freight Fare')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->placeholder('No freight fare')
                                            ->visible(fn ($record) => ($record->freight_fare ?? 0) > 0),
                                        TextEntry::make('total')
                                            ->label('Final Total')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->color('success')
                                            ->size('lg'),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),

                                Section::make('Sale Performance')
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

                                Section::make('FBR Invoice Information')
                                    ->schema([
                                        TextEntry::make('use_fbr')
                                            ->label('FBR Enabled')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                                        TextEntry::make('fbr_invoice_number')
                                            ->label('FBR Invoice Number')
                                            ->placeholder('Not synced to FBR')
                                            ->badge()
                                            ->color('success')
                                            ->visible(fn ($record) => $record->fbr_invoice_number),
                                        TextEntry::make('fbr_synced_at')
                                            ->label('FBR Sync Date')
                                            ->dateTime()
                                            ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                            ->placeholder('Not synced to FBR')
                                            ->visible(fn ($record) => $record->fbr_synced_at),
                                        TextEntry::make('fbr_response')
                                            ->label('FBR Response')
                                            ->formatStateUsing(fn ($state) => $state['Response'] ?? 'N/A')
                                            ->visible(fn ($record) => $record->fbr_response && isset($record->fbr_response['Response'])),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->use_fbr),

                                Section::make('FBR Refund Information')
                                    ->schema([
                                        TextEntry::make('fbr_refund_invoice_number')
                                            ->label('Refund Invoice Number')
                                            ->placeholder('Refund not synced to FBR')
                                            ->badge()
                                            ->color('danger')
                                            ->visible(fn ($record) => $record->fbr_refund_invoice_number),
                                        TextEntry::make('fbr_refund_synced_at')
                                            ->label('Refund Sync Date')
                                            ->dateTime()
                                            ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                            ->placeholder('Refund not synced')
                                            ->visible(fn ($record) => $record->fbr_refund_synced_at),
                                        TextEntry::make('cancelled_info')
                                            ->label('Cancellation Details')
                                            ->formatStateUsing(function ($record) {
                                                if ($record->status === \SmartTill\Core\Enums\SaleStatus::Cancelled) {
                                                    $info = 'Sale cancelled';
                                                    if ($record->fbr_refund_invoice_number) {
                                                        $info .= ' - FBR Refund Invoice: '.$record->fbr_refund_invoice_number;
                                                    } elseif ($record->fbr_invoice_number) {
                                                        $info .= ' - FBR refund pending';
                                                    }

                                                    return $info;
                                                }

                                                return null;
                                            })
                                            ->visible(fn ($record) => $record->status === \SmartTill\Core\Enums\SaleStatus::Cancelled)
                                            ->color('danger')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->status === \SmartTill\Core\Enums\SaleStatus::Cancelled && $record->use_fbr),
                            ])
                            ->columnSpan(2),

                        // RIGHT SIDEBAR (1/3 width)
                        Grid::make(1)
                            ->columnSpan(1)
                            ->components([
                                Section::make('Customer Information')
                                    ->components([
                                        TextEntry::make('customer.name')
                                            ->label('Customer Name')
                                            ->placeholder('Guest Customer')
                                            ->weight('medium'),
                                        TextEntry::make('customer.phone')
                                            ->label('Phone Number')
                                            ->placeholder('No phone provided'),
                                        TextEntry::make('customer.email')
                                            ->label('Email')
                                            ->placeholder('No email provided'),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),

                                Section::make('Sale Details')
                                    ->components([
                                        TextEntry::make('variations_count')
                                            ->label('Items in Sale')
                                            ->getStateUsing(function ($record) {
                                                if (! $record) {
                                                    return 0;
                                                }

                                                $variationsCount = $record->variations()->count();
                                                $customCount = DB::table('sale_variation')
                                                    ->where('sale_id', $record->id)
                                                    ->whereNull('variation_id')
                                                    ->where('is_preparable', false)
                                                    ->count();

                                                return $variationsCount + $customCount;
                                            })
                                            ->badge()
                                            ->color('info'),
                                        TextEntry::make('created_at')
                                            ->label('Sale Date')
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

                                Section::make('User Activity')
                                    ->components([
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
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
