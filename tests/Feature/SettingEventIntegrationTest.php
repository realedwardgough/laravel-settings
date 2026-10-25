<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Feature;

use Egough\LaravelSettings\Console\SetSettingCommand;
use Egough\LaravelSettings\Events\SettingCreated;
use Egough\LaravelSettings\SettingsManager;
use Egough\LaravelSettings\SettingsServiceProvider;
use Egough\LaravelSettings\Tests\DatabaseTestCase;
use Egough\LaravelSettings\Tests\Support\FakeModel;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SettingEventIntegrationTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->instance('cache.store', new CacheRepository(new ArrayStore));
        (new SettingsServiceProvider(app()))->register();
    }

    public function test_service_provider_wires_events_for_both_managers(): void
    {
        $received = [];
        app('events')->listen(SettingCreated::class, function (SettingCreated $event) use (&$received): void {
            $received[] = $event;
        });
        $owner = new FakeModel;
        $owner->id = 5;

        settings()->set('enabled', true);
        $owner->settings()->set('enabled', false);

        $this->assertEquals([
            new SettingCreated('enabled', true),
            new SettingCreated('enabled', false, $owner),
        ], $received);
        $this->assertSame(settings(), app('settings.manager'));
        $this->assertTrue(settings()->get('enabled'));
        $this->assertFalse($owner->settings()->get('enabled'));
    }

    public function test_artisan_setter_emits_an_event_with_the_cast_value(): void
    {
        $received = [];
        app('events')->listen(SettingCreated::class, function (SettingCreated $event) use (&$received): void {
            $received[] = $event;
        });
        $command = new SetSettingCommand;
        $input = new ArrayInput(['key' => 'enabled', 'value' => 'true', '--type' => 'bool'], $command->getDefinition());
        $output = new BufferedOutput;
        $command->setInput($input);
        $command->setOutput(new OutputStyle($input, $output));

        $status = $command->handle(app(SettingsManager::class));

        $this->assertSame(SetSettingCommand::SUCCESS, $status);
        $this->assertEquals([new SettingCreated('enabled', true)], $received);
        $this->assertStringContainsString('Setting [enabled] saved.', $output->fetch());
    }
}
