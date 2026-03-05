# smart-till/core

`smart-till/core` is the shared business/domain layer for SMART TiLL Server and SMART TiLL POS.

This package centralizes:

- Filament Resources, Pages, Relation Managers, and Widgets
- Core Eloquent Models
- Domain Enums
- Domain Observers
- Shared Services (geo bootstrap, permissions bootstrap, settings bootstrap, units bootstrap)
- Shared routes/views used by both server and POS
- Shared schema migrations for core tables/columns

The goal is one implementation for both projects so behavior stays aligned.

## What This Package Actually Does

When installed in a host Laravel app, this package:

1. Registers `SmartTill\Core\Providers\CoreServiceProvider`
2. Loads package migrations from `database/migrations` inside this package
3. Loads package views (`resources/views`)
4. Loads package routes from `routes/web.php` (only if `public.receipt` route is not already registered)
5. Registers Livewire component: `product-search`
6. Registers morph-map compatibility for core/app aliases
7. Attaches core observers to core models
8. Provides installer commands to bootstrap required data

## Compatibility Matrix

- PHP: `^8.2`
- Laravel: `^12.0`
- Filament: `^5.0`
- Livewire: `^4.0`

From package `composer.json`:

- `filament/filament`
- `laravel/framework`
- `laravel/sanctum`
- `league/iso3166`
- `livewire/livewire`
- `pragmarx/countries`

## Host App Requirements

Before installation:

1. Host app has `App\Models\Store`
2. `stores` table exists
3. Host app uses Filament tenancy with `Store` tenant model
4. Host app has a `Store` panel that discovers package resources/pages

If these are missing, install command will fail fast with explicit errors.

## Installation

## Option A: VCS (Recommended for staging/production)

In host app `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/SMART-DADDY/core"
    }
  ],
  "require": {
    "smart-till/core": "^1.0"
  }
}
```

Then:

```bash
composer update smart-till/core --with-all-dependencies --no-interaction
php artisan core:install --no-interaction
php artisan optimize:clear
```

## Option B: Local Path (development only)

In host app `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../core",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "smart-till/core": "*@dev"
  }
}
```

Then:

```bash
composer update smart-till/core --no-interaction
php artisan core:install --no-interaction
```

## Installer Commands

## `php artisan core:install`

Runs on default DB connection and does:

1. Validates `App\Models\Store`
2. Validates `stores` table exists
3. Runs `php artisan migrate --force --no-interaction`
4. Bootstraps countries/currencies/timezones
5. Bootstraps store settings defaults
6. Bootstraps universal units
7. Bootstraps core permissions and super-admin role mapping

## `php artisan native:core:install`

For NativePHP sqlite runtime:

1. Validates `App\Models\Store`
2. Validates `native:migrate` command exists
3. Runs `php artisan native:migrate --force --no-interaction`
4. Validates `stores` exists on `nativephp` connection
5. Runs same bootstrap services on `nativephp` connection

## Host Panel Wiring (Required)

Your Filament Store panel must discover package resources/pages.

Example pattern:

```php
use ReflectionClass;
use SmartTill\Core\Providers\CoreServiceProvider;
use SmartTill\Core\Http\Middleware\SetTenantTimezone as CoreSetTenantTimezone;

$coreSrcPath = dirname((new ReflectionClass(CoreServiceProvider::class))->getFileName(), 2);

->discoverResources(in: $coreSrcPath.'/Filament/Resources', for: 'SmartTill\\Core\\Filament\\Resources')
->discoverPages(in: $coreSrcPath.'/Filament/Pages', for: 'SmartTill\\Core\\Filament\\Pages')
->tenantMiddleware([CoreSetTenantTimezone::class], isPersistent: true)
```

Without tenant timezone middleware, `created_at/updated_at` can render in wrong timezone.

## Config

The package reads:

- `config('smart_till.reference_on_create', true)`

If true:

- Store-scoped reference values are auto-assigned for resources having `reference` columns.

Recommended host config file:

```php
<?php

return [
    'reference_on_create' => true,
];
```

Save as: `config/smart_till.php`

## Migrations Included In This Package

Current package migrations:

1. `2026_02_25_000002_create_core_tables.php`
2. `2026_02_26_000003_add_store_user_cash_columns.php`
3. `2026_03_02_000010_add_local_id_to_sync_tables.php`
4. `2026_03_02_104300_add_reference_to_sync_tables.php`

These migrations are idempotent-style guarded with `Schema::hasTable/hasColumn` checks where appropriate.

## Important: Existing Projects With Legacy Data

This package ships foundational schema. Project-specific data correction/backfill migrations may exist in host app (for example polymorphic type normalization and reference backfills).

If your host app already has production data, you should:

1. Run package install/migrations
2. Run host app custom backfill/fix migrations
3. Verify key datasets (transactions, payments, references, dashboard stats)

Do not assume package migration alone backfills historical data.

## Morph Map / Polymorphic Compatibility

Core provider registers compatibility map for both app and core class names:

- `App\Models\Customer` <-> `SmartTill\Core\Models\Customer`
- `App\Models\Supplier` <-> `SmartTill\Core\Models\Supplier`
- `App\Models\Sale` <-> `SmartTill\Core\Models\Sale`
- `App\Models\PurchaseOrder` <-> `SmartTill\Core\Models\PurchaseOrder`
- `App\Models\Payment` <-> `SmartTill\Core\Models\Payment`
- `App\Models\Transaction` <-> `SmartTill\Core\Models\Transaction`

This is required because historical records can contain either alias.

## Observer Behavior

Core provider wires domain observers, including:

- Product/variation/stock/sale/payment/transaction lifecycle observers
- Store-scoped reference observer across multiple entities

Because this is centralized in package boot, behavior is shared between Server and POS when both consume the same core version.

## Versioning / Release Policy

For production:

1. Tag this repository (`vX.Y.Z`)
2. Pin host apps to that tag/range
3. Avoid `dev-main` in production

Recommended:

```json
"smart-till/core": "^1.0"
```

Not recommended for production:

```json
"smart-till/core": "dev-main"
```

## Upgrade Procedure

1. Update package version in host app
2. `composer update smart-till/core --with-all-dependencies --no-interaction`
3. `php artisan core:install --no-interaction`
4. `php artisan optimize:clear`
5. Run smoke tests:
   - Sales create/print
   - Purchase order flows
   - Payment flows
   - Customer/supplier transaction links
   - Dashboard widgets
   - Tenant timezone rendering

## Testing This Package

From package root:

```bash
./vendor/bin/pest
```

Or single file:

```bash
./vendor/bin/pest tests/Feature/CorePaymentPrintRouteGuardTest.php
```

## Troubleshooting

## `Missing required model: App\Models\Store`

Create host model:

```bash
php artisan make:model Store
```

## `Missing required table: stores`

Run host migrations that create `stores`, then rerun installer.

## Package installed but resources/pages not visible

1. Ensure panel `discoverResources/discoverPages` includes core namespace/path
2. Clear cache:

```bash
php artisan optimize:clear
```

## Wrong timezone display in Filament dates

1. Ensure tenant middleware includes `SetTenantTimezone`
2. Ensure store has valid `timezone_id` and related timezone row
3. Ensure date columns call `->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')`

## Notes For Contributors

- Keep business logic in `core` when shared between Server and POS.
- Keep host-app-only logic in host app.
- Add tests for behavior compatibility, especially around morph aliases and route/url generation.
- Avoid introducing breaking changes without migration strategy and release notes.
