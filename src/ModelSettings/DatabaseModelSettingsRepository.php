<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\ModelSettings;

use Egough\LaravelSettings\ModelSettings\Models\ModelSetting;
use Egough\LaravelSettings\Support\Caster;

class DatabaseModelSettingsRepository implements ModelSettingsRepository
{
    /**
     * @param string $type
     * @param int $id
     * @return array
     * @throws \JsonException
     */
    public function all(string $type, int $id): array
    {
        return ModelSetting::query()
            ->where('settingable_type', $type)
            ->where('settingable_id', $id)
            ->get(['key', 'value', 'type'])
            ->mapWithKeys(fn (ModelSetting $s) => [
                $s->key => Caster::decode($s->type, $s->value),
            ])
            ->all();
    }

    /**
     * @param string $type
     * @param int $id
     * @param string $key
     * @param mixed $value
     * @param string|null $valueType
     * @return void
     * @throws \JsonException
     */
    public function set(string $type, int $id, string $key, mixed $value, ?string $valueType = null): void
    {
        $valueType ??= Caster::detectType($value);

        ModelSetting::query()->updateOrCreate(
            [
                'settingable_type' => $type,
                'settingable_id' => $id,
                'key' => $key,
            ],
            [
                'type' => $valueType,
                'value' => Caster::encode($valueType, $value),
            ],
        );
    }

    /**
     * @param string $type
     * @param int $id
     * @param string $key
     * @return void
     */
    public function forget(string $type, int $id, string $key): void
    {
        ModelSetting::query()
            ->where('settingable_type', $type)
            ->where('settingable_id', $id)
            ->where('key', $key)
            ->delete();
    }
}
