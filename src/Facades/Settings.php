<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value, ?string $type = null)
 * @method static void forget(string $key)
 * @method static array all()
 * @method static void clearCache()
 * @method static bool flag(string $key, bool $default = false)
 *
 * @see \Egough\LaravelSettings\SettingsManager
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'settings.manager';
    }
}
