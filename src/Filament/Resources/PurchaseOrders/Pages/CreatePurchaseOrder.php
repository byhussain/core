<?php

namespace SmartTill\Core\Filament\Resources\PurchaseOrders\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use SmartTill\Core\Filament\Resources\PurchaseOrders\PurchaseOrderResource;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function beforeCreate(): void
    {
        if (empty($this->data['purchaseOrderProducts'] ?? [])) {
            Notification::make()
                ->title('No items added')
                ->body('Please add items before creating the purchase order.')
                ->danger()
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'purchaseOrderProducts' => 'Please add at least one item.',
            ]);
        }
    }

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }
}
