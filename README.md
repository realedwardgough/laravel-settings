
![alt text](/resources/banner.png "Laravel Settings")

[![Latest Version on Packagist](https://img.shields.io/packagist/v/egough/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/egough/laravel-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/egough/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/egough/laravel-settings)
[![License](https://img.shields.io/packagist/l/egough/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/egough/laravel-settings)
[![Tests](https://github.com/realedwardgough/laravel-settings/actions/workflows/tests.yml/badge.svg)](https://github.com/realedwardgough/laravel-settings/actions/workflows/tests.yml)

---

### Add global and per-model settings for your Laravel application.

Settings are stored in the database, automatically cached for performance, and exposed through a clean, expressive API including a global helper, a facade, Blade directives, and support for dependency injection.

Provides a simple, typed and cacheable way to store dynamic configuration outside of `.env` and static config files, including per-model settings such as user or team preferences.

[View the documentation](https://github.com/realedwardgough/laravel-settings/blob/main/docs/documentation.md)

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

Adding to your model:

```php
use Egough\LaravelSettings\Traits\HasSettings;

class User extends Authenticatable
{
    use HasSettings;
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

## Requirements

* PHP 8.2+
* Laravel 10, 11, 12, or 13

---

## License

MIT
