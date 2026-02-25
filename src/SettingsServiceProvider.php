<?php

namespace Egough\LaravelSettings;

use Egough\LaravelSettings\Contracts\SettingsRepository;
use Egough\LaravelSettings\Repositories\DatabaseSettingsRepository;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/settings.php', 'settings');

        $this->app->bind(SettingsRepository::class, DatabaseSettingsRepository::class);

        $this->app->singleton(SettingsManager::class, function ($app) {
            return new SettingsManager(
                $app->make(SettingsRepository::class),
                $app['cache.store']
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/settings.php' => config_path('settings.php'),
        ], 'settings-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_settings_table.php.stub' =>
                database_path('migrations/'.date('Y_m_d_His').'_create_settings_table.php'),
        ], 'settings-migrations');

        if ($this->app->runningInConsole()) {
            // coming soon... commands
        }
    }
}
