<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Feature;

use Egough\LaravelSettings\Events\SettingCreated;
use Egough\LaravelSettings\Events\SettingDeleted;
use Egough\LaravelSettings\Events\SettingUpdated;
use Egough\LaravelSettings\ModelSettings\DatabaseModelSettingsRepository;
use Egough\LaravelSettings\ModelSettings\Models\ModelSetting;
use Egough\LaravelSettings\ModelSettings\ModelSettingsManager;
use Egough\LaravelSettings\Tests\DatabaseTestCase;
use Egough\LaravelSettings\Tests\Support\FakeModel;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Relations\Relation;

class ModelSettingEventsTest extends DatabaseTestCase
{
    private FakeModel $owner;

    private ModelSettingsManager $manager;

    private array $received = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = new FakeModel;
        $this->owner->id = 5;
        $base = new ModelSettingsManager(new DatabaseModelSettingsRepository, new CacheRepository(new ArrayStore));
        app()->instance(ModelSettingsManager::class, $base);
        $this->manager = $base->for($this->owner);

        foreach ([SettingCreated::class, SettingUpdated::class, SettingDeleted::class] as $event) {
            app('events')->listen($event, function (object $event): void {
                $this->received[] = $event;
            });
        }
    }

    public function test_model_trait_emits_the_lifecycle_with_the_exact_owner(): void
    {
        $this->owner->settings()->set('enabled', '1', 'bool');
        $this->owner->setting()->set('enabled', false);
        $this->owner->settings()->forget('enabled');

        $this->assertEquals([
            new SettingCreated('enabled', true, $this->owner),
            new SettingUpdated('enabled', true, false, $this->owner),
            new SettingDeleted('enabled', false, $this->owner),
        ], $this->received);
        foreach ($this->received as $event) {
            $this->assertSame($this->owner, $event->model);
        }
    }

    public function test_stored_null_and_equal_values_are_handled_per_scope(): void
    {
        $this->manager->set('optional', null);
        $this->manager->set('optional', null, 'int');
        $this->manager->set('optional', 'value');
        $this->manager->set('optional', null);
        $this->manager->forget('optional');
        $this->manager->forget('optional');

        $this->assertEquals([
            new SettingCreated('optional', null, $this->owner),
            new SettingUpdated('optional', null, 'value', $this->owner),
            new SettingUpdated('optional', 'value', null, $this->owner),
            new SettingDeleted('optional', null, $this->owner),
        ], $this->received);
    }

    public function test_events_use_decoded_json_and_skip_equal_cast_values(): void
    {
        $this->manager->set('options', (object) ['email' => false]);
        $this->manager->set('options', ['email' => false]);
        $this->manager->set('options', ['email' => true]);

        $this->assertEquals([
            new SettingCreated('options', ['email' => false], $this->owner),
            new SettingUpdated('options', ['email' => false], ['email' => true], $this->owner),
        ], $this->received);
    }

    public function test_owners_have_isolated_values_cache_and_event_payloads(): void
    {
        $other = new FakeModel;
        $other->id = 6;
        $otherManager = $this->manager->for($other);
        $this->manager->set('theme', 'dark');
        $otherManager->set('theme', 'light');
        $this->manager->get('theme');
        $otherManager->get('theme');
        $this->manager->set('theme', 'system');

        $this->assertSame('light', $otherManager->get('theme'));
        $this->assertEquals([
            new SettingCreated('theme', 'dark', $this->owner),
            new SettingCreated('theme', 'light', $other),
            new SettingUpdated('theme', 'dark', 'system', $this->owner),
        ], $this->received);
        $this->assertSame($other, $this->received[1]->model);
        $this->assertSame($this->owner, $this->received[2]->model);
    }

    public function test_morph_aliases_are_used_for_storage_while_events_keep_the_owner(): void
    {
        Relation::morphMap(['people' => FakeModel::class]);

        try {
            $this->owner->settings()->set('theme', 'dark');

            $this->assertSame('people', ModelSetting::query()->firstOrFail()->settingable_type);
            $this->assertSame($this->owner, $this->received[0]->model);
        } finally {
            Relation::morphMap([], false);
        }
    }

    public function test_old_values_bypass_stale_cache_and_listeners_read_fresh_values(): void
    {
        $repository = new DatabaseModelSettingsRepository;
        $repository->set(FakeModel::class, 5, 'theme', 'cached');
        $this->manager->get('theme');
        $repository->set(FakeModel::class, 5, 'theme', 'actual');
        $seen = [];
        $listener = function () use (&$seen): void {
            $seen[] = $this->manager->get('theme');
        };
        app('events')->listen(SettingUpdated::class, $listener);
        app('events')->listen(SettingDeleted::class, $listener);

        $this->manager->set('theme', 'new');
        $this->manager->forget('theme');

        $this->assertSame('actual', $this->received[0]->oldValue);
        $this->assertSame(['new', null], $seen);
    }

    public function test_committed_events_keep_the_original_values_and_owners(): void
    {
        $connection = $this->database->getConnection();
        $other = new FakeModel;
        $other->id = 6;
        $connection->beginTransaction();
        $this->manager->set('theme', 'dark');
        $this->manager->set('theme', 'light');
        $this->manager->for($other)->set('theme', 'system');
        $this->manager->forget('theme');
        $this->assertSame([], $this->received);

        $connection->commit();

        $this->assertEquals([
            new SettingCreated('theme', 'dark', $this->owner),
            new SettingUpdated('theme', 'dark', 'light', $this->owner),
            new SettingCreated('theme', 'system', $other),
            new SettingDeleted('theme', 'light', $this->owner),
        ], $this->received);
    }

    public function test_cache_is_cleared_again_before_delivery_after_commit(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $this->manager->set('theme', 'dark');
        $this->manager->get('theme');
        (new DatabaseModelSettingsRepository)->set(FakeModel::class, 5, 'theme', 'final');
        $seen = null;
        app('events')->listen(SettingCreated::class, function () use (&$seen): void {
            $seen = $this->manager->get('theme');
        });

        $connection->commit();

        $this->assertSame('final', $seen);
        $this->assertSame('dark', $this->received[0]->newValue);
    }

    public function test_rolled_back_model_changes_emit_nothing(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $this->manager->set('theme', 'discarded');
        $connection->rollBack();

        $this->assertSame([], $this->received);
        $this->assertSame([], $this->manager->all());
    }

    public function test_nested_model_events_wait_for_the_outer_commit(): void
    {
        $connection = $this->database->getConnection();
        $connection->beginTransaction();
        $connection->beginTransaction();
        $this->manager->set('theme', 'dark');
        $connection->commit();
        $this->assertSame([], $this->received);

        $connection->commit();

        $this->assertEquals([new SettingCreated('theme', 'dark', $this->owner)], $this->received);
    }

    public function test_it_defers_on_the_storage_connection_when_the_owner_uses_another_connection(): void
    {
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:'], 'owners');
        $this->owner->setConnection('owners');
        $connection = $this->database->getConnection();
        $connection->beginTransaction();

        $this->manager->set('theme', 'dark');
        $this->assertSame([], $this->received);
        $connection->commit();

        $this->assertEquals([new SettingCreated('theme', 'dark', $this->owner)], $this->received);
    }

    public function test_an_owner_transaction_does_not_defer_an_independent_storage_write(): void
    {
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:'], 'owners');
        $this->owner->setConnection('owners');
        $ownerConnection = $this->owner->getConnection();
        $ownerConnection->beginTransaction();

        try {
            $this->manager->set('theme', 'dark');

            $this->assertEquals([new SettingCreated('theme', 'dark', $this->owner)], $this->received);
        } finally {
            $ownerConnection->rollBack();
            $ownerConnection->disconnect();
        }
    }

    public function test_failed_model_writes_emit_nothing(): void
    {
        try {
            $this->manager->set('invalid', NAN, 'json');
            $this->fail('Expected JSON encoding to fail.');
        } catch (\JsonException) {
            $this->assertSame([], $this->received);
            $this->assertSame([], $this->manager->all());
        }
    }
}
