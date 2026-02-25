<?php

namespace Egough\LaravelSettings\Repositories;

use Egough\LaravelSettings\Contracts\SettingsRepository;
use Egough\LaravelSettings\Models\Setting;
use Egough\LaravelSettings\Support\Caster;

class DatabaseSettingsRepository implements SettingsRepository
{
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

    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $type ??= Caster::detectType($value);

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['type' => $type, 'value' => Caster::encode($type, $value)]
        );
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();
    }
}
