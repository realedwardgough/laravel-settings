---
name: laravel-settings-development
description: Implement and test database-backed application settings, model preferences, feature flags, and setting event listeners using egough/laravel-settings. Use when working with its settings() helper, HasSettings trait, SettingsManager, or setting events.
---

# Laravel Settings Development

## When to use this skill

Use this skill when implementing or testing dynamic configuration, user or team preferences, feature toggles, Blade setting output, or change listeners with `egough/laravel-settings`.

## Core concepts

- **Global settings and model settings are separate scopes.** Model settings do not automatically inherit global values or config defaults.
- Global reads resolve stored value, then `settings.defaults`, then the supplied fallback. A stored `null` wins over both defaults.
- Dotted keys such as `site.name` are literal keys, not nested array paths.
- Write through helpers, facades, or managers to preserve automatic cache invalidation and setting events.
- Pass native PHP values. Feature flags use PHP boolean casting, so the string `'false'` is truthy.
- Keep secrets and configuration required during application boot in Laravel configuration or environment variables.

## Setup

Before adding setup, check the application's existing `config/settings.php`, published migrations, and models using `Egough\LaravelSettings\Traits\HasSettings`. Publish only missing assets:

```bash
php artisan vendor:publish --tag=settings-config
php artisan vendor:publish --tag=settings-migrations
# Also required when using model settings:
php artisan vendor:publish --tag=settings-model-migrations
php artisan migrate
```

## Global settings and defaults

Define defaults as literal dotted keys in the `defaults` array in `config/settings.php`:

```php
'defaults' => [
    'site.name' => 'My Application',
    'billing.enabled' => false,
],
```

Use `settings()`, `Egough\LaravelSettings\Facades\Settings`, or an injected `Egough\LaravelSettings\SettingsManager` to read and write:

```php
settings()->set('site.name', 'Customer Portal');
$name = settings()->get('site.name', 'Fallback name');

settings()->set('notifications.channels', ['mail', 'database']);
$channels = settings()->get('notifications.channels', []);

settings()->forget('site.name'); // Restores lookup of the config default.

$stored = settings()->all(); // Stored values only; excludes config defaults.
$exists = settings()->has('site.name'); // Includes stored null values.
$existsOrDefault = settings()->has('site.name', true);
```

The package infers storage types. Explicit types are available through `set($key, $value, $type)` with `string`, `int`, `float`, `bool`, or `json`:

```php
settings()->set('uploads.limit', '10', 'int');
$limit = settings()->get('uploads.limit'); // int: 10
```

## Facades and dependency injection

The global helper, facade, and injected manager resolve the same settings service:

```php
use Egough\LaravelSettings\Facades\Settings;
use Egough\LaravelSettings\SettingsManager;

Settings::set('site.name', 'Customer Portal');
$name = Settings::get('site.name');

class SiteBranding
{
    public function __construct(private SettingsManager $settings) {}

    public function name(): string
    {
        return (string) $this->settings->get('site.name', 'My Application');
    }
}
```

## Model settings

Add the `Egough\LaravelSettings\Traits\HasSettings` trait to the application's existing Eloquent model. Use a persisted model with an integer primary key; the current implementation casts model IDs to integers.

```php
use Egough\LaravelSettings\Traits\HasSettings;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasSettings;
}
```

```php
$user = User::findOrFail($userId);
$user->settings()->set('ui.theme', 'dark');
$theme = $user->settings()->get('ui.theme', 'light');
$preferences = $user->settings()->all();
$user->settings()->forget('ui.theme');
```

Missing model settings use only the supplied fallback. They do not inherit global values or `settings.defaults`. If the application needs inheritance, express it explicitly:

```php
$theme = $user->settings()->get('ui.theme', settings()->get('ui.theme', 'light'));
```

Prefer `$model->settings()` for scoped access. When injecting `Egough\LaravelSettings\ModelSettings\ModelSettingsManager` directly, call `for($model)` and use its returned manager; `for()` returns a scoped clone.

## Feature flags

Store booleans and use the helper or facade for global flags:

```php
use Egough\LaravelSettings\Facades\Flag;

settings()->set('billing.enabled', true);
$enabled = flag('billing.enabled', false);
$enabled = Flag::enabled('billing.enabled', false);

// Model flags use the scoped manager and an explicit fallback.
$user->settings()->set('notifications.enabled', false);
$notificationsEnabled = (bool) $user->settings()->get('notifications.enabled', true);
```

## Blade directives

```blade
@hasFlag('billing.enabled')
    <x-billing-panel />
@endhasFlag

@hasSetting('site.name')
    <title>@setting('site.name')</title>
@endhasSetting
```

These directives read global settings. `@setting` escapes output; `@hasSetting` checks whether the resolved value is non-null, including config defaults. A stored `null` therefore satisfies `settings()->has($key)` but fails `@hasSetting($key)`.

## Events

Register listeners through the application's existing Laravel event registration convention. For example, in a service provider's `boot()` method:

```php
use Egough\LaravelSettings\Events\SettingUpdated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

Event::listen(SettingUpdated::class, function (SettingUpdated $event): void {
    if ($event->model === null && $event->key === 'billing.enabled') {
        Log::info('Billing availability changed', ['enabled' => $event->newValue]);
    }
});
```

`SettingCreated` exposes `key`, `newValue`, and `model`; `SettingUpdated` also exposes `oldValue`; `SettingDeleted` exposes `key`, `oldValue`, and `model`. Import them from `Egough\LaravelSettings\Events`. Values are decoded. The owner is `null` for global settings and the Eloquent model for model settings; filter both scope and key where needed.

Creation of a stored override emits `SettingCreated`, even if it matches a config default. Equal decoded values and deleting missing keys emit nothing. Listen to both creation and updates when reacting to any newly stored value.

With the built-in repositories, events dispatch after the settings database transaction commits and are discarded on rollback. Outside transactions, listeners run immediately after cache invalidation. Direct repository, Eloquent, and SQL writes bypass cache invalidation and events.

## Caching and Artisan commands

Writes clear the affected cache automatically. For manual invalidation, use `settings()->clearCache()` for global values or `$model->settings()->clearCache()` for one model; `php artisan settings:clear-cache` clears only the global cache.

Configure global caching under `settings.cache` and model caching under `settings.model.cache`. Each has `enabled` and `ttl`; a `null` TTL caches forever. Global cache uses `key`, while model cache uses `key_prefix` plus the owner identity.

The commands operate on global settings:

```bash
php artisan settings:get site.name
php artisan settings:set site.name "Customer Portal"
php artisan settings:set billing.enabled false --type=bool
php artisan settings:set uploads.limit 10 --type=int
php artisan settings:clear-cache
```

Specify `--type` for typed command-line writes; without it, command input is stored as a string.

## Testing

Use the application's existing test framework and database setup, including the package migrations. Exercise manager APIs with the real database for settings integration tests:

- Verify native types round-trip, stored values override defaults, and `forget()` restores fallback behavior. Cover stored `null` separately from missing keys.
- Create two persisted owners and verify their preferences remain isolated, including from global settings. Check missing preferences use the supplied fallback.
- Prime the cache before a write, then verify the next read returns the new value.
- Assert creation, updates, and deletion emit the expected event payloads and owner. Check equal values and missing deletions emit nothing.
- For transaction-sensitive behavior, explicitly commit or roll back the settings connection and check delivery or suppression. An enclosing test transaction that never commits will defer after-commit events.

When testing listeners, dispatch real events; when testing only emission, use the application's event faking conventions. Avoid faking the dispatcher if the test needs to verify listener behavior or cache visibility inside a listener.
