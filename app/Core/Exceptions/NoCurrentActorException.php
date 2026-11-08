<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use LogicException;

/**
 * A change was recorded with no command being handled. Every write goes
 * through a command, so this is a programming error, never a fallback case.
 */
final class NoCurrentActorException extends LogicException
{
    public static function outsideCommand(): self
    {
        return new self('A change was recorded outside a command. Send a command on the bus instead.');
    }
}
