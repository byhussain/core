<?php

it('updates payment method visibility immediately when payment status changes', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2).'/src/Filament/Resources/Sales/Schemas/SaleForm.php');

    expect($contents)
        ->toContain("Select::make('payment_status')")
        ->toContain('->live()')
        ->toContain("Select::make('payment_method')")
        ->toContain("->visible(fn (\$get) => (self::getPaymentStatusValue(\$get) === SalePaymentStatus::Paid->value))");
});
