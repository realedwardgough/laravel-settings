<?php

namespace Egough\LaravelSettings\Console;

use Illuminate\Console\Command;
use Egough\LaravelSettings\SettingsManager;

class ClearSettingsCacheCommand extends Command
{
    protected $signature = 'settings:clear-cache';
    protected $description = 'Clear cached settings';

    /**
     * @param SettingsManager $settings
     * @return int
     */
    public function handle(SettingsManager $settings): int
    {
        $settings->clearCache();

        $this->info('Settings cache cleared.');

        return self::SUCCESS;
    }
}
