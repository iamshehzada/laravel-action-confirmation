<?php

namespace Iamshehzada\ActionConfirmation\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Iamshehzada\ActionConfirmation\ActionConfirmationServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ActionConfirmationServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // In tests we define action target as our fake model
        $app['config']->set('action-confirmation.actions.delete_user', [
            'target' => \Iamshehzada\ActionConfirmation\Tests\Fixtures\User::class,
            'ttl' => 300,
            'channels' => ['api', 'web'],
            'reason_required' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Migrate package table
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Create a fake users table for tests
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        // Create fake model class at runtime (fixture)
        if (! class_exists(\Iamshehzada\ActionConfirmation\Tests\Fixtures\User::class)) {
            eval('
            namespace Iamshehzada\ActionConfirmation\Tests\Fixtures;
            use Illuminate\Database\Eloquent\Model;
            class User extends Model {
                protected $table = "users";
                protected $guarded = [];
            }');
        }
    }
}
