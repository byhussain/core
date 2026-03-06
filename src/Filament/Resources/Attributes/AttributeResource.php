<?php

namespace SmartTill\Core\Filament\Resources\Attributes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SmartTill\Core\Filament\Resources\Attributes\Pages\CreateAttribute;
use SmartTill\Core\Filament\Resources\Attributes\Pages\EditAttribute;
use SmartTill\Core\Filament\Resources\Attributes\Pages\ListAttributes;
use SmartTill\Core\Filament\Resources\Attributes\Schemas\AttributeForm;
use SmartTill\Core\Filament\Resources\Attributes\Tables\AttributesTable;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Models\Attribute;
use UnitEnum;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::AdjustmentsHorizontal;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Attributes');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Attributes');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Attributes');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Attributes');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Attributes');
    }

    public static function canDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Delete Attributes');
    }

    public static function canRestore($record): bool
    {
        return ResourceCanAccessHelper::check('Restore Attributes');
    }

    public static function canForceDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Force Delete Attributes');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'local_id', 'name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Reference' => $record->reference ?: ($record->local_id ?: "#{$record->id}"),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return AttributeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttributesTable::configure($table);
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
            'index' => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit' => EditAttribute::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
