# smart-till/core

Core Filament modules for SMART TiLL.

This package contains the extracted module code (resources, models, services, enums, observers, migrations) and installs with:

```bash
php artisan core:install --no-interaction
```

## Requirements

Before installing this package in a host Laravel app:

1. Laravel 12 and Filament v5 are installed.
2. `App\Models\Store` exists.
3. `stores` table exists in database.
4. Filament tenant setup in host app uses `Store`.

## Install Option A: Local Path (same machine)

Use this when your package source is local (for example during development).

### 1) Update host project `composer.json`

Example for host project at `/Users/pasha/PhpstormProjects/SMART-TiLL-POS`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../SMART-TiLL/packages/smart-till/core",
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

### 2) Install package

```bash
cd /Users/pasha/PhpstormProjects/SMART-TiLL-POS
composer update smart-till/core --no-interaction
```

### 3) Run installer

```bash
php artisan core:install --no-interaction
```

### 4) Verify command exists

```bash
php artisan list | rg core:install
```

## Install Option B: GitHub repo (VCS)

Use this when package is pushed to:

`https://github.com/SMART-DADDY/core`

### 1) Ensure package repo metadata

In package repo `composer.json`:

```json
{
  "name": "smart-till/core",
  "autoload": {
    "psr-4": {
      "SmartTill\\Core\\": "src/"
    }
  }
}
```

### 2) Push code and create tag (recommended)

From package repo:

```bash
git add .
git commit -m "Initial package release"
git push origin main
git tag v0.1.0
git push origin v0.1.0
```

### 3) Add VCS repository in host app

In host project `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/SMART-DADDY/core"
    }
  ],
  "require": {
    "smart-till/core": "^0.1"
  }
}
```

If no tag exists yet:

```json
"smart-till/core": "dev-main"
```

If stability blocks install, use:

```json
"smart-till/core": "dev-main@dev"
```

### 4) Install and run setup

```bash
composer update smart-till/core --no-interaction
php artisan core:install --no-interaction
```

## Private GitHub repository setup

If `SMART-DADDY/core` is private, configure Composer auth:

```bash
composer config --global github-oauth.github.com <GITHUB_TOKEN>
```

Then run:

```bash
composer update smart-till/core --no-interaction
```

## Upgrade package in host app

### With tags

```bash
composer update smart-till/core --with-all-dependencies --no-interaction
php artisan core:install --no-interaction
```

### With dev branch

```bash
composer update smart-till/core --no-interaction
php artisan core:install --no-interaction
```

## What `core:install` does

`core:install`:

1. Verifies `App\Models\Store` exists.
2. Verifies `stores` table exists.
3. Runs package migrations.
4. Exits with clear error when prerequisites are missing.

## Troubleshooting

### Error: `Missing required model: App\Models\Store`

Create the model in host app:

```bash
php artisan make:model Store
```

### Error: `Missing required table: stores`

Run host app migrations that create `stores` table, then rerun installer.

### Composer cannot find package

1. Confirm `name` is exactly `smart-till/core`.
2. Confirm repository URL is correct.
3. For tags, run `composer clear-cache` and retry.

### Install works but Filament resources do not appear

1. Ensure host panel discovers package resources (or provider is auto-discovered).
2. Clear caches:

```bash
php artisan optimize:clear
```

## Quick Start (VCS flow)

```bash
# In host app
composer update smart-till/core --no-interaction
php artisan core:install --no-interaction
php artisan optimize:clear
```

