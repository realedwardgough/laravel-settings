<?php

namespace Egough\LaravelSettings;

use Egough\LaravelSettings\Contracts\SettingsRepository;
use Illuminate\Contracts\Cache\Repository as Cache;

readonly class SettingsManager
{
    public function __construct(
        private SettingsRepository $repo,
        private Cache $cache,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        $configDefaults = config('settings.defaults', []);
        if (array_key_exists($key, $configDefaults)) {
            return $configDefaults[$key];
        }

        return $default;
    }

    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $this->repo->set($key, $value, $type);
        $this->clearCache();
    }

    public function forget(string $key): void
    {
        $this->repo->forget($key);
        $this->clearCache();
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $cacheEnabled = (bool) config('settings.cache.enabled', true);
        $cacheKey = (string) config('settings.cache.key', 'gough.settings.all');
        $ttl = config('settings.cache.ttl', 3600);

        $load = function () {
            $raw = $this->repo->all();
            return collect($raw)->map(fn ($row) => $row['value'])->all();
        };

        if (! $cacheEnabled) {
            return $load();
        }

        if ($ttl === null) {
            return $this->cache->rememberForever($cacheKey, $load);
        }

        return $this->cache->remember($cacheKey, $ttl, $load);
    }

    public function clearCache(): void
    {
        $cacheKey = (string) config(key: 'settings.cache.key', default: 'egough.settings.all');
        $this->cache->forget($cacheKey);
    }

    public function flag(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }
}
