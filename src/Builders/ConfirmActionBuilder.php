<?php

namespace Iamshehzada\ActionConfirmation\Builders;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Iamshehzada\ActionConfirmation\Exceptions\ConfirmationInvalidException;
use Iamshehzada\ActionConfirmation\Exceptions\ConfirmationRequiredException;
use Iamshehzada\ActionConfirmation\Exceptions\InvalidTargetException;
use Iamshehzada\ActionConfirmation\Models\ActionConfirmation;
use Iamshehzada\ActionConfirmation\Support\ActionConfig;

class ConfirmActionBuilder
{
    protected ?string $action = null;
    protected ?Model $target = null;
    protected mixed $actor = null; // usually Authenticatable
    protected string $channel = 'web';
    protected ?string $token = null;
    protected ?string $reason = null;

    public function fresh(): self
    {
        $clone = new self();
        return $clone;
    }

    public function action(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function on(Model $model): self
    {
        $this->target = $model;
        return $this;
    }

    public function by(mixed $user): self
    {
        $this->actor = $user;
        return $this;
    }

    public function via(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * In API flow: attach token from request header.
     */
    public function token(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function reason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * Run the protected action or throw ConfirmationRequiredException.
     */
    public function run(Closure $callback): mixed
    {
        $this->guardBasics();
        $this->guardChannel();
        $this->guardTargetMatchesAction();

        // If caller supplied token, verify it; otherwise require confirmation.
        if ($this->token) {
            $this->consumeTokenOrFail($this->token);
            return $callback();
        }

        // No token: create (or reuse) a pending confirmation and throw.
        $pending = $this->issuePendingConfirmation();
        throw new ConfirmationRequiredException(
            token: $pending->token,
            expiresInSeconds: max(1, now()->diffInSeconds($pending->expires_at, false)),
            reasonRequired: ActionConfig::reasonRequired($this->action)
        );
    }

    protected function guardBasics(): void
    {
        if (! $this->action) {
            throw new \InvalidArgumentException('Action is required. Call ->action("...")');
        }
        if (! $this->target) {
            throw new \InvalidArgumentException('Target model is required. Call ->on($model)');
        }
    }

    protected function guardChannel(): void
    {
        $channels = ActionConfig::channels($this->action);
        if (! in_array($this->channel, $channels, true)) {
            throw new \InvalidArgumentException("Channel [{$this->channel}] is not allowed for action [{$this->action}]");
        }
    }

    protected function guardTargetMatchesAction(): void
    {
        $expected = ActionConfig::targetClass($this->action);
        if (! $expected) {
            // If not configured, allow (but best practice: always set target in config).
            return;
        }

        if (! is_a($this->target, $expected)) {
            $got = get_class($this->target);
            throw new InvalidTargetException("Action [{$this->action}] cannot be applied to [$got]. Expected [$expected].");
        }
    }

    protected function issuePendingConfirmation(): ActionConfirmation
    {
        $ttl = ActionConfig::ttl($this->action);

        // Reuse existing unexpired, unconfirmed token for same actor+target+action
        $q = ActionConfirmation::query()
            ->where('action', $this->action)
            ->where('target_type', get_class($this->target))
            ->where('target_id', $this->target->getKey())
            ->whereNull('confirmed_at')
            ->where('expires_at', '>', now());

        if ($this->actor && isset($this->actor->id)) {
            $q->where('actor_id', $this->actor->id);
        } else {
            $q->whereNull('actor_id');
        }

        $existing = $q->latest('id')->first();
        if ($existing) {
            return $existing;
        }

        return ActionConfirmation::create([
            'action' => $this->action,
            'target_type' => get_class($this->target),
            'target_id' => $this->target->getKey(),
            'actor_id' => ($this->actor && isset($this->actor->id)) ? $this->actor->id : null,
            'token' => $this->makeToken(),
            'reason' => null,
            'confirmed_at' => null,
            'expires_at' => now()->addSeconds($ttl),
        ]);
    }

    protected function consumeTokenOrFail(string $token): void
    {
        $conf = ActionConfirmation::query()
            ->where('token', $token)
            ->first();

        if (! $conf) {
            throw new ConfirmationInvalidException('Invalid confirmation token.');
        }

        if ($conf->isExpired()) {
            throw new ConfirmationInvalidException('Confirmation token expired.');
        }

        // Must match the action + target (prevents token reuse on other objects)
        if ($conf->action !== $this->action) {
            throw new ConfirmationInvalidException('Confirmation token does not match action.');
        }
        if ($conf->target_type !== get_class($this->target) || (string) $conf->target_id !== (string) $this->target->getKey()) {
            throw new ConfirmationInvalidException('Confirmation token does not match target.');
        }

        // If actor set, enforce same actor
        if ($this->actor && isset($this->actor->id)) {
            if ((string) $conf->actor_id !== (string) $this->actor->id) {
                throw new ConfirmationInvalidException('Confirmation token does not match actor.');
            }
        }

        // If reason required, ensure reason already exists OR passed now
        $reasonRequired = ActionConfig::reasonRequired($this->action);
        $finalReason = $conf->reason ?? $this->reason;

        if ($reasonRequired && ! $finalReason) {
            throw new ConfirmationInvalidException('Reason is required to confirm this action.');
        }

        // Mark confirmed (idempotent)
        if (! $conf->isConfirmed()) {
            $conf->forceFill([
                'reason' => $finalReason,
                'confirmed_at' => now(),
            ])->save();
        }
    }

    protected function makeToken(): string
    {
        // Long random token (safe for headers)
        return Str::random(64);
    }
}
