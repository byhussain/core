<?php

namespace SmartTill\Core\Filament\Concerns;

use App\Models\Store;

trait FormatsCurrency
{
    protected function formatCurrencyAmount(int|float $amount, ?Store $store = null): string
    {
        $decimalPlaces = $store?->currency?->decimal_places ?? 2;
        $currencyCode = $store?->currency?->code;

        $formatted = number_format($amount, $decimalPlaces, '.', ',');

        return $currencyCode ? $currencyCode.' '.$formatted : $formatted;
    }

    protected function formatCompactCurrency(int|float $number, ?Store $store = null, bool $showSign = false): string
    {
        $currencyCode = $store?->currency?->code ?? 'PKR';

        $sign = '';
        if ($showSign) {
            if ($number < 0 || (is_float($number) && (string) $number === '-0')) {
                $sign = '-';
            } elseif ($number > 0) {
                $sign = '+';
            }
        }

        $abs = abs($number);
        $formatted = match (true) {
            $abs >= 1_000_000_000 => $sign.number_format($abs / 1_000_000_000, 1).'B',
            $abs >= 1_000_000 => $sign.number_format($abs / 1_000_000, 1).'M',
            $abs >= 1_000 => $sign.number_format($abs / 1_000, 1).'k',
            default => $sign.number_format($abs, 0),
        };

        return $currencyCode.' '.$formatted;
    }

    protected function convertFromStorage(int|float $rawAmount, ?Store $store = null): float
    {
        $decimalPlaces = $store?->currency?->decimal_places ?? 2;
        $multiplier = (int) pow(10, $decimalPlaces);

        return floatval($rawAmount) / $multiplier;
    }
}
