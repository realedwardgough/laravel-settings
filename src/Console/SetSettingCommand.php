<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Console;

use Illuminate\Console\Command;
use Egough\LaravelSettings\SettingsManager;
use JsonException;

class SetSettingCommand extends Command
{
    protected $signature = '
        settings:set
        {key}
        {value}
        {--type= : string|int|float|bool|json}
    ';

    protected $description = 'Store or update a setting';

    /**
     * @param SettingsManager $settings
     * @return int
     * @throws JsonException
     */
    public function handle(SettingsManager $settings): int
    {
        $key = $this->argument('key');
        $value = $this->argument('value');
        $type = $this->option('type');

        $value = $this->castInput($value, $type);

        $settings->set($key, $value, $type);

        $this->info("Setting [$key] saved.");

        return self::SUCCESS;
    }

    /**
     * @param string $value
     * @param string|null $type
     * @return mixed
     * @throws JsonException
     */
    private function castInput(string $value, ?string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'json' => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }
}
