<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class RoleAssignmentRefused extends DomainException
{
    public static function superAdminConsoleOnly(): self
    {
        return new self('users::exceptions.super_admin_console_only');
    }

    public static function alreadySuperAdmin(): self
    {
        return new self('users::exceptions.already_super_admin');
    }

    public static function notSuperAdmin(): self
    {
        return new self('users::exceptions.not_super_admin');
    }

    public static function userNotFound(): self
    {
        return new self('users::exceptions.user_not_found');
    }

    public static function roleNotRegistered(): self
    {
        return new self('users::exceptions.role_not_registered');
    }
}
