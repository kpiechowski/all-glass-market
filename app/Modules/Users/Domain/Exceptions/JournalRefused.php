<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class JournalRefused extends DomainException
{
    public static function invalidKind(): self
    {
        return new self('users::exceptions.journal_invalid_kind');
    }

    public static function invalidTitle(): self
    {
        return new self('users::exceptions.journal_invalid_title');
    }

    public static function entryNotFound(): self
    {
        return new self('users::exceptions.journal_entry_not_found');
    }

    public static function notOwner(): self
    {
        return new self('users::exceptions.journal_not_owner');
    }
}
