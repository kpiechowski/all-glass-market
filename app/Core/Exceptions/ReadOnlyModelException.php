<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use LogicException;

final class ReadOnlyModelException extends LogicException
{
    public static function forWrite(string $modelClass, string $operation): self
    {
        return new self("{$modelClass}::{$operation}() outside a write scope. Send a command instead.");
    }
}
