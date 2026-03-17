<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Support;

use Egough\LaravelSettings\Contracts\SettingsRepository;

class InMemorySettingsRepository implements SettingsRepository
{
    /** @var array<string, array{value: mixed, type: string}> */
    public array $items = [];

    public int $allCalls = 0;

    public function all(): array
    {
        $this->allCalls++;

        return $this->items;
    }

    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $this->items[$key] = [
            'type' => $type ?? 'string',
            'value' => $value,
        ];
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }
}
