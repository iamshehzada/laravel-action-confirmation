<?php

namespace Iamshehzada\ActionConfirmation;

use Illuminate\Support\ServiceProvider;
use Iamshehzada\ActionConfirmation\Builders\ConfirmActionBuilder;

class ActionConfirmationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/action-confirmation.php', 'action-confirmation');

        $this->app->bind(ConfirmActionBuilder::class, function ($app) {
            return new ConfirmActionBuilder();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/action-confirmation.php' => config_path('action-confirmation.php'),
        ], 'action-confirmation-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'action-confirmation-migrations');
    }
}
