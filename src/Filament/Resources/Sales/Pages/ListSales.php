<?php

namespace SmartTill\Core\Filament\Resources\Sales\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use SmartTill\Core\Filament\Resources\Sales\SaleResource;
use SmartTill\Core\Filament\Resources\Sales\Widgets\SaleStats;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SaleStats::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All'),
            'today' => Tab::make('Today\'s Sales')
                ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            'this_month' => Tab::make('This Month\'s Sales')
                ->query(fn (Builder $query) => $query->whereMonth('created_at', now()->month)),
            'this_year' => Tab::make('This Year\'s Sales')
                ->query(fn (Builder $query) => $query->whereYear('created_at', now()->year)),
        ];
    }
}
