<?php

namespace SmartTill\Core\Filament\Resources\PurchaseOrders\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use SmartTill\Core\Filament\Resources\PurchaseOrders\PurchaseOrderResource;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load relationships to prevent N+1 queries
        $this->record->loadMissing([
            'purchaseOrderProducts.variation.product',
            'supplier',
        ]);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn ($record) => ! $record->trashed() && $record->status !== \SmartTill\Core\Enums\PurchaseOrderStatus::Closed),
            RestoreAction::make()
                ->visible(fn ($record) => $record->trashed()),
            ForceDeleteAction::make()
                ->visible(fn ($record) => $record->trashed()),
        ];
    }

    protected function beforeSave(): void
    {
        if (empty($this->data['purchaseOrderProducts'] ?? [])) {
            Notification::make()
                ->title('No items added')
                ->body('Please add items before saving the purchase order.')
                ->danger()
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'purchaseOrderProducts' => 'Please add at least one item.',
            ]);
        }
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
