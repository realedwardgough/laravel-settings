<?php

declare(strict_types=1);

namespace Egough\LaravelSettings;

use Egough\LaravelSettings\Console\ClearSettingsCacheCommand;
use Egough\LaravelSettings\Console\GetSettingCommand;
use Egough\LaravelSettings\Console\SetSettingCommand;
use Egough\LaravelSettings\Contracts\SettingsRepository;
use Egough\LaravelSettings\Repositories\DatabaseSettingsRepository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(path: __DIR__.'/../config/settings.php', key: 'settings');

        $this->app->bind(abstract: SettingsRepository::class, concrete: DatabaseSettingsRepository::class);

        $this->app->singleton(abstract: SettingsManager::class, concrete: function ($app) {
            return new SettingsManager(
                repo: $app->make(SettingsRepository::class),
                cache: $app['cache.store']
            );
        });

        $this->app->singleton(abstract: 'settings.flags', concrete: function ($app) {
            return new \Egough\LaravelSettings\Support\FlagManager(
                $app->make(\Egough\LaravelSettings\SettingsManager::class)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes(paths: [
            __DIR__.'/../config/settings.php' => config_path(path: 'settings.php'),
        ], groups: 'settings-config');

        $this->publishes(paths: [
            __DIR__.'/../database/migrations/create_settings_table.php.stub' => database_path(path: 'migrations/'.date(format: 'Y_m_d_His').'_create_settings_table.php'),
        ], groups: 'settings-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GetSettingCommand::class,
                SetSettingCommand::class,
                ClearSettingsCacheCommand::class,
            ]);
        }

        $this->registerBladeDirectives();

        $this->app->alias(abstract: \Egough\LaravelSettings\SettingsManager::class, alias: 'settings.manager');
    }

    protected function registerBladeDirectives(): void
    {
        /*
        |--------------------------------------------------------------------------
        | @hasSetting
        |--------------------------------------------------------------------------
        */

        Blade::if(name: 'hasSetting', callback: function (string $key) {
            return settings()->get($key) !== null;
        });

        /*
        |--------------------------------------------------------------------------
        | @hasFlag
        |--------------------------------------------------------------------------
        */

        Blade::if(name: 'hasFlag', callback: function (string $key, bool $default = false) {
            return flag($key, $default);
        });

        /*
        |--------------------------------------------------------------------------
        | @setting
        |--------------------------------------------------------------------------
        */

        Blade::directive(name: 'setting', handler: function ($expression) {
            return "<?php echo e(settings()->get($expression)); ?>";
        });
    }
}
