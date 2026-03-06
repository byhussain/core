<?php

namespace SmartTill\Core\Filament\Resources\Payments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Payments\Pages\ListPayments;
use SmartTill\Core\Filament\Resources\Payments\Tables\PaymentsTable;
use SmartTill\Core\Models\Payment;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::CreditCard;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Transactions';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Payments');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Payments');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Payments');
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'note'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return 'Payment #'.($record->reference ?: ($record->local_id ?: $record->id));
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Payable' => $record->payable?->name,
            'Amount' => $record->amount,
            'Method' => $record->payment_method?->value ?? null,
        ];
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
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
            'index' => ListPayments::route('/'),
        ];
    }
}
