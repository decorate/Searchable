<?php
    namespace Searchable\Test;

    class TestCase extends \Orchestra\Testbench\TestCase {

        protected function setUp(): void {
            parent::setUp();

            $path = dirname(dirname(__DIR__));

            $this->loadMigrationsFrom($path. '/database/migrations');

            $this->withFactories($path. '/database/factories');

            $this->artisan('migrate')->run();
        }

        protected function getEnvironmentSetUp ($app)
        {
            $app['config']->set('database.connections.mysql', [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'testbench',
                'username' => 'root',
                'password' => 'password',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ]);
        }

        protected function getPackageProviders ($app)
        {
            return [
            ];
        }

        protected function getPackageAliases ($app)
        {
            return [
            ];
        }
    }