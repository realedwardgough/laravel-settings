<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Support;

use Egough\LaravelSettings\SettingsManager;

readonly class FlagManager
{
    public function __construct(private SettingsManager $settings) {}

    public function enabled(string $key, bool $default = false): bool
    {
        return $this->settings->flag($key, $default);
    }
}
