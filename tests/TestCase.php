<?php

namespace Rappasoft\LaravelPatches\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Orchestra\Testbench\TestCase as Orchestra;
use Rappasoft\LaravelPatches\LaravelPatchesServiceProvider;

class TestCase extends Orchestra
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->clearPatches();
    }

    /**
     * @param  Application  $app
     *
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelPatchesServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $migration = include __DIR__.'/../database/migrations/create_patches_table.php.stub';
        $migration->up();
    }

    /**
     * Clear the database/patches folder in Orchestra
     */
    public function clearPatches(): void
    {
        foreach (glob(database_path('patches').'/*') as $file) {
            unlink($file);
        }
    }
}
