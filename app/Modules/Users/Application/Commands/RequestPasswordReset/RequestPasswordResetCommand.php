<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\RequestPasswordReset;

use App\Core\Domain\Actor;

/**
 * FR-11, WF-03: a Guest asks for a reset link. The actor is the system, since
 * nobody is signed in.
 */
final readonly class RequestPasswordResetCommand
{
    public function __construct(
        public Actor $actor,
        public string $email,
    ) {}
}
