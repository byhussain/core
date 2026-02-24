<?php

namespace SmartTill\Core\Filament\Resources\Suppliers\RelationManagers;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Enums\PaymentMethod;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Transactions\Tables\TransactionsTable;
use SmartTill\Core\Services\PaymentService;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ResourceCanAccessHelper::check('View Supplier Transactions');
    }

    public function table(Table $table): Table
    {
        return TransactionsTable::configure($table)
            ->headerActions([
                Action::make('pay')
                    ->label('Pay to Supplier')
                    ->visible(fn () => ResourceCanAccessHelper::check('Pay to Suppliers'))
                    ->authorize(fn () => ResourceCanAccessHelper::check('Pay to Suppliers'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->required()
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
                        $supplier = $livewire->getOwnerRecord();
                        $amount = $data['amount'];

                        app(PaymentService::class)->recordPayment(
                            payable: $supplier,
                            amount: $amount,
                            paymentMethod: $data['payment_method'],
                            note: $data['note'] ?? null
                        );

                        Notification::make()
                            ->title('Supplier debited successfully')
                            ->success()
                            ->send();
                    })
                    ->icon(Heroicon::OutlinedArrowUp),
            ]);
    }
}
