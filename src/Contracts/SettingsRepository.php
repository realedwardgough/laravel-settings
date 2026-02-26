<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Contracts;

interface SettingsRepository
{
    /** @return array<string, array{value: mixed, type: string}> */
    public function all(): array;

    public function set(string $key, mixed $value, ?string $type = null): void;

    public function forget(string $key): void;
}
