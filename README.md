# egough/laravel-settings

Database-backed application settings and feature flags for Laravel.

`egough/laravel-settings` provides a simple, typed, cacheable way to store dynamic application configuration outside of `.env` and static config files.

---

## Installation

Install via Composer:

```bash
composer require egough/laravel-settings
```

---

## Features

- Database-backed settings
- Typed values (string, int, float, bool, json)
- Automatic caching
- Config default fallback
- Feature flag helpers
- Artisan commands
- Laravel auto-discovery
- Publishable config & migrations

---

## Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=settings-config
```

Publish the migration:

```bash
php artisan vendor:publish --tag=settings-migrations
```

Run migrations:

```bash
php artisan migrate
```

---

## Basic Usage

### Setting Values

```php
settings()->set('site.name', 'My Application');

settings()->set('billing.enabled', true);

settings()->set('ui.options', [
    'dark_mode' => true,
]);
```

---

### Retrieving Values

```php
settings()->get('site.name');

settings()->get('unknown.key', 'fallback');
```

---

### Retrieve All Settings

```php
settings()->all();
```

---

## Feature Flags

Feature flags are simple boolean settings.

```php
flag('billing.enabled');
```

With fallback:

```php
flag('new.dashboard', false);
```

Example usage:

```php
if (flag('beta.feature')) {
    // Enable beta functionality
}
```

---

## Default Values

Define default settings inside:

```
config/settings.php
```

```php
'defaults' => [
    'site.name' => 'Laravel App',
    'billing.enabled' => false,
],
```

Lookup priority:

1. Database value
2. Config default
3. Provided fallback

---

## Caching

All settings are cached automatically for performance.

Cache behaviour can be configured:

```php
'cache' => [
    'enabled' => true,
    'key' => 'egough.settings.all',
    'ttl' => 3600,
],
```

Clear cache manually:

```bash
php artisan settings:clear-cache
```

---

## Artisan Commands

### Get a Setting

```bash
php artisan settings:get site.name
```

---

### Set a Setting

```bash
php artisan settings:set billing.enabled true --type=bool
```

Supported types:

* string
* int
* float
* bool
* json

---

### Clear Cached Settings

```bash
php artisan settings:clear-cache
```

---

## Requirements

* PHP 8.2+
* Laravel 11+

---

## Contributing

Contributions, issues, and feature requests are welcome.

Please open a Pull Request or Issue on GitHub.

---

## License

The MIT License (MIT).

---

## Author

**Edward Gough**

---
