<?php

namespace SmartTill\Core\Filament\Resources\CashTransactions;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use SmartTill\Core\Filament\Resources\CashTransactions\Pages\ListCashTransactions;
use SmartTill\Core\Filament\Resources\CashTransactions\Tables\CashTransactionsTable;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Models\CashTransaction;
use UnitEnum;

class CashTransactionResource extends Resource
{
    protected static ?string $model = CashTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::Banknotes;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Transactions';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Cash Transactions');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Cash Transactions');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Cash Transactions');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canRestore($record): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return CashTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashTransactions::route('/'),
        ];
    }
}
