<?php

namespace SmartTill\Core\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Enums\PaymentMethod;
use SmartTill\Core\Filament\Exports\CustomerTransactionExporter;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Transactions\Tables\TransactionsTable;
use SmartTill\Core\Services\PaymentService;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ResourceCanAccessHelper::check('View Customer Transactions');
    }

    public function table(Table $table): Table
    {
        return TransactionsTable::configure($table)
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'customer_debit' => 'Debit',
                        'customer_credit' => 'Credit',
                    ])
                    ->multiple()
                    ->preload(),

                Filter::make('created_at_range')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $data['until'])
                            );
                    }),
            ])
            ->headerActions([
                Action::make('receive')
                    ->label('Receive Payment')
                    ->visible(fn () => ResourceCanAccessHelper::check('Receive Payment from Customers'))
                    ->authorize(fn () => ResourceCanAccessHelper::check('Receive Payment from Customers'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Positive = Payment received, Negative = Receivable added')
                                    ->placeholder('Enter positive for payment, negative for receivable')
                                    ->prefix(fn () => Filament::getTenant()?->currency->code ?? 'PKR'),
                                Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options(PaymentMethod::class)
                                    ->default(PaymentMethod::Cash)
                                    ->required()
                                    ->enum(PaymentMethod::class),
                                Textarea::make('note')
                                    ->label('Note')
                                    ->maxLength(50)
                                    ->helperText('Up to 50 characters.')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $customer = $livewire->getOwnerRecord();
                        if (! array_key_exists('amount', $data) || ! array_key_exists('payment_method', $data)) {
                            Notification::make()
                                ->title('Missing payment details')
                                ->body('Please provide amount and payment method.')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            app(PaymentService::class)->recordPayment(
                                payable: $customer,
                                amount: $data['amount'],
                                paymentMethod: $data['payment_method'],
                                note: $data['note'] ?? null
                            );

                            $isReceivable = $data['amount'] < 0;
                            Notification::make()
                                ->title($isReceivable ? 'Receivable added successfully' : 'Payment received successfully')
                                ->body($isReceivable ? 'Receivable recorded and transaction entry created' : 'Payment recorded and transaction entry created')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to record payment')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->icon(Heroicon::OutlinedArrowDown),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(CustomerTransactionExporter::class)
                        ->visible(fn () => ResourceCanAccessHelper::check('Export Sales'))
                        ->authorize(fn () => ResourceCanAccessHelper::check('Export Sales')),
                ]),
            ]);
    }
}
