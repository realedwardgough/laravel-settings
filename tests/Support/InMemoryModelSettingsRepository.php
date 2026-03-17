<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Support;

use Egough\LaravelSettings\ModelSettings\ModelSettingsRepository;

class InMemoryModelSettingsRepository implements ModelSettingsRepository
{
    /** @var array<string, array<string, mixed>> */
    public array $items = [];

    public int $allCalls = 0;

    public function all(string $type, int $id): array
    {
        $this->allCalls++;

        return $this->items[$this->scopeKey($type, $id)] ?? [];
    }

    public function set(string $type, int $id, string $key, mixed $value, ?string $valueType = null): void
    {
        $this->items[$this->scopeKey($type, $id)][$key] = $value;
    }

    public function forget(string $type, int $id, string $key): void
    {
        unset($this->items[$this->scopeKey($type, $id)][$key]);
    }

    private function scopeKey(string $type, int $id): string
    {
        return $type.':'.$id;
    }
}
