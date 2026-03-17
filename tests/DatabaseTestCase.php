<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

abstract class DatabaseTestCase extends TestCase
{
    protected Capsule $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = new Capsule;
        $this->database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->database->setAsGlobal();
        $this->database->bootEloquent();

        $this->runPackageMigrations();
    }

    protected function tearDown(): void
    {
        $this->database->getConnection()->disconnect();

        parent::tearDown();
    }

    private function runPackageMigrations(): void
    {
        $this->database->schema()->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        $this->database->schema()->create('model_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('settingable_type');
            $table->unsignedBigInteger('settingable_id');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
            $table->unique(['settingable_type', 'settingable_id', 'key'], 'model_settings_unique');
            $table->index(['settingable_type', 'settingable_id'], 'model_settings_owner');
        });
    }
}
