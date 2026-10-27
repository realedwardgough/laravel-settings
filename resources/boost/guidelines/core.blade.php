@verbatim
## Laravel Settings (egough/laravel-settings)

This package stores dynamic application and Eloquent model settings in the database. Use it for runtime preferences and feature toggles; keep secrets, infrastructure configuration, and values needed during application boot in Laravel configuration or environment variables.

### Setup

Publish configuration and global settings migrations, then migrate. Publish model settings migrations only when using model settings:

```bash
php artisan vendor:publish --tag=settings-config
php artisan vendor:publish --tag=settings-migrations
# Optional: required for model settings
php artisan vendor:publish --tag=settings-model-migrations
php artisan migrate
```

### Application settings

Use the `settings()` helper, `Egough\LaravelSettings\Facades\Settings` facade, or inject `Egough\LaravelSettings\SettingsManager`.

```php
settings()->set('site.name', 'My Application');
settings()->get('site.name', 'Fallback name');
settings()->set('billing.enabled', true);
settings()->set('notifications.channels', ['mail', 'database']);
settings()->forget('site.name');
```

- Types are inferred from PHP values; pass actual booleans for feature flags.
- Global lookup order is stored database value, `config('settings.defaults')` entry, then the fallback passed to `get()`. A stored `null` takes precedence over defaults.
- Dotted setting keys such as `site.name` are literal keys, not nested array paths. Define defaults as `['site.name' => 'My Application']`.
- `all()` returns stored settings only. `has($key)` checks stored keys; `has($key, true)` also checks config defaults.

### Model settings

Add `Egough\LaravelSettings\Traits\HasSettings` to the Eloquent model. Use a persisted model with an integer primary key:

```php
use Egough\LaravelSettings\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasSettings;
}

$team = Team::findOrFail($id);
$team->settings()->set('ui.theme', 'dark');
$team->settings()->get('ui.theme', 'light');
$team->settings()->all();
$team->settings()->forget('ui.theme');
```

Model settings are scoped to the owning model. Missing keys use the supplied fallback; they do not inherit global settings or `settings.defaults`.

### Feature flags and Blade

`flag('billing.enabled', false)` reads a global setting as a boolean. The facade equivalent is `Egough\LaravelSettings\Facades\Flag::enabled('billing.enabled', false)`.

```blade
@hasFlag('billing.enabled')
    <x-billing-panel />
@endhasFlag

@hasSetting('site.name')
    <title>@setting('site.name')</title>
@endhasSetting
```

`@setting` escapes its output. `@hasSetting` checks whether the resolved global value is non-null, including config defaults.

### Writes, caching, and events

- Write through the helpers, facades, or managers so cache invalidation and setting events run. Direct repository, Eloquent, or SQL writes bypass these behaviors.
- Writes automatically invalidate the affected settings cache. Use `settings()->clearCache()`, `$model->settings()->clearCache()`, or `php artisan settings:clear-cache` (global cache) when manual invalidation is needed.
- Listen for `Egough\LaravelSettings\Events\SettingCreated`, `SettingUpdated`, and `SettingDeleted`. Their `model` property is the owner for model settings and `null` for global settings. Values are decoded; unchanged values and deleting missing keys emit no event.
- With the built-in repositories, events dispatch after the settings database transaction commits and are discarded on rollback. Outside transactions they dispatch after cache invalidation.
- Available global commands: `php artisan settings:get site.name`, `php artisan settings:set site.name "My App"`, and `php artisan settings:set billing.enabled true --type=bool`.
@endverbatim
