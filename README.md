
![alt text](https://banners.beyondco.de/Laravel%20Settings.png?theme=light&packageManager=composer+require&packageName=egough%2Flaravel-settings&pattern=architect&style=style_2&description=Global+%26+Model+Settings&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg "Laravel Settings")

[![Latest Version on Packagist](https://img.shields.io/packagist/v/egough/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/egough/laravel-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/egough/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/egough/laravel-settings)
[![License](https://img.shields.io/packagist/l/egough/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/egough/laravel-settings)

Database-backed application and model settings for Laravel.

Provides a simple, typed and cacheable way to store dynamic configuration outside of `.env` and static config files, including per-model settings such as user or team preferences.

---


## Quick Example

```php
// Application setting
settings()->set('site.name', 'My Application');

settings()->get('site.name');


// Model-specific setting
$user->settings()->set('ui.theme', 'dark');

$user->settings()->get('ui.theme');


// Feature flag
if (flag('billing.enabled')) {
    // Feature enabled
}
```

Blade usage:

```blade
@hasFlag('billing.enabled')
    <x-billing-panel />
@endhasFlag

<title>@setting('site.name')</title>
```

---

## When Should I Use This Package?

Use this package when your application needs configuration that can change at runtime without modifying environment variables or deployment configuration.

Typical use cases include:

**Application settings**

* Site name or branding
* Feature toggles
* Maintenance or runtime configuration
* Admin-managed options

**Model settings**

* User preferences (theme, notifications, dashboard layout)
* Team or organisation configuration
* Account-specific options
* Per-project or per-resource metadata

This package is intended for dynamic configuration stored in the database and accessed consistently across your application.

---

### When Not to Use It

You should continue using Laravel configuration or environment variables for:

* Database credentials
* API keys and secrets
* Environment-specific infrastructure configuration
* Values required during application boot

In general:

* `.env` → infrastructure and secrets
* `config/*.php` → static application configuration
* `egough/laravel-settings` → runtime, database-driven configuration


---

## Installation

```bash
composer require egough/laravel-settings:^1.0
```

Publish configuration and migrations:

```bash
php artisan vendor:publish --tag=settings-config
php artisan vendor:publish --tag=settings-migrations
php artisan vendor:publish --tag=settings-model-migrations
php artisan migrate
```

---

## Application Settings

Global settings are accessible anywhere in your application.

### Helper

```php
settings()->set('site.name', 'My Application');

settings()->get('site.name');
```

### Facade

```php
use Settings;

Settings::set('site.name', 'My Application');
Settings::get('site.name');
```

### Dependency Injection

```php
use Egough\LaravelSettings\SettingsManager;

public function __construct(
    private SettingsManager $settings
) {}

$this->settings->get('site.name');
```

---

## Model Settings

Settings can be attached to any Eloquent model.

Add the trait:

```php
use Egough\LaravelSettings\Traits\HasSettings;

class User extends Model
{
    use HasSettings;
}
```

Usage:

```php
$user = User::find(1);

$user->settings()->set('ui.theme', 'dark');

$user->settings()->get('ui.theme');
```

Retrieve all settings:

```php
$user->settings()->all();
```

Remove a setting:

```php
$user->settings()->forget('ui.theme');
```

---

## Feature Flags

Feature flags are boolean settings.

```php
flag('billing.enabled');

Flag::enabled('billing.enabled');
```

With fallback:

```php
flag('beta.feature', false);
```

---

## Blade Directives

```blade
@hasSetting('site.name')
@endhasSetting

@hasFlag('billing.enabled')
@endhasFlag

<title>@setting('site.name')</title>
```

---

## Default Values

Define defaults in `config/settings.php`:

```php
'defaults' => [
    'site.name' => 'Laravel App',
];
```

Lookup order:

1. Database value
2. Config default
3. Provided fallback

---

## Caching

Settings are cached automatically.

Clear cache manually:

```bash
php artisan settings:clear-cache
```

---

## Artisan Commands

```bash
php artisan settings:get site.name
php artisan settings:set site.name "My App"
php artisan settings:clear-cache
```

---

## Requirements

* PHP 8.2+
* Laravel 11+

---

## License

MIT
