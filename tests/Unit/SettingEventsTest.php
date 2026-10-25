<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Unit;

use Egough\LaravelSettings\Events\SettingCreated;
use Egough\LaravelSettings\Events\SettingDeleted;
use Egough\LaravelSettings\Events\SettingUpdated;
use Egough\LaravelSettings\Tests\Support\FakeModel;
use Egough\LaravelSettings\Tests\TestCase;

class SettingEventsTest extends TestCase
{
    public function test_global_events_preserve_keys_and_values_without_an_owner(): void
    {
        $created = new SettingCreated('notifications.enabled', null);
        $updated = new SettingUpdated('notifications.enabled', false, true);
        $deleted = new SettingDeleted('notifications.enabled', null);

        $this->assertSame('notifications.enabled', $created->key);
        $this->assertNull($created->newValue);
        $this->assertNull($created->model);
        $this->assertSame('notifications.enabled', $updated->key);
        $this->assertFalse($updated->oldValue);
        $this->assertTrue($updated->newValue);
        $this->assertNull($updated->model);
        $this->assertSame('notifications.enabled', $deleted->key);
        $this->assertNull($deleted->oldValue);
        $this->assertNull($deleted->model);
    }

    public function test_model_events_preserve_the_owner_and_structured_values(): void
    {
        $model = new FakeModel;
        $model->id = 5;
        $oldValue = ['email' => false];
        $newValue = ['email' => true];

        $created = new SettingCreated('notifications', $newValue, $model);
        $updated = new SettingUpdated('notifications', $oldValue, $newValue, $model);
        $deleted = new SettingDeleted('notifications', $oldValue, $model);

        $this->assertSame($model, $created->model);
        $this->assertSame($newValue, $created->newValue);
        $this->assertSame($model, $updated->model);
        $this->assertSame($oldValue, $updated->oldValue);
        $this->assertSame($newValue, $updated->newValue);
        $this->assertSame($model, $deleted->model);
        $this->assertSame($oldValue, $deleted->oldValue);
    }
}
