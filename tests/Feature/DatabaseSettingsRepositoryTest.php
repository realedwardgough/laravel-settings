<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Feature;

use Egough\LaravelSettings\Models\Setting;
use Egough\LaravelSettings\Repositories\DatabaseSettingsRepository;
use Egough\LaravelSettings\Tests\DatabaseTestCase;

class DatabaseSettingsRepositoryTest extends DatabaseTestCase
{
    public function test_it_persists_and_returns_typed_global_settings(): void
    {
        $repository = new DatabaseSettingsRepository();

        $repository->set('feature.enabled', true);
        $repository->set('checkout.retries', 3);
        $repository->set('ui.options', ['theme' => 'dark']);

        $all = $repository->all();

        $this->assertTrue($all['feature.enabled']['value']);
        $this->assertSame('bool', $all['feature.enabled']['type']);
        $this->assertSame(3, $all['checkout.retries']['value']);
        $this->assertSame(['theme' => 'dark'], $all['ui.options']['value']);
    }

    public function test_it_uses_the_configured_settings_table_name(): void
    {
        config()->set('settings.table', 'package_settings');

        $this->database->schema()->create('package_settings', function ($table): void {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        $repository = new DatabaseSettingsRepository();
        $repository->set('site.name', 'Package Name');

        $this->assertTrue(Setting::query()->where('key', 'site.name')->exists());
        $this->assertSame('Package Name', $repository->all()['site.name']['value']);
    }
}
