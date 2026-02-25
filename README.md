# egough/laravel-settings

Database-backed application settings and feature flags for Laravel.

`egough/laravel-settings` provides a simple, typed, and cacheable way to store dynamic application configuration outside of `.env` files and static config values.


---

## ✨ Features

* ✅ Database-backed settings
* ✅ Typed values (`string`, `int`, `float`, `bool`, `json`)
* ✅ Automatic caching
* ✅ Config default fallback
* ✅ Helper functions
* ✅ Facade support
* ✅ Feature flags
* ✅ Artisan commands
* ✅ Laravel auto-discovery
* ✅ Publishable config & migrations

---

## 📦 Installation

Install via Composer:

```bash
composer require egough/laravel-settings
```

---

## ⚙️ Publish Configuration

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

## 🚀 Usage

The package provides **three ways** to interact with settings:

* Helper functions
* Facades
* Dependency Injection

---

## Helper Usage

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

## Facade Usage

Facades are automatically registered via Laravel package discovery.

```php
use Settings;

Settings::set('site.name', 'My Application');

Settings::get('site.name');

Settings::all();
```

Feature flags via facade:

```php
use Flag;

Flag::enabled('billing.enabled');
```

---

## Dependency Injection

You may inject the manager directly:

```php
use Egough\LaravelSettings\SettingsManager;

public function __construct(
    private SettingsManager $settings
) {}

$this->settings->get('site.name');
```

---

## 🚩 Feature Flags

Feature flags are boolean-backed settings.

### Helper

```php
flag('billing.enabled');
```

### Facade

```php
Flag::enabled('new.dashboard');
```

With fallback:

```php
flag('beta.feature', false);
```

Example:

```php
if (flag('beta.feature')) {
    // Enable beta functionality
}
```

---

## 🧠 Default Values

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

## ⚡ Caching

All settings are cached automatically for performance.

Configure caching:

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

## 🛠 Artisan Commands

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

## 🧪 Requirements

* PHP 8.2+
* Laravel 11+

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome.

Please open a Pull Request or Issue on GitHub.

---

## 📄 License

The MIT License (MIT).

---

## 👤 Author

**Edward Gough**
