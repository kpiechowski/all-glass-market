<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class PreferenceRefused extends DomainException
{
    public static function unknownPreference(): self
    {
        return new self('preferences::exceptions.unknown_preference');
    }

    public static function usersOnly(): self
    {
        return new self('preferences::exceptions.users_only');
    }

    public static function missingKey(): self
    {
        return new self('preferences::exceptions.missing_key');
    }

    public static function quickAccessInvalid(): self
    {
        return new self('preferences::exceptions.quick_access.invalid');
    }

    public static function quickAccessTooMany(int $max): self
    {
        return new self('preferences::exceptions.quick_access.too_many', ['max' => $max]);
    }

    public static function quickAccessDuplicate(): self
    {
        return new self('preferences::exceptions.quick_access.duplicate');
    }

    public static function quickAccessUnknownItem(): self
    {
        return new self('preferences::exceptions.quick_access.unknown_item');
    }
}
