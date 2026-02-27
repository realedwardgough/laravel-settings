<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\ModelSettings;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;

class ModelSettingsManager
{
    private ?string $type = null;
    private ?int $id = null;

    /**
     * @param ModelSettingsRepository $repo
     * @param Cache $cache
     */
    public function __construct(
        private readonly ModelSettingsRepository $repo,
        private readonly Cache $cache,
    ) {}

    /**
     * @param Model $model
     * @return $this
     */
    public function for(Model $model): self
    {
        $clone = clone $this;

        // Use morph class so it supports morphMap() aliases
        $clone->type = $model->getMorphClass();
        $clone->id = (int) $model->getKey();

        return $clone;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @return void
     */
    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $this->ensureScoped();
        $this->repo->set($this->type, $this->id, $key, $value, $type);
        $this->clearCache();
    }

    /**
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        $this->ensureScoped();
        $this->repo->forget($this->type, $this->id, $key);
        $this->clearCache();
    }

    /**
     * @return string|array
     */
    public function all(): string|array
    {
        $this->ensureScoped();

        $cacheEnabled = (bool) config('settings.model.cache.enabled', true);
        $ttl = config('settings.model.cache.ttl', 3600);

        $cacheKey = $this->cacheKey();

        $load = fn () => $this->repo->all($this->type, $this->id);

        if (! $cacheEnabled) {
            return $load();
        }

        if ($ttl === null) {
            return $this->cache->rememberForever($cacheKey, $load);
        }

        return $this->cache->remember($cacheKey, $ttl, $load);
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->ensureScoped();
        $this->cache->forget($this->cacheKey());
    }

    /**
     * @return string
     */
    private function cacheKey(): string
    {
        $prefix = (string) config('settings.model.cache.key_prefix', 'egough.model_settings.');
        return $prefix.$this->type.'.'.$this->id.'.all';
    }

    /**
     * @return void
     */
    private function ensureScoped(): void
    {
        if ($this->type === null || $this->id === null) {
            throw new \RuntimeException('ModelSettingsManager is not scoped. Call ->for($model) first.');
        }
    }
}
