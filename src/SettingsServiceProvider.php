<?php

declare(strict_types=1);

namespace Egough\LaravelSettings;

use Egough\LaravelSettings\Contracts\SettingsRepository;
use Egough\LaravelSettings\Repositories\DatabaseSettingsRepository;
use Egough\LaravelSettings\Console\GetSettingCommand;
use Egough\LaravelSettings\Console\SetSettingCommand;
use Egough\LaravelSettings\Console\ClearSettingsCacheCommand;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(path: __DIR__ . '/../config/settings.php', key: 'settings');

        $this->app->bind(abstract: SettingsRepository::class, concrete: DatabaseSettingsRepository::class);

        $this->app->singleton(abstract: SettingsManager::class, concrete: function ($app) {
            return new SettingsManager(
                repo: $app->make(SettingsRepository::class),
                cache: $app['cache.store']
            );
        });
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->publishes(paths: [
            __DIR__ . '/../config/settings.php' => config_path(path: 'settings.php'),
        ], groups: 'settings-config');

        $this->publishes(paths: [
            __DIR__.'/../database/migrations/create_settings_table.php.stub' =>
                database_path(path: 'migrations/' . date(format: 'Y_m_d_His') . '_create_settings_table.php'),
        ], groups: 'settings-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GetSettingCommand::class,
                SetSettingCommand::class,
                ClearSettingsCacheCommand::class,
            ]);
        }
    }
}
