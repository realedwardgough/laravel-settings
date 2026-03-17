<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\ModelSettings;

interface ModelSettingsRepository
{
    public function all(string $type, int $id): array;

    public function set(string $type, int $id, string $key, mixed $value, ?string $valueType = null): void;

    public function forget(string $type, int $id, string $key): void;
}
