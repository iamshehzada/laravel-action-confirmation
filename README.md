# Laravel Action Confirmation

Require explicit confirmation before executing dangerous actions in Laravel. Designed for API-first applications to prevent destructive operations (deleting users, issuing refunds, bulk changes) from running without a confirmation token.

---

## Features

- API-first, token-based confirmation
- Per-action configuration and model targeting
- Expiring confirmation tokens with optional reason requirement
- Idempotent flow (safe retries)
- Laravel 10 & 11 compatible

---

## Installation

```bash
composer require iamshehzada/laravel-action-confirmation
php artisan vendor:publish --tag=action-confirmation-config
php artisan vendor:publish --tag=action-confirmation-migrations
php artisan migrate
```

---

## Configuration

Example configuration (published to `config/action-confirmation.php`):

```php
return [
    // Header used for API confirmation tokens
    'api_header' => 'X-Confirmation-Token',

    // Define actions and their rules
    'actions' => [
        'delete_user' => [
            'target' => App\Models\User::class, // model to be acted upon
            'ttl' => 300,                         // token time-to-live in seconds
            'channels' => ['api', 'web'],         // allowed channels
            'reason_required' => true,            // require a reason string
        ],
    ],
];
```

---

## API Usage

```php
use Illuminate\Http\Request;
use App\Models\User;

public function destroy(Request $request, User $user)
{
    return confirm()
        ->action('delete_user')
        ->by($request->user())
        ->on($user)
        ->via('api')
        ->token($request->header('X-Confirmation-Token'))
        ->reason($request->input('reason'))
        ->run(function () use ($user) {
            $user->delete();
            return response()->json(['deleted' => true]);
        });
}
```

---

## API Confirmation Flow

1. First request without token (server responds with 409):

```json
{
  "message": "Confirmation required",
  "confirmation_id": "AbC123xyz",
  "expires_in": 300,
  "reason_required": true
}
```

2. Retry with token header and optional reason body:

Header:

```
X-Confirmation-Token: AbC123xyz
```

Body:

```json
{
  "reason": "User requested account deletion"
}
```

3. Action executes successfully after validation.

---

## Exception Handling

```php
use Iamshehzada\ActionConfirmation\Exceptions\ConfirmationRequiredException;

try {
    // protected action
} catch (ConfirmationRequiredException $e) {
    return response()->json($e->toArray(), 409);
}
```

---

## Security Guarantees

- Token bound to a specific action
- Token bound to a specific model and ID
- Token bound to a specific user
- Token expires automatically
- Token cannot be reused

---

## Testing

```bash
./vendor/bin/pest
```
