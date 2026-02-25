<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Console;

use Illuminate\Console\Command;
use Egough\LaravelSettings\SettingsManager;

class GetSettingCommand extends Command
{
    protected $signature = 'settings:get {key}';
    protected $description = 'Retrieve a setting value';

    public function handle(SettingsManager $settings): int
    {
        $key = $this->argument('key');

        $value = $settings->get($key);

        if ($value === null) {
            $this->warn("Setting [$key] not found.");
            return self::FAILURE;
        }

        if (is_array($value)) {
            $this->line(json_encode($value, JSON_PRETTY_PRINT));
        } else {
            $this->line((string) $value);
        }

        return self::SUCCESS;
    }
}
