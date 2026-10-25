## Contents

- [Introduction](#introduction)
- [When To Use This Package](#when-to-use-this-package)
- [Installation](#installation)
- [Configuration](#configuration)
- [Application Settings](#application-settings)
    - [Setting a Value](#setting-a-value)
    - [Retrieving a Value](#retrieving-a-value)
    - [Removing a Setting](#removing-a-setting)
    - [Retrieving All Settings](#retrieving-all-settings)
- [Model Settings](#model-settings)
    - [Preparing Your Model](#preparing-your-model)
    - [Setting a Value](#setting-a-model-value)
    - [Retrieving a Value](#retrieving-a-model-value)
    - [Retrieving All Settings](#retrieving-all-model-settings)
    - [Removing a Setting](#removing-a-model-setting)
- [Feature Flags](#feature-flags)
    - [Checking a Flag](#checking-a-flag)
    - [Using the Facade](#using-the-flag-facade)
- [Blade Directives](#blade-directives)
- [Default Values](#default-values)
- [Caching](#caching)
- [Artisan Commands](#artisan-commands)
- [Dependency Injection](#dependency-injection)
- [Testing](#testing)

---

<a name="introduction"></a>
## Introduction

Laravel Settings provides a simple, typed, and cacheable way to store dynamic application configuration in your database. Rather than hardcoding values in `.env` files or static `config/` files, Laravel Settings allows you to manage runtime configuration — such as feature flags, site preferences, and per-model options — from within your application itself.

The package supports both **application-level settings** (global configuration shared across your entire application) and **model-level settings** (per-model configuration, such as user preferences or team options). Settings are stored in the database, automatically cached for performance, and exposed through a clean, expressive API including a global helper, a facade, Blade directives, and support for dependency injection.

```php
// Store and retrieve a global application setting
settings()->set('site.name', 'My Application');
settings()->get('site.name');

// Store and retrieve a setting on any Eloquent model
$user->settings()->set('ui.theme', 'dark');
$user->settings()->get('ui.theme');

// Check whether a feature flag is enabled
if (flag('billing.enabled')) {
    // Charge the customer
}
```

---

<a name="when-to-use-this-package"></a>
## When To Use This Package

This package is designed for configuration that needs to change at runtime — without modifying environment variables or redeploying your application. Common use cases include:

**Application-level settings:**
- Site name, logo URL, or branding values
- Feature toggles controlled by an admin panel
- Maintenance mode flags or runtime switches
- Configurable thresholds or limits

**Model-level settings:**
- User preferences such as notification settings, UI theme, or dashboard layout
- Team or organisation-specific configuration
- Per-project or per-resource metadata
- Account-level feature overrides

**When not to use this package:**

You should continue using `.env` files and `config/*.php` for values that are environment-specific, secret, or required before the application fully boots:

| Storage | Use For |
|---|---|
| `.env` | Database credentials, API keys, secrets, environment-specific infrastructure |
| `config/*.php` | Static application configuration, values needed at boot time |
| `egough/laravel-settings` | Runtime, database-driven configuration that changes without redeployment |

> **Note:** Settings stored in this package are loaded from the database (and cached). They are not suitable for values required during the service container boot cycle, such as queue connection credentials or logging configuration.

---

<a name="installation"></a>
## Installation

Install the package via Composer:

```bash
composer require egough/laravel-settings:^1.0
```

### Publishing Assets

Publish the configuration file:

```bash
php artisan vendor:publish --tag=settings-config
```

Publish the migrations for application settings:

```bash
php artisan vendor:publish --tag=settings-migrations
```

If you plan to use model-level settings, also publish the model settings migration:

```bash
php artisan vendor:publish --tag=settings-model-migrations
```

Run your migrations:

```bash
php artisan migrate
```

This will create the necessary database tables for storing your settings.

---

<a name="configuration"></a>
## Configuration

After publishing, the configuration file will be located at `config/settings.php`. This file allows you to define default values for your settings, which act as fallbacks when a setting has not yet been stored in the database.

```php
return [

    'defaults' => [
        'site.name' => 'Laravel App',
        'site.logo' => null,
        'billing.enabled' => false,
    ],

];
```

### Value Resolution Order

When you retrieve a setting, the package resolves the value in the following order:

1. **Database value** — A value explicitly stored in the database takes highest priority.
2. **Config default** — If no database value exists, the value defined in `config/settings.php` under `defaults` is used.
3. **Fallback argument** — If neither of the above exists, the fallback you pass directly to `get()` is returned.

This means you can always provide a sensible default without ever throwing exceptions for missing values.

---

<a name="application-settings"></a>
## Application Settings

Application settings are global and accessible from anywhere in your application — controllers, jobs, services, commands, and views.

<a name="setting-a-value"></a>
### Setting a Value

Use the `settings()` helper to store a value:

```php
settings()->set('site.name', 'My Application');
settings()->set('billing.enabled', true);
settings()->set('features.max_uploads', 10);
```

You may also use dot notation to organise your settings into logical groups, which helps keep your keys readable and consistent.

<a name="retrieving-a-value"></a>
### Retrieving a Value

Retrieve a stored value using the `get` method:

```php
$name = settings()->get('site.name');
```

You may pass a default value as the second argument. This value is returned if no database value and no config default exists for the key:

```php
$theme = settings()->get('ui.theme', 'light');
```

<a name="removing-a-setting"></a>
### Removing a Setting

To remove a setting from the database entirely:

```php
settings()->forget('site.name');
```

Once forgotten, subsequent calls to `get` will fall back to the config default or provided fallback.

<a name="retrieving-all-settings"></a>
### Retrieving All Settings

To retrieve all stored application settings:

```php
$all = settings()->all();
```

This returns a collection of all settings currently stored in the database.

---

<a name="model-settings"></a>
## Model Settings

In addition to application-wide settings, you can attach settings to any Eloquent model. This is ideal for storing per-user preferences, per-team configuration, or any per-resource metadata.

<a name="preparing-your-model"></a>
### Preparing Your Model

Add the `HasSettings` trait to any Eloquent model:

```php
use Egough\LaravelSettings\Traits\HasSettings;

class User extends Authenticatable
{
    use HasSettings;
}
```

You can add this trait to as many models as you need:

```php
class Team extends Model
{
    use HasSettings;
}

class Project extends Model
{
    use HasSettings;
}
```

> **Note:** Ensure you have published and run the model settings migration before using this feature.

<a name="setting-a-model-value"></a>
### Setting a Model Value

Access the model's settings via the `settings()` method:

```php
$user = User::find(1);

$user->settings()->set('ui.theme', 'dark');
$user->settings()->set('notifications.email', true);
$user->settings()->set('dashboard.layout', 'grid');
```

Settings are scoped to the individual model instance, so two different users will have completely independent settings.

```php
$alice = User::find(1);
$bob   = User::find(2);

$alice->settings()->set('ui.theme', 'dark');
$bob->settings()->set('ui.theme', 'light');

$alice->settings()->get('ui.theme'); // 'dark'
$bob->settings()->get('ui.theme');   // 'light'
```

<a name="retrieving-a-model-value"></a>
### Retrieving a Model Value

```php
$theme = $user->settings()->get('ui.theme');
```

You may provide a fallback as the second argument:

```php
$layout = $user->settings()->get('dashboard.layout', 'list');
```

<a name="retrieving-all-model-settings"></a>
### Retrieving All Model Settings

Retrieve all settings stored for a given model instance:

```php
$preferences = $user->settings()->all();
```

<a name="removing-a-model-setting"></a>
### Removing a Model Setting

Remove a specific setting from the model:

```php
$user->settings()->forget('ui.theme');
```

---

<a name="feature-flags"></a>
## Feature Flags

Feature flags are boolean settings used to toggle functionality on or off at runtime. They allow you to ship incomplete features safely, roll out functionality progressively, or let administrators control what's available in the application without code changes.

<a name="checking-a-flag"></a>
### Checking a Flag

Use the global `flag()` helper:

```php
if (flag('billing.enabled')) {
    // The billing feature is active
}
```

You may provide a default fallback as the second argument:

```php
if (flag('beta.new_dashboard', false)) {
    return view('dashboard.beta');
}

return view('dashboard.stable');
```

Feature flags are backed by the same settings storage as regular settings, but are always cast to a boolean for convenience.

<a name="using-a-flag-facade"></a>
### Using the Flag Facade

You may also use the `Flag` facade:

```php
use Flag;

if (Flag::enabled('billing.enabled')) {
    // Feature is enabled
}
```

---

<a name="blade-directives"></a>
## Blade Directives

Laravel Settings provides a set of Blade directives so you can conditionally render content in your views based on settings and feature flags without cluttering your templates with verbose PHP.

### `@setting`

Render a setting value directly in a template:

```blade
<title>@setting('site.name')</title>

<meta name="description" content="@setting('site.description')">
```

### `@hasSetting`

Conditionally render content if a setting exists and has a value:

```blade
@hasSetting('site.announcement')
    <div class="banner">
        @setting('site.announcement')
    </div>
@endhasSetting
```

### `@hasFlag`

Conditionally render content based on a feature flag:

```blade
@hasFlag('billing.enabled')
    <x-billing-panel />
@endhasFlag

@hasFlag('beta.new_dashboard')
    @include('partials.beta-notice')
@endhasFlag
```

These directives keep your Blade templates expressive and easy to read, and they respect the same value resolution order as the PHP API.

---

<a name="default-values"></a>
## Default Values

Default values provide a baseline for your settings before any values are saved to the database. They are defined in `config/settings.php` under the `defaults` key:

```php
'defaults' => [
    'site.name'              => 'Laravel App',
    'site.locale'            => 'en',
    'billing.enabled'        => false,
    'ui.theme'               => 'light',
    'features.max_uploads'   => 25,
],
```

Defaults are returned transparently — calling `settings()->get('site.name')` will return `'Laravel App'` even if the key has never been written to the database.

This means you can safely call `get()` throughout your application without worrying about null values for settings that haven't been explicitly configured yet.

---

<a name="caching"></a>
## Caching

Settings are automatically cached to avoid repeated database queries on every request. This means your application will only hit the database for settings once, and subsequent reads within the same request lifecycle are served from cache.

<a name="clearing-the-cache"></a>
### Clearing the Cache

If you update settings directly in the database, or need to force fresh values to be loaded, you can clear the settings cache using the provided Artisan command:

```bash
php artisan settings:clear-cache
```

You may wish to call this command as part of your deployment pipeline if you manage settings via database seeding or migrations.

> **Note:** When you set or forget a setting through the package's API (via the helper, facade, or model), the cache is automatically invalidated for you. Manual cache clearing is only necessary when you bypass the package's API and write directly to the database.

---

<a name="artisan-commands"></a>
## Artisan Commands

Laravel Settings includes a set of Artisan commands for managing settings from the command line, which is particularly useful in deployment scripts, seeding workflows, or quick debugging.

### Retrieving a Setting

```bash
php artisan settings:get site.name
```

### Setting a Value

```bash
php artisan settings:set site.name "My Application"
php artisan settings:set billing.enabled true
```

### Clearing the Cache

```bash
php artisan settings:clear-cache
```

---

## Dependency Injection

For applications that favour dependency injection over facades and global helpers, you may type-hint the `SettingsManager` class directly in any constructor or method resolved by the service container:

```php
use Egough\LaravelSettings\SettingsManager;

class SiteConfigurationController extends Controller
{
    public function __construct(
        private readonly SettingsManager $settings,
    ) {}

    public function show(): array
    {
        return [
            'name'   => $this->settings->get('site.name'),
            'locale' => $this->settings->get('site.locale', 'en'),
        ];
    }

    public function update(Request $request): void
    {
        $this->settings->set('site.name', $request->input('name'));
    }
}
```

The `SettingsManager` is bound as a singleton in the service container, so the same instance — and therefore the same in-memory cache — is shared throughout the lifetime of a single request.

---

<a name="testing"></a>
## Testing

The package ships with a full test suite using an in-memory SQLite database, meaning no external database is required to run tests.

### Running the Tests

Install the development dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test
```

Or run PHPUnit directly:

```bash
vendor/bin/phpunit
```

### Testing Your Own Application

When writing tests for code that reads or writes settings, you can use the `settings()` helper or the `SettingsManager` directly within your test to set up state:

```php
it('shows the correct site name in the header', function () {
    settings()->set('site.name', 'Test App');

    $response = $this->get('/');

    $response->assertSee('Test App');
});
```

For feature flag tests:

```php
it('shows the billing panel when billing is enabled', function () {
    settings()->set('billing.enabled', true);

    $response = $this->actingAs($this->user)->get('/dashboard');

    $response->assertSeeLivewire('billing-panel');
});

it('hides the billing panel when billing is disabled', function () {
    settings()->set('billing.enabled', false);

    $response = $this->actingAs($this->user)->get('/dashboard');

    $response->assertDontSeeLivewire('billing-panel');
});
```

Because settings are stored in the database, they will be reset between tests as long as you use the `RefreshDatabase` trait in your test cases.

---

<a name="requirements"></a>
## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 10, 11, 12, or 13 |

---

<a name="license"></a>
## License

Laravel Settings is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).