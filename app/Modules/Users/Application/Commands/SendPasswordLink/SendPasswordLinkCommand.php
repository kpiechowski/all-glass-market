<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\SendPasswordLink;

use App\Core\Domain\Actor;

/**
 * FR-26: an Admin re-sends an invitation or a reset link. `kind` is 'invitation' or 'reset'.
 */
final readonly class SendPasswordLinkCommand
{
    public function __construct(
        public Actor $actor,
        public int $userId,
        public string $kind,
    ) {}
}
