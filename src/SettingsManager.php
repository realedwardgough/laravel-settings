<?php

declare(strict_types=1);

namespace Egough\LaravelSettings;

use Egough\LaravelSettings\Contracts\SettingsRepository;
use Illuminate\Contracts\Cache\Repository as Cache;

readonly class SettingsManager
{

    /**
     * @param SettingsRepository $repo
     * @param Cache $cache
     */
    public function __construct(
        private SettingsRepository $repo,
        private Cache $cache,
    ) {}

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
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

    /**
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @return void
     */
    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $this->repo->set(key: $key, value: $value, type: $type);
        $this->clearCache();
    }

    /**
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        $this->repo->forget(key: $key);
        $this->clearCache();
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

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $cacheKey = (string) config(key: 'settings.cache.key', default: 'egough.settings.all');
        $this->cache->forget(key: $cacheKey);
    }

    /**
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function flag(string $key, bool $default = false): bool
    {
        return (bool) $this->get(key: $key, default: $default);
    }
}
