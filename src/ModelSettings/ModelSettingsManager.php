<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\ModelSettings;

use Egough\LaravelSettings\Events\SettingCreated;
use Egough\LaravelSettings\Events\SettingDeleted;
use Egough\LaravelSettings\Events\SettingUpdated;
use Egough\LaravelSettings\Support\DispatchesSettingEvents;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

class ModelSettingsManager
{
    use DispatchesSettingEvents;

    private ?Model $model = null;

    private ?string $type = null;

    private ?int $id = null;

    public function __construct(
        private readonly ModelSettingsRepository $repo,
        private readonly Cache $cache,
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * @return $this
     */
    public function for(Model $model): self
    {
        $clone = clone $this;
        $clone->model = $model;

        // Use morph class so it supports morphMap() aliases
        $clone->type = $model->getMorphClass();
        $clone->id = (int) $model->getKey();

        return $clone;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $this->ensureScoped();
        $before = $this->repo->all($this->type, $this->id);
        $this->repo->set($this->type, $this->id, $key, $value, $type);
        $this->clearCache();
        $after = $this->repo->all($this->type, $this->id);

        if (! array_key_exists($key, $after)) {
            return;
        }

        if (! array_key_exists($key, $before)) {
            $this->dispatchSettingEvent(new SettingCreated($key, $after[$key], $this->model));
        } elseif ($before[$key] !== $after[$key]) {
            $this->dispatchSettingEvent(new SettingUpdated($key, $before[$key], $after[$key], $this->model));
        }
    }

    public function forget(string $key): void
    {
        $this->ensureScoped();
        $before = $this->repo->all($this->type, $this->id);
        $this->repo->forget($this->type, $this->id, $key);
        $this->clearCache();
        $after = $this->repo->all($this->type, $this->id);

        if (array_key_exists($key, $before) && ! array_key_exists($key, $after)) {
            $this->dispatchSettingEvent(new SettingDeleted($key, $before[$key], $this->model));
        }
    }

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

    public function clearCache(): void
    {
        $this->ensureScoped();
        $this->cache->forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        $prefix = (string) config('settings.model.cache.key_prefix', 'egough.model_settings.');

        return $prefix.$this->type.'.'.$this->id.'.all';
    }

    private function ensureScoped(): void
    {
        if ($this->type === null || $this->id === null) {
            throw new \RuntimeException('ModelSettingsManager is not scoped. Call ->for($model) first.');
        }
    }
}
