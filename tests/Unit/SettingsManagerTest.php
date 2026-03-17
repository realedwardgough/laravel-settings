<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Unit;

use Egough\LaravelSettings\SettingsManager;
use Egough\LaravelSettings\Tests\Support\InMemorySettingsRepository;
use Egough\LaravelSettings\Tests\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;

class SettingsManagerTest extends TestCase
{
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
