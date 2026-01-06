<?php

use Iamshehzada\ActionConfirmation\Exceptions\ConfirmationRequiredException;
use Iamshehzada\ActionConfirmation\Exceptions\ConfirmationInvalidException;
use Iamshehzada\ActionConfirmation\Models\ActionConfirmation;
use Iamshehzada\ActionConfirmation\Tests\Fixtures\User;

it('throws confirmation required when no token provided', function () {
    $user = User::create(['name' => 'Target']);
    $actor = User::create(['name' => 'Admin']);

    try {
        confirm()
            ->action('delete_user')
            ->by($actor)
            ->on($user)
            ->via('api')
            ->run(fn () => $user->delete());

        $this->fail('Expected exception not thrown');
    } catch (ConfirmationRequiredException $e) {
        expect($e->token)->not->toBeEmpty();
        expect($e->expiresInSeconds)->toBeGreaterThan(0);
        expect($e->reasonRequired)->toBeTrue();
    }
});

it('executes after token provided (and sets confirmed_at)', function () {
    $target = User::create(['name' => 'Target']);
    $actor = User::create(['name' => 'Admin']);

    // First call generates pending token
    try {
        confirm()
            ->action('delete_user')
            ->by($actor)
            ->on($target)
            ->via('api')
            ->run(fn () => $target->delete());
    } catch (\Iamshehzada\ActionConfirmation\Exceptions\ConfirmationRequiredException $e) {
        $token = $e->token;
    }

    // Second call provides token + reason
    confirm()
        ->action('delete_user')
        ->by($actor)
        ->on($target)
        ->via('api')
        ->token($token)
        ->reason('User requested account deletion')
        ->run(fn () => $target->delete());

    expect(User::query()->whereKey($target->id)->exists())->toBeFalse();

    $conf = ActionConfirmation::query()->where('token', $token)->firstOrFail();
    expect($conf->confirmed_at)->not->toBeNull();
    expect($conf->reason)->toBe('User requested account deletion');
});

it('rejects token if it does not match actor', function () {
    $target = User::create(['name' => 'Target']);
    $actor1 = User::create(['name' => 'Admin1']);
    $actor2 = User::create(['name' => 'Admin2']);

    // create pending token for actor1
    try {
        confirm()
            ->action('delete_user')
            ->by($actor1)
            ->on($target)
            ->via('api')
            ->run(fn () => true);
    } catch (\Iamshehzada\ActionConfirmation\Exceptions\ConfirmationRequiredException $e) {
        $token = $e->token;
    }

    // actor2 tries to use it
    expect(fn () =>
        confirm()
            ->action('delete_user')
            ->by($actor2)
            ->on($target)
            ->via('api')
            ->token($token)
            ->reason('whatever')
            ->run(fn () => true)
    )->toThrow(ConfirmationInvalidException::class);
});
