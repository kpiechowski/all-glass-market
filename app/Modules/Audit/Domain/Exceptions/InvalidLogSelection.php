<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

/** A channel that is not configured, or a day that is not a real date (FR-16). */
final class InvalidLogSelection extends DomainException
{
    public static function unknownChannel(): self
    {
        return new self('audit::exceptions.log_unknown_channel');
    }

    public static function invalidDay(): self
    {
        return new self('audit::exceptions.log_invalid_day');
    }
}
