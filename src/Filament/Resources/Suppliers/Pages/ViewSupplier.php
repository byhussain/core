<?php

namespace SmartTill\Core\Filament\Resources\Suppliers\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use SmartTill\Core\Filament\Resources\Suppliers\SupplierResource;
use SmartTill\Core\Filament\Resources\Suppliers\Widgets\SupplierStatsOverview;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->color('warning'),
            DeleteAction::make()
                ->label('Delete')
                ->color('danger'),
            RestoreAction::make()
                ->label('Restore')
                ->color('success'),
            ForceDeleteAction::make()
                ->label('Force delete')
                ->color('warning'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupplierStatsOverview::class,
        ];
    }
}
