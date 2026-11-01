<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class UserRefused extends DomainException
{
    public static function invalidEmail(): self
    {
        return new self('users::exceptions.invalid_email');
    }

    public static function emailTaken(): self
    {
        return new self('users::exceptions.email_taken');
    }

    public static function invalidName(): self
    {
        return new self('users::exceptions.invalid_name');
    }

    public static function passwordTooShort(): self
    {
        return new self('users::exceptions.password_too_short');
    }

    public static function userNotFound(): self
    {
        return new self('users::exceptions.user_not_found');
    }

    public static function cannotManage(): self
    {
        return new self('users::exceptions.cannot_manage');
    }

    public static function cannotChangeOwnRole(): self
    {
        return new self('users::exceptions.cannot_change_own_role');
    }

    public static function roleNotAssignable(): self
    {
        return new self('users::exceptions.role_not_assignable');
    }
}
