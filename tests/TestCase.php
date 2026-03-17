<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container();

        $app->instance('config', new ConfigRepository([
            'settings' => [
                'cache' => [
                    'enabled' => true,
                    'key' => 'egough.settings.all',
                    'ttl' => 60,
                ],
                'defaults' => [],
                'model' => [
                    'cache' => [
                        'enabled' => true,
                        'ttl' => 3600,
                        'key_prefix' => 'egough.model_settings.',
                    ],
                ],
            ],
        ]));

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }
}
