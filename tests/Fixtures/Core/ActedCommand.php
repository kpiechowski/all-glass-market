<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Domain\Actor;

final readonly class ActedCommand
{
    public function __construct(public Actor $actor, public bool $nested = false) {}
}
