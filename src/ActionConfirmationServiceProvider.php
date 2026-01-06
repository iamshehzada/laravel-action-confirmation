<?php

namespace Iamshehzada\ActionConfirmation;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Iamshehzada\ActionConfirmation\Exceptions\ConfirmationRequiredException;

class ActionConfirmationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerExceptionHandler();
    }

    protected function registerExceptionHandler(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'renderable')) {
            $handler->renderable(function (
                ConfirmationRequiredException $e,
                $request
            ) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Confirmation required',
                        'confirmation_id' => $e->token,
                        'expires_in' => $e->expiresInSeconds,
                        'reason_required' => $e->reasonRequired,
                    ], 409);
                }
            });
        }
    }
}
