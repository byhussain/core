<?php

namespace SmartTill\Core\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use SmartTill\Core\Models\PurchaseOrder;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Header Section - Key Information
                Section::make('Purchase Order Overview')
                    ->description('Essential purchase order information')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('supplier.name')
                                    ->label('Supplier')
                                    ->weight('bold')
                                    ->icon(Heroicon::OutlinedBuildingOffice)
                                    ->columnSpan(1),
                                TextEntry::make('reference')
                                    ->label('Reference')
                                    ->prefix('#')
                                    ->weight('bold')
                                    ->icon(Heroicon::OutlinedHashtag)
                                    ->columnSpan(1),
                                TextEntry::make('status')
                                    ->badge()
                                    ->columnSpan(1),
                                TextEntry::make('items_count')
                                    ->label('Items')
                                    ->getStateUsing(fn ($record) => $record->purchaseOrderProducts()->count())
                                    ->numeric()
                                    ->icon(Heroicon::OutlinedCube)
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible(false)
                    ->compact()
                    ->columnSpanFull(),

                // Financial Summary Section
                Section::make('Financial Summary')
                    ->description('Purchase order financial overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Left Section - Requested Summary
                                Section::make('Requested Summary')
                                    ->description('Requested purchase order details')
                                    ->inlineLabel()
                                    ->schema([
                                        TextEntry::make('items_count_requested')
                                            ->label('Total Items')
                                            ->getStateUsing(fn ($record) => $record->purchaseOrderProducts()->count())
                                            ->numeric()
                                            ->icon(Heroicon::OutlinedCube)
                                            ->weight('medium')
                                            ->columnSpanFull(),

                                        TextEntry::make('total_requested_tax_amount')
                                            ->label('Total Tax')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->icon(Heroicon::OutlinedPercentBadge)
                                            ->weight('medium')
                                            ->visible(fn () => Filament::getTenant()?->tax_enabled ?? false)
                                            ->columnSpanFull(),

                                        TextEntry::make('total_requested_unit_price')
                                            ->label('Total Unit Price')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->icon(Heroicon::OutlinedCurrencyDollar)
                                            ->weight('medium')
                                            ->columnSpanFull(),

                                        TextEntry::make('calculated_total_requested_supplier_price')
                                            ->label('Total Supplier Cost')
                                            ->state(fn (PurchaseOrder $record): float => $record->calculateRequestedSupplierTotal())
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->weight('bold')
                                            ->color('success')
                                            ->icon(Heroicon::OutlinedBanknotes)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(false)
                                    ->compact()
                                    ->columnSpan(1),

                                // Right Section - Received Information
                                Section::make('Received Information')
                                    ->description('Received purchase order details')
                                    ->inlineLabel()
                                    ->schema([
                                        TextEntry::make('items_count_received')
                                            ->label('Total Items')
                                            ->getStateUsing(fn ($record) => $record->purchaseOrderProducts()->whereNotNull('received_quantity')->count())
                                            ->numeric()
                                            ->icon(Heroicon::OutlinedCheckCircle)
                                            ->color('success')
                                            ->weight('medium')
                                            ->visible(function ($record): bool {
                                                $status = $record->status;
                                                if ($status instanceof \SmartTill\Core\Enums\PurchaseOrderStatus) {
                                                    return $status === \SmartTill\Core\Enums\PurchaseOrderStatus::Closed;
                                                }

                                                return $status === 'closed';
                                            })
                                            ->columnSpanFull(),

                                        TextEntry::make('total_received_tax_amount')
                                            ->label('Total Tax')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->icon(Heroicon::OutlinedPercentBadge)
                                            ->color('success')
                                            ->weight('medium')
                                            ->visible(function ($record): bool {
                                                $store = Filament::getTenant();
                                                if (! $store?->tax_enabled) {
                                                    return false;
                                                }

                                                $status = $record->status;
                                                // Check if status is Closed enum or 'closed' string
                                                if ($status instanceof \SmartTill\Core\Enums\PurchaseOrderStatus) {
                                                    return $status === \SmartTill\Core\Enums\PurchaseOrderStatus::Closed;
                                                }

                                                // Fallback for string comparison
                                                $statusValue = is_string($status) ? strtolower($status) : ($status?->value ?? '');

                                                return strtolower($statusValue) === 'closed';
                                            })
                                            ->columnSpanFull(),

                                        TextEntry::make('total_received_unit_price')
                                            ->label('Total Unit Price')
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->icon(Heroicon::OutlinedCurrencyDollar)
                                            ->color('success')
                                            ->weight('medium')
                                            ->visible(function ($record): bool {
                                                $status = $record->status;
                                                if ($status instanceof \SmartTill\Core\Enums\PurchaseOrderStatus) {
                                                    return $status === \SmartTill\Core\Enums\PurchaseOrderStatus::Closed;
                                                }

                                                return $status === 'closed';
                                            })
                                            ->columnSpanFull(),

                                        TextEntry::make('calculated_total_received_supplier_price')
                                            ->label('Total Supplier Cost')
                                            ->state(fn (PurchaseOrder $record): float => $record->calculateReceivedSupplierTotal())
                                            ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                                            ->weight('bold')
                                            ->color('success')
                                            ->icon(Heroicon::OutlinedBanknotes)
                                            ->visible(function ($record): bool {
                                                $status = $record->status;
                                                if ($status instanceof \SmartTill\Core\Enums\PurchaseOrderStatus) {
                                                    return $status === \SmartTill\Core\Enums\PurchaseOrderStatus::Closed;
                                                }

                                                return $status === 'closed';
                                            })
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(false)
                                    ->compact()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible(false)
                    ->compact()
                    ->columnSpanFull(),

                // Additional Information Section
                Section::make('Additional Information')
                    ->description('Timestamps and audit trail')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime()
                                    ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                    ->icon(Heroicon::OutlinedCalendar)
                                    ->columnSpan(1),
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                    ->icon(Heroicon::OutlinedClock)
                                    ->columnSpan(1),
                                TextEntry::make('created_by')
                                    ->label('Created By')
                                    ->getStateUsing(fn ($record) => $record->store->name ?? 'System')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->columnSpan(1),
                                TextEntry::make('deleted_at')
                                    ->label('Deleted')
                                    ->dateTime()
                                    ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                                    ->hidden(fn ($record) => ! $record->deleted_at)
                                    ->icon(Heroicon::OutlinedTrash)
                                    ->color('danger')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(true)
                    ->compact()
                    ->columnSpanFull(),
            ]);
    }
}
