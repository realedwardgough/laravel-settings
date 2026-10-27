---
name: laravel-settings-development
description: Implement and test database-backed application settings, model preferences, feature flags, and setting event listeners using egough/laravel-settings. Use when working with its settings() helper, HasSettings trait, SettingsManager, or setting events.
---

# Laravel Settings Development

## Choose the setting scope

Use global settings for application-wide runtime options and model settings for user or team preferences. Keep secrets and configuration required during application boot in Laravel configuration or environment variables.

Before adding setup, check the application's existing `config/settings.php`, published migrations, and models using `Egough\LaravelSettings\Traits\HasSettings`. Publish only missing assets:

```bash
php artisan vendor:publish --tag=settings-config
php artisan vendor:publish --tag=settings-migrations
# Also required when using model settings:
php artisan vendor:publish --tag=settings-model-migrations
php artisan migrate
```

## Add a global setting

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
```

Pass native PHP values; the package infers their storage types. Explicit types are available through `set($key, $value, $type)` with `string`, `int`, `float`, `bool`, or `json`.

Lookup order is stored value, config default, then the supplied fallback. A stored `null` wins over both defaults. `all()` returns stored keys only; `has($key)` checks storage and `has($key, true)` also considers config defaults. Dotted keys do not create nested arrays.

## Add user or team preferences

Add the `Egough\LaravelSettings\Traits\HasSettings` trait to the application's existing Eloquent model. Use a persisted model with an integer primary key; the current implementation casts model IDs to integers.

```php
$user->settings()->set('ui.theme', 'dark');
$theme = $user->settings()->get('ui.theme', 'light');
$user->settings()->forget('ui.theme');
```

Missing model settings use only the supplied fallback. They do not inherit global values or `settings.defaults`. If the application needs inheritance, express it explicitly:

```php
$theme = $user->settings()->get('ui.theme', settings()->get('ui.theme', 'light'));
```

Prefer `$model->settings()` for scoped access. When injecting `Egough\LaravelSettings\ModelSettings\ModelSettingsManager` directly, call `for($model)` and use its returned manager; `for()` returns a scoped clone.

## Add a feature toggle

Store a boolean and read it with `flag('billing.enabled', false)` or `Egough\LaravelSettings\Facades\Flag::enabled('billing.enabled', false)`. Avoid the string `'false'`, which PHP casts to `true`.

```php
settings()->set('billing.enabled', true);
```

For command-line writes, use `php artisan settings:set billing.enabled false --type=bool` to preserve the boolean type.

```blade
@hasFlag('billing.enabled')
    <x-billing-panel />
@endhasFlag

@hasSetting('site.name')
    <title>@setting('site.name')</title>
@endhasSetting
```

These directives read global settings. `@setting` escapes output; `@hasSetting` checks whether the resolved value is non-null, including config defaults. For model-specific toggles, use the scoped manager's `get()` method and a boolean value.

## React to setting changes

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

Write through helpers, facades, or managers to preserve cache invalidation and events. With the built-in repositories, events dispatch after the settings database transaction commits and are discarded on rollback. Outside transactions, listeners run immediately after cache invalidation. Direct repository, Eloquent, and SQL writes bypass both behaviors.

Writes clear the affected cache automatically. For manual invalidation, use `settings()->clearCache()` for global values or `$model->settings()->clearCache()` for one model; `php artisan settings:clear-cache` clears only the global cache.

## Test the application behavior

Use the application's existing test framework and database setup, including the package migrations. Exercise manager APIs with the real database for settings integration tests:

- Verify native types round-trip, stored values override defaults, and `forget()` restores fallback behavior. Cover stored `null` separately from missing keys.
- Create two persisted owners and verify their preferences remain isolated, including from global settings. Check missing preferences use the supplied fallback.
- Prime the cache before a write, then verify the next read returns the new value.
- Assert creation, updates, and deletion emit the expected event payloads and owner. Check equal values and missing deletions emit nothing.
- For transaction-sensitive behavior, explicitly commit or roll back the settings connection and check delivery or suppression. An enclosing test transaction that never commits will defer after-commit events.

When testing listeners, dispatch real events; when testing only emission, use the application's event faking conventions. Avoid faking the dispatcher if the test needs to verify listener behavior or cache visibility inside a listener.
