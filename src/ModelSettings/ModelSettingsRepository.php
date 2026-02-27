<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\ModelSettings;

interface ModelSettingsRepository
{
    /**
     * @param string $type
     * @param int $id
     * @return array
     */
    public function all(string $type, int $id): array;

    /**
     * @param string $type
     * @param int $id
     * @param string $key
     * @param mixed $value
     * @param string|null $valueType
     * @return void
     */
    public function set(string $type, int $id, string $key, mixed $value, ?string $valueType = null): void;

    /**
     * @param string $type
     * @param int $id
     * @param string $key
     * @return void
     */
    public function forget(string $type, int $id, string $key): void;
}
