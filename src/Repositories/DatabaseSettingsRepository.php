<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Repositories;

use Egough\LaravelSettings\Contracts\SettingsRepository;
use Egough\LaravelSettings\Models\Setting;
use Egough\LaravelSettings\Support\Caster;

class DatabaseSettingsRepository implements SettingsRepository
{
    /**
     * @return array|array[]
     */
    public function all(): array
    {
        return Setting::query()
            ->get(['key', 'value', 'type'])
            ->mapWithKeys(function (Setting $s) {
                return [
                    $s->key => [
                        'type' => $s->type,
                        'value' => Caster::decode($s->type, $s->value),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @return void
     * @throws \JsonException
     */
    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $type ??= Caster::detectType($value);

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['type' => $type, 'value' => Caster::encode($type, $value)]
        );
    }

    /**
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();
    }
}
