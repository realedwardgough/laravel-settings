<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Unit;

use Egough\LaravelSettings\Contracts\DefersSettingEvents;
use Egough\LaravelSettings\Events\SettingCreated;
use Egough\LaravelSettings\Events\SettingDeleted;
use Egough\LaravelSettings\Events\SettingUpdated;
use Egough\LaravelSettings\SettingsManager;
use Egough\LaravelSettings\Tests\Support\InMemorySettingsRepository;
use Egough\LaravelSettings\Tests\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Events\Dispatcher;

class SettingsManagerTest extends TestCase
{
    public function test_custom_repositories_work_with_an_injected_dispatcher(): void
    {
        $events = new Dispatcher;
        $received = [];
        foreach ([SettingCreated::class, SettingUpdated::class, SettingDeleted::class] as $event) {
            $events->listen($event, function (object $event) use (&$received): void {
                $received[] = $event;
            });
        }
        $manager = new SettingsManager(new InMemorySettingsRepository, new CacheRepository(new ArrayStore), $events);

        $manager->set('name', 'first');
        $manager->set('name', 'second');
        $manager->forget('name');

        $this->assertEquals([
            new SettingCreated('name', 'first'),
            new SettingUpdated('name', 'first', 'second'),
            new SettingDeleted('name', 'second'),
        ], $received);
    }

    public function test_custom_repositories_can_opt_into_deferred_delivery(): void
    {
        $repo = new class extends InMemorySettingsRepository implements DefersSettingEvents
        {
            public array $callbacks = [];

            public function afterCommit(callable $callback): void
            {
                $this->callbacks[] = $callback;
            }
        };
        $received = [];
        app('events')->listen(SettingCreated::class, function (object $event) use (&$received): void {
            $received[] = $event;
        });
        $manager = new SettingsManager($repo, new CacheRepository(new ArrayStore));

        $manager->set('name', 'deferred');
        $this->assertSame([], $received);
        $this->assertCount(1, $repo->callbacks);
        ($repo->callbacks[0])();

        $this->assertEquals([new SettingCreated('name', 'deferred')], $received);
    }

    public function test_it_reads_database_values_before_defaults_and_fallbacks(): void
    {
        config()->set('settings.defaults', ['site.tagline' => 'Default Tagline']);

        $repo = new InMemorySettingsRepository;
        $repo->items = [
            'site.name' => ['type' => 'string', 'value' => 'Stored Name'],
        ];

        $manager = new SettingsManager($repo, new CacheRepository(new ArrayStore));

        $this->assertSame('Stored Name', $manager->get('site.name', 'Fallback Name'));
        $this->assertSame('Default Tagline', $manager->get('site.tagline', 'Fallback Name'));
        $this->assertSame('Fallback Name', $manager->get('missing.other', 'Fallback Name'));
    }

    public function test_it_uses_cached_values_until_cache_is_cleared(): void
    {
        $repo = new InMemorySettingsRepository;
        $repo->items = [
            'site.name' => ['type' => 'string', 'value' => 'Alpha'],
        ];

        $manager = new SettingsManager($repo, new CacheRepository(new ArrayStore));

        $this->assertSame('Alpha', $manager->get('site.name'));
        $this->assertSame(1, $repo->allCalls);

        $repo->items['site.name']['value'] = 'Beta';

        $this->assertSame('Alpha', $manager->get('site.name'));
        $this->assertSame(1, $repo->allCalls);

        $manager->clearCache();

        $this->assertSame('Beta', $manager->get('site.name'));
        $this->assertSame(2, $repo->allCalls);
    }

    public function test_it_updates_and_forgets_values_through_the_repository(): void
    {
        $repo = new InMemorySettingsRepository;
        $manager = new SettingsManager($repo, new CacheRepository(new ArrayStore));

        $manager->set('feature.billing', true, 'bool');
        $this->assertTrue($manager->flag('feature.billing'));
        $this->assertTrue($manager->has('feature.billing'));

        $manager->forget('feature.billing');
        $this->assertFalse($manager->has('feature.billing'));
    }
}
