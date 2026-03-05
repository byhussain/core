<?php

namespace SmartTill\Core\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantTimezone = Filament::getTenant()?->timezone?->name;

        if (is_string($tenantTimezone) && in_array($tenantTimezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $tenantTimezone]);
            date_default_timezone_set($tenantTimezone);
        } else {
            date_default_timezone_set((string) config('app.timezone', 'UTC'));
        }

        return $next($request);
    }
}
