<?php

namespace Tests;

use Heritage\Contracts\Config\Repository;
use Heritage\Foundation\AliasLoader;
use Heritage\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase, WithWorkbench;

    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        $this->usesTestingFeature(new WithMigration('ugarit', 'queue'));

        parent::setUp();

        AliasLoader::getInstance()->setAliases([]);
    }

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config) {
            $config->set('queue.failed.driver', 'null');
        });
    }
}
