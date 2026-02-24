<?php

namespace SmartTill\Core\Filament\Resources\CashTransactions\Tables;

use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use SmartTill\Core\Enums\CashTransactionType;

class CashTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CashTransactionType::SalePaid->value => 'Sale Paid',
                        CashTransactionType::PaymentReceived->value => 'Payment Received',
                        CashTransactionType::SaleRefunded->value => 'Sale Refunded',
                        CashTransactionType::SaleCancelled->value => 'Sale Cancelled',
                        CashTransactionType::CashCollected->value => 'Cash Collected',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CashTransactionType::SalePaid->value,
                        CashTransactionType::PaymentReceived->value => 'success',
                        CashTransactionType::SaleRefunded->value,
                        CashTransactionType::SaleCancelled->value => 'danger',
                        CashTransactionType::CashCollected->value => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->sortable()
                    ->color(fn ($record) => $record->amount >= 0 ? 'success' : 'danger'),
                TextColumn::make('cash_balance')
                    ->label('Cash Balance')
                    ->money(fn () => Filament::getTenant()?->currency->code ?? 'PKR')
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Note')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('collected_by')
                    ->label('Collected By')
                    ->getStateUsing(function ($record) {
                        if (! $record) {
                            return '—';
                        }

                        if (! isset($record->collected_by) || $record->collected_by === null) {
                            return '—';
                        }

                        // Ensure the relationship is loaded
                        if (! $record->relationLoaded('collectedBy')) {
                            $record->load('collectedBy');
                        }

                        return $record->collectedBy?->name ?? '—';
                    })
                    ->placeholder('—')
                    ->sortable(false)
                    ->searchable(false),
                TextColumn::make('created_at')
                    ->label('Created at')
                    ->since()
                    ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->setTimezone(Filament::getTenant()?->timezone?->name ?? 'UTC')->format('M d, Y g:i A'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated at')
                    ->since()
                    ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->updated_at?->setTimezone(Filament::getTenant()?->timezone?->name ?? 'UTC')->format('M d, Y g:i A'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        CashTransactionType::SalePaid->value => 'Sale Paid',
                        CashTransactionType::PaymentReceived->value => 'Payment Received',
                        CashTransactionType::SaleRefunded->value => 'Sale Refunded',
                        CashTransactionType::SaleCancelled->value => 'Sale Cancelled',
                        CashTransactionType::CashCollected->value => 'Cash Collected',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
