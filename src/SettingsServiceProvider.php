<?php

declare(strict_types=1);

namespace Egough\LaravelSettings;

use Egough\LaravelSettings\Console\ClearSettingsCacheCommand;
use Egough\LaravelSettings\Console\GetSettingCommand;
use Egough\LaravelSettings\Console\SetSettingCommand;
use Egough\LaravelSettings\Contracts\SettingsRepository;
use Egough\LaravelSettings\ModelSettings\DatabaseModelSettingsRepository;
use Egough\LaravelSettings\ModelSettings\ModelSettingsManager;
use Egough\LaravelSettings\ModelSettings\ModelSettingsRepository;
use Egough\LaravelSettings\Repositories\DatabaseSettingsRepository;
use Egough\LaravelSettings\Support\FlagManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Config
        $this->mergeConfigFrom(path: __DIR__.'/../config/settings.php', key: 'settings');

        // Global settings repository
        $this->app->bind(abstract: SettingsRepository::class, concrete: DatabaseSettingsRepository::class);

        // Global settings manager
        $this->app->singleton(abstract: SettingsManager::class, concrete: function ($app) {
            return new SettingsManager(
                repo: $app->make(SettingsRepository::class),
                cache: $app['cache.store'],
            );
        });

        // Facade accessor
        $this->app->alias(abstract: SettingsManager::class, alias: 'settings.manager');

        // Feature flags helper manager (used by Flag facade)
        $this->app->singleton(abstract: 'settings.flags', concrete: function ($app) {
            return new FlagManager(
                $app->make(SettingsManager::class),
            );
        });

        // Model-scoped settings
        $this->app->bind(abstract: ModelSettingsRepository::class, concrete: DatabaseModelSettingsRepository::class);

        $this->app->singleton(abstract: ModelSettingsManager::class, concrete: function ($app) {
            return new ModelSettingsManager(
                $app->make(ModelSettingsRepository::class),
                $app['cache.store'],
            );
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes(paths: [
            __DIR__.'/../config/settings.php' => config_path(path: 'settings.php'),
        ], groups: 'settings-config');

        // Publish migrations (global)
        $this->publishes(paths: [
            __DIR__.'/../database/migrations/create_settings_table.php.stub' =>
                database_path(path: 'migrations/'.date(format: 'Y_m_d_His').'_create_settings_table.php'),
        ], groups: 'settings-migrations');

        // Publish migrations (model-scoped)
        $this->publishes(paths: [
            __DIR__.'/../database/migrations/create_model_settings_table.php.stub' =>
                database_path(path: 'migrations/'.date(format: 'Y_m_d_His').'_create_model_settings_table.php'),
        ], groups: 'settings-model-migrations');

        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GetSettingCommand::class,
                SetSettingCommand::class,
                ClearSettingsCacheCommand::class,
            ]);
        }

        // Blade directives
        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives(): void
    {
        Blade::if(name: 'hasSetting', callback: function (string $key): bool {
            return settings()->get($key) !== null;
        });

        Blade::if(name: 'hasFlag', callback: function (string $key, bool $default = false): bool {
            return flag($key, $default);
        });

        Blade::directive(name: 'setting', handler: function ($expression): string {
            return "<?php echo e(settings()->get($expression)); ?>";
        });
    }
}
