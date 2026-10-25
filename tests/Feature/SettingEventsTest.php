<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Feature;

use Egough\LaravelSettings\Events\SettingCreated;
use Egough\LaravelSettings\Events\SettingDeleted;
use Egough\LaravelSettings\Events\SettingUpdated;
use Egough\LaravelSettings\Facades\Settings;
use Egough\LaravelSettings\Models\Setting;
use Egough\LaravelSettings\Repositories\DatabaseSettingsRepository;
use Egough\LaravelSettings\SettingsManager;
use Egough\LaravelSettings\Tests\DatabaseTestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Events\Dispatcher;

class SettingEventsTest extends DatabaseTestCase
{
    private SettingsManager $manager;

    private Dispatcher $events;

    private array $received = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->events = app('events');
        $this->manager = new SettingsManager(new DatabaseSettingsRepository, new CacheRepository(new ArrayStore));

        foreach ([SettingCreated::class, SettingUpdated::class, SettingDeleted::class] as $event) {
            $this->events->listen($event, function (object $event): void {
                $this->received[] = $event;
            });
        }
    }

    public function test_it_emits_the_global_lifecycle_with_decoded_values(): void
    {
        $this->manager->set('retries', '1', 'int');
        $this->manager->set('retries', '2', 'int');
        $this->manager->forget('retries');

        $this->assertCount(3, $this->received);
        $this->assertEquals(new SettingCreated('retries', 1), $this->received[0]);
        $this->assertEquals(new SettingUpdated('retries', 1, 2), $this->received[1]);
        $this->assertEquals(new SettingDeleted('retries', 2), $this->received[2]);
    }

    public function test_it_distinguishes_stored_null_from_a_missing_key(): void
    {
        $this->manager->set('optional', null);
        $this->manager->set('optional', null);
        $this->manager->set('optional', 'value');
        $this->manager->set('optional', null);
        $this->manager->forget('optional');
        $this->manager->forget('optional');

        $this->assertEquals([
            new SettingCreated('optional', null),
            new SettingUpdated('optional', null, 'value'),
            new SettingUpdated('optional', 'value', null),
            new SettingDeleted('optional', null),
        ], $this->received);
    }

    public function test_config_defaults_do_not_count_as_stored_settings(): void
    {
        config()->set('settings.defaults', ['enabled' => true]);

        $this->manager->forget('enabled');
        $this->manager->set('enabled', true);
        $this->manager->forget('enabled');

        $this->assertEquals([
            new SettingCreated('enabled', true),
            new SettingDeleted('enabled', true),
        ], $this->received);
        $this->assertTrue($this->manager->get('enabled'));
    }

    public function test_equal_decoded_values_and_missing_deletions_emit_nothing(): void
    {
        $repository = new DatabaseSettingsRepository;
        $repository->set('enabled', true, 'bool');
        $repository->set('options', ['theme' => 'dark']);
        $repository->set('optional', null, 'string');

        $this->manager->set('enabled', '1', 'bool');
        $this->manager->set('options', (object) ['theme' => 'dark']);
        $this->manager->set('optional', null, 'int');
        $this->manager->forget('missing');

        $this->assertSame([], $this->received);
    }

    public function test_type_changes_that_change_the_decoded_value_emit_updated(): void
    {
        (new DatabaseSettingsRepository)->set('number', '1', 'string');

        $this->manager->set('number', '1', 'int');

        $this->assertEquals([new SettingUpdated('number', '1', 1)], $this->received);
    }

    public function test_it_bypasses_stale_cache_when_capturing_old_values(): void
    {
        $repository = new DatabaseSettingsRepository;
        $repository->set('name', 'cached');
        $this->assertSame('cached', $this->manager->get('name'));
        $repository->set('name', 'actual');

        $this->manager->set('name', 'new');

        $this->assertEquals([new SettingUpdated('name', 'actual', 'new')], $this->received);
    }

    public function test_listeners_read_fresh_values_after_each_operation(): void
    {
        $repository = new DatabaseSettingsRepository;
        $repository->set('name', 'old');
        $this->manager->get('name');
        $values = [];
        $listener = function () use (&$values): void {
            $values[] = $this->manager->get('name');
        };
        $this->events->listen(SettingUpdated::class, $listener);
        $this->events->listen(SettingDeleted::class, $listener);

        $this->manager->set('name', 'new');
        $this->manager->forget('name');

        $this->assertSame(['new', null], $values);
    }

    public function test_it_delivers_each_captured_change_only_after_commit(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $this->manager->set('name', 'first');
        $this->manager->set('name', 'second');
        $this->manager->forget('name');
        $this->assertSame([], $this->received);

        $connection->commit();

        $this->assertEquals([
            new SettingCreated('name', 'first'),
            new SettingUpdated('name', 'first', 'second'),
            new SettingDeleted('name', 'second'),
        ], $this->received);
    }

    public function test_it_clears_cache_again_before_deferred_delivery(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $this->manager->set('name', 'first');
        $this->assertSame('first', $this->manager->get('name'));
        (new DatabaseSettingsRepository)->set('name', 'final');
        $seen = null;
        $this->events->listen(SettingCreated::class, function () use (&$seen): void {
            $seen = $this->manager->get('name');
        });

        $connection->commit();

        $this->assertSame('final', $seen);
        $this->assertSame('first', $this->received[0]->newValue);
    }

    public function test_rolled_back_changes_emit_nothing(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $this->manager->set('name', 'discarded');
        $connection->rollBack();

        $this->assertSame([], $this->received);
        $this->assertSame([], (new DatabaseSettingsRepository)->all());
    }

    public function test_nested_transactions_wait_for_the_outer_commit(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $this->manager->set('outer', 'kept');
        $connection->beginTransaction();
        $this->manager->set('inner', 'kept');
        $connection->commit();
        $this->assertSame([], $this->received);

        $connection->commit();

        $this->assertCount(2, $this->received);
    }

    public function test_inner_rollback_discards_only_inner_events(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $this->manager->set('outer', 'kept');
        $connection->beginTransaction();
        $this->manager->set('inner', 'discarded');
        $connection->rollBack();
        $connection->commit();

        $this->assertEquals([new SettingCreated('outer', 'kept')], $this->received);
    }

    public function test_outer_rollback_discards_committed_inner_events(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $connection->beginTransaction();
        $this->manager->set('inner', 'discarded');
        $connection->commit();
        $connection->rollBack();

        $this->assertSame([], $this->received);
    }

    public function test_failed_writes_emit_nothing(): void
    {
        try {
            $this->manager->set('invalid', NAN, 'json');
            $this->fail('Expected JSON encoding to fail.');
        } catch (\JsonException) {
            $this->assertSame([], $this->received);
            $this->assertFalse(Setting::query()->where('key', 'invalid')->exists());
        }
    }

    public function test_listener_exceptions_propagate_after_the_write(): void
    {
        $this->events->listen(SettingCreated::class, function (): void {
            throw new \RuntimeException('Listener failed');
        });

        try {
            $this->manager->set('name', 'persisted');
            $this->fail('Expected the listener exception.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Listener failed', $exception->getMessage());
            $this->assertSame('persisted', $this->manager->get('name'));
        }
    }

    public function test_helpers_and_facades_share_manager_events(): void
    {
        app()->instance('settings.manager', $this->manager);
        app()->instance(SettingsManager::class, $this->manager);

        settings()->set('name', 'helper');
        Settings::set('name', 'facade');

        $this->assertEquals([
            new SettingCreated('name', 'helper'),
            new SettingUpdated('name', 'helper', 'facade'),
        ], $this->received);
    }
}
