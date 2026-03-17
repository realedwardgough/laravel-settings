<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Feature;

use Egough\LaravelSettings\ModelSettings\DatabaseModelSettingsRepository;
use Egough\LaravelSettings\ModelSettings\Models\ModelSetting;
use Egough\LaravelSettings\Tests\DatabaseTestCase;

class DatabaseModelSettingsRepositoryTest extends DatabaseTestCase
{
    public function test_it_persists_and_returns_typed_model_settings(): void
    {
        $repository = new DatabaseModelSettingsRepository();

        $repository->set('users', 7, 'notifications.email', false);
        $repository->set('users', 7, 'dashboard.widgets', ['sales', 'tasks']);

        $all = $repository->all('users', 7);

        $this->assertFalse($all['notifications.email']);
        $this->assertSame(['sales', 'tasks'], $all['dashboard.widgets']);
    }

    public function test_it_forgets_only_the_requested_model_setting(): void
    {
        $repository = new DatabaseModelSettingsRepository();

        $repository->set('users', 7, 'ui.theme', 'dark');
        $repository->set('users', 7, 'timezone', 'UTC');
        $repository->forget('users', 7, 'ui.theme');

        $all = $repository->all('users', 7);

        $this->assertArrayNotHasKey('ui.theme', $all);
        $this->assertSame('UTC', $all['timezone']);
    }

    public function test_it_uses_the_configured_model_settings_table_name(): void
    {
        config()->set('settings.model.table', 'package_model_settings');

        $this->database->schema()->create('package_model_settings', function ($table): void {
            $table->id();
            $table->string('settingable_type');
            $table->unsignedBigInteger('settingable_id');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        $repository = new DatabaseModelSettingsRepository();
        $repository->set('teams', 4, 'branding.primary_colour', 'blue');

        $this->assertTrue(ModelSetting::query()->where('key', 'branding.primary_colour')->exists());
        $this->assertSame('blue', $repository->all('teams', 4)['branding.primary_colour']);
    }
}
