<?php

namespace Iamshehzada\ActionConfirmation\Exceptions;

use RuntimeException;

class ConfirmationRequiredException extends RuntimeException
{
    public function __construct(
        public readonly string $token,
        public readonly int $expiresInSeconds,
        public readonly bool $reasonRequired
    ) {
        parent::__construct('Confirmation required.');
    }

    public function toArray(): array
    {
        return [
            'message' => 'Confirmation required',
            'confirmation_id' => $this->token,
            'expires_in' => $this->expiresInSeconds,
            'reason_required' => $this->reasonRequired,
        ];
    }
}
