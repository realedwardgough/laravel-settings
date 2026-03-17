<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Unit;

use Egough\LaravelSettings\ModelSettings\ModelSettingsManager;
use Egough\LaravelSettings\Tests\Support\FakeModel;
use Egough\LaravelSettings\Tests\Support\InMemoryModelSettingsRepository;
use Egough\LaravelSettings\Tests\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;

class ModelSettingsManagerTest extends TestCase
{
    public function test_it_requires_a_model_scope_before_access(): void
    {
        $manager = new ModelSettingsManager(
            new InMemoryModelSettingsRepository,
            new CacheRepository(new ArrayStore),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ModelSettingsManager is not scoped. Call ->for($model) first.');

        $manager->all();
    }

    public function test_it_reads_and_updates_settings_for_a_scoped_model(): void
    {
        $repo = new InMemoryModelSettingsRepository;
        $model = new FakeModel;
        $model->id = 5;

        $manager = new ModelSettingsManager($repo, new CacheRepository(new ArrayStore));
        $scopedManager = $manager->for($model);

        $scopedManager->set('ui.theme', 'dark');
        $this->assertSame('dark', $scopedManager->get('ui.theme'));

        $scopedManager->set('ui.theme', 'light');
        $this->assertSame('light', $scopedManager->get('ui.theme'));

        $scopedManager->forget('ui.theme');
        $this->assertNull($scopedManager->get('ui.theme'));
    }

    public function test_it_caches_model_settings_per_scope_until_the_cache_is_cleared(): void
    {
        $repo = new InMemoryModelSettingsRepository;
        $model = new FakeModel;
        $model->id = 12;

        $repo->items[FakeModel::class.':12'] = ['dashboard.layout' => 'grid'];

        $manager = (new ModelSettingsManager($repo, new CacheRepository(new ArrayStore)))->for($model);

        $this->assertSame('grid', $manager->get('dashboard.layout'));
        $this->assertSame(1, $repo->allCalls);

        $repo->items[FakeModel::class.':12']['dashboard.layout'] = 'list';

        $this->assertSame('grid', $manager->get('dashboard.layout'));
        $this->assertSame(1, $repo->allCalls);

        $manager->clearCache();

        $this->assertSame('list', $manager->get('dashboard.layout'));
        $this->assertSame(2, $repo->allCalls);
    }
}
