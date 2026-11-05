<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class ChangeRefused extends DomainException
{
    public static function nothingChanged(): self
    {
        return new self('audit::exceptions.change_nothing_changed');
    }

    public static function invalidActor(): self
    {
        return new self('audit::exceptions.change_invalid_actor');
    }
}
