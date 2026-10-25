<?php

declare(strict_types=1);

namespace Egough\LaravelSettings;

use Egough\LaravelSettings\Contracts\SettingsRepository;
use Egough\LaravelSettings\Events\SettingCreated;
use Egough\LaravelSettings\Events\SettingDeleted;
use Egough\LaravelSettings\Events\SettingUpdated;
use Egough\LaravelSettings\Support\DispatchesSettingEvents;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;

readonly class SettingsManager
{
    use DispatchesSettingEvents;

    public function __construct(
        private SettingsRepository $repo,
        private Cache $cache,
        private ?Dispatcher $events = null,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists(key: $key, array: $all)) {
            return $all[$key];
        }

        $configDefaults = config(key: 'settings.defaults', default: []);
        if (array_key_exists(key: $key, array: $configDefaults)) {
            return $configDefaults[$key];
        }

        return $default;
    }

    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $before = $this->repo->all();
        $this->repo->set(key: $key, value: $value, type: $type);
        $this->clearCache();
        $after = $this->repo->all();

        if (! array_key_exists($key, $after)) {
            return;
        }

        if (! array_key_exists($key, $before)) {
            $this->dispatchSettingEvent(new SettingCreated($key, $after[$key]['value']));
        } elseif ($before[$key]['value'] !== $after[$key]['value']) {
            $this->dispatchSettingEvent(new SettingUpdated($key, $before[$key]['value'], $after[$key]['value']));
        }
    }

    public function forget(string $key): void
    {
        $before = $this->repo->all();
        $this->repo->forget(key: $key);
        $this->clearCache();
        $after = $this->repo->all();

        if (array_key_exists($key, $before) && ! array_key_exists($key, $after)) {
            $this->dispatchSettingEvent(new SettingDeleted($key, $before[$key]['value']));
        }
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $cacheEnabled = (bool) config(key: 'settings.cache.enabled', default: true);
        $cacheKey = (string) config(key: 'settings.cache.key', default: 'gough.settings.all');
        $ttl = config(key: 'settings.cache.ttl', default: 3600);

        $load = function () {
            $raw = $this->repo->all();

            return collect(value: $raw)->map(callback: fn ($row) => $row['value'])->all();
        };

        if (! $cacheEnabled) {
            return $load();
        }

        if ($ttl === null) {
            return $this->cache->rememberForever(key: $cacheKey, callback: $load);
        }

        return $this->cache->remember(key: $cacheKey, ttl: $ttl, callback: $load);
    }

    public function clearCache(): void
    {
        $cacheKey = (string) config(key: 'settings.cache.key', default: 'egough.settings.all');
        $this->cache->forget(key: $cacheKey);
    }

    public function flag(string $key, bool $default = false): bool
    {
        return (bool) $this->get(key: $key, default: $default);
    }

    public function has(string $key, mixed $includeDefaults = null): bool
    {
        $all = $this->all();

        if (array_key_exists(key: $key, array: $all)) {
            return true;
        }

        if (! $includeDefaults) {
            return false;
        }

        $configDefaults = config(key: 'settings.defaults', default: []);

        return array_key_exists(key: $key, array: $configDefaults);
    }
}
