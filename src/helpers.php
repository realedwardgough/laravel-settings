<?php

use Egough\LaravelSettings\SettingsManager;

if (! function_exists('settings')) {
    function settings(): SettingsManager
    {
        return app(SettingsManager::class);
    }
}

if (! function_exists('flag')) {
    function flag(string $key, bool $default = false): bool
    {
        return settings()->flag($key, $default);
    }
}
