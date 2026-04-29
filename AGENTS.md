# SMART TiLL Core — Agent Guidelines

## What This Repo Is

`smart-till/core` is a **Laravel package** — not a standalone application. It provides shared Filament modules (resources, tables, forms, widgets, models, observers, services) consumed by two host apps:

- **SMART-TiLL** (`../SMART-TiLL`) — the main Laravel + Filament admin app (MySQL)
- **SMART-TiLL-POS** (`../SMART-TiLL-POS`) — the POS app (SQLite)

There is no `artisan` binary here. Do not run `php artisan` commands in this directory.

---

## How Issues Flow Into This Repo

Issues are almost always reported against **SMART-TiLL** or **SMART-TiLL-POS** — not against `core` directly. But many of those issues are actually caused by code that lives here.

### The fix workflow

1. An issue is raised in SMART-TiLL or SMART-TiLL-POS
2. You trace the broken code to `vendor/smart-till/core/src/` in that app
3. The real source is **here** (`../core/src/`) — fix it here, not in vendor
4. Write a test here in `tests/` that proves the fix — this is the only place core tests live

### Never fix in vendor

`vendor/smart-till/core/` inside SMART-TiLL or SMART-TiLL-POS is a **read-only copy** — it is overwritten by `composer install`. Any fix there is permanently lost. Always fix in this repo (`../core/src/`).

### Context differences between host apps

| Concern | SMART-TiLL | SMART-TiLL-POS |
|---|---|---|
| Database | MySQL | SQLite |
| Environment | Cloud / online | Desktop / offline |
| Extra packages | Horizon, Nightwatch, Telescope | NativePHP |

When fixing something in `core`, ensure the fix works for **both** host apps. SQLite-incompatible code (MySQL-specific queries, unsupported index operations) will break SMART-TiLL-POS even if it works fine on SMART-TiLL.

---

## Package Stack

| Package | Version |
|---|---|
| php | 8.4 |
| laravel/framework | ^12.0 |
| filament/filament | ^5.0 |
| livewire/livewire | ^4.0 |
| laravel/sanctum | ^4.0 |
| pestphp/pest | ^4.0 |
| orchestra/testbench | ^10.0 |

---

## Source Structure

```
src/
├── Casts/           # Custom Eloquent casts
├── Console/         # Artisan commands (CoreInstallCommand, etc.)
├── Enums/           # PHP-backed enums
├── Filament/
│   ├── Concerns/    # Reusable Filament traits
│   ├── Exports/     # Filament export classes
│   ├── Forms/       # Reusable form schemas
│   ├── Imports/     # Filament import classes
│   ├── Pages/       # Filament pages
│   ├── Resources/   # Filament resources (each has Forms/, Tables/, Pages/, RelationManagers/)
│   │   └── Helpers/ # Shared column/form helpers (e.g. SyncReferenceColumn)
│   └── Widgets/     # Filament widgets
├── Http/
│   └── Controllers/ # Package controllers (e.g. PublicReceiptController)
├── Livewire/        # Standalone Livewire components
├── Models/          # Eloquent models
├── Notifications/   # Laravel notifications
├── Observers/       # Eloquent model observers
├── Providers/       # CoreServiceProvider (single entry point)
├── Services/        # Domain service classes
├── Support/         # Misc support/utility classes
└── Traits/          # Reusable PHP traits
```

---

## ⚠️ Test Enforcement — Non-Negotiable

**Every addition, change, or removal requires a test. No exceptions.**

- Adding a new class, method, or behaviour → write a test that proves it works
- Modifying existing logic → update the existing test or add a new one
- Removing something → remove or update the tests that covered it
- Refactoring → existing tests must still pass; add new ones if behaviour changes

Do not consider any task complete until `vendor/bin/pest --compact` passes with all tests green.

---

## Running Tests

```bash
# Run all tests
vendor/bin/pest --compact

# Run a specific test file
vendor/bin/pest --compact tests/Feature/CoreBrandReferenceBootHookTest.php

# Filter by test name
vendor/bin/pest --compact --filter="brand reference"
```

There is no `php artisan test` here — use `vendor/bin/pest` directly.

---

## Test Architecture

Two base test case classes live in `tests/`:

### `TestCase` (default — for most tests)
Extends `Orchestra\Testbench\TestCase`. Boots `LivewireServiceProvider` and `CoreServiceProvider` with an in-memory SQLite DB. Use this for:
- Static code analysis tests (asserting source file contents)
- Behaviour tests that don't need migrations

```php
// tests/Pest.php already extends this for all Feature tests
use SmartTill\Core\Tests\TestCase;
pest()->extend(TestCase::class)->in('Feature');
```

### `DatabaseTestCase` (when you need real DB)
Extends `TestCase` and adds `RefreshDatabase` + loads all package migrations. Use this for tests that create models, run queries, or test observers.

```php
use SmartTill\Core\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('persists a product', function (): void {
    $product = Product::factory()->create(['name' => 'Test']);
    expect(Product::find($product->id)->name)->toBe('Test');
});
```

### Existing test style — static code analysis
Most existing tests use `file_get_contents()` to assert patterns in source files. Follow this convention for structural/architectural tests:

```php
it('uses the correct column type', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2).'/src/Filament/Resources/Sales/Tables/SalesTable.php');

    expect($contents)
        ->toContain('SyncReferenceColumn::make(')
        ->not->toContain("TextColumn::make('reference')");
});
```

---

## Conventions

- Follow existing code patterns — check sibling files before writing anything new
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`
- PHP 8 constructor property promotion: `public function __construct(public GitHub $github) {}`
- Explicit return types and type hints on all methods
- Always use curly braces for control structures
- TitleCase for Enum keys: `FavoritePerson`, `Monthly`
- PHPDoc blocks over inline comments; use array shape types in PHPDoc

### Creating new files

No `php artisan make:` here. Create files manually, following the exact structure of sibling files. For example:
- New model → mirror an existing model in `src/Models/`
- New resource → mirror an existing resource directory under `src/Filament/Resources/`
- New observer → mirror an existing observer in `src/Observers/` and register it in `CoreServiceProvider::boot()`

### Registering new things in CoreServiceProvider

`src/Providers/CoreServiceProvider.php` is the single bootstrap point. When you add:
- A new model observer → register in `boot()` with `ModelName::observe(ObserverClass::class)`
- A new singleton service → register in `register()` with `$this->app->singleton(...)`
- A new Livewire component → register in `boot()` with `Livewire::component('name', Component::class)`
- A new migration → place it in `database/migrations/` (loaded automatically via `loadMigrationsFrom`)

---

## Host App Integration

This package bridges with host app models it does not own. Be careful:

- `App\Models\User` and `App\Models\Store` are **host app classes** — always guard with `class_exists()` before using
- Relations on host models are registered via `resolveRelationUsing()` in `CoreServiceProvider`, not in the models themselves
- Morph map compatibility is registered in `registerMorphMapCompatibility()` — if you add a new morphable model, register it there

---

## Code Formatting

After modifying any PHP file, run Pint to enforce code style:

```bash
vendor/bin/pint --dirty
```

Do not run `--test` mode — just run it and let it fix formatting automatically.

---

## Documentation Files

Only create documentation files when explicitly requested. Do not create READMEs, changelogs, or guides unless asked.

## Replies

Be concise. Focus on what matters — avoid restating obvious details.
