<?php

namespace SmartTill\Core\Filament\Resources\Customers\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use SmartTill\Core\Filament\Resources\Customers\CustomerResource;
use SmartTill\Core\Filament\Resources\Customers\Widgets\CustomerStatsOverview;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerStatsOverview::class,
        ];
    }

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
}
