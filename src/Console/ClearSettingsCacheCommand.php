<?php

namespace Egough\LaravelSettings\Console;

use Egough\LaravelSettings\SettingsManager;
use Illuminate\Console\Command;

class ClearSettingsCacheCommand extends Command
{
    protected $signature = 'settings:clear-cache';

    protected $description = 'Clear cached settings';

    public function handle(SettingsManager $settings): int
    {
        $settings->clearCache();

        $this->info('Settings cache cleared.');

        return self::SUCCESS;
    }
}
