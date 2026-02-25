<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool enabled(string $key, bool $default = false)
 *
 * @see \Egough\LaravelSettings\Support\FlagManager
 */
class Flag extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'settings.flags';
    }
}
