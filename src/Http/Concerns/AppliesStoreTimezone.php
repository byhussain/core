<?php

namespace SmartTill\Core\Http\Concerns;

trait AppliesStoreTimezone
{
    /**
     * Align the request runtime timezone with the store's timezone.
     *
     * Records are written through the Filament store panel, where the
     * SetTenantTimezone middleware sets app.timezone to the store's zone — so
     * created_at is persisted as a store-local wall clock. Public print routes
     * run on the plain "web" middleware with no tenant, leaving app.timezone at
     * UTC; the print blade then calls setTimezone(store tz) on a value it wrongly
     * believes is UTC, shifting the displayed time by the store's offset.
     *
     * Re-applying the store timezone here makes the read context match the write
     * context, so the timestamp is labelled correctly and the blade conversion
     * becomes a no-op. Call this before any model timestamp is accessed.
     */
    protected function applyStoreTimezone(?string $timezone): void
    {
        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
    }
}
