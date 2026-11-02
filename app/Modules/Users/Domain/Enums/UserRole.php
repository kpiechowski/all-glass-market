<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

use App\Modules\Users\Domain\Contracts\Role;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The built-in Roles, ordered Super Admin > Admin > User.
 */
enum UserRole: string implements HasColor, HasLabel, Role
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case User = 'user';

    public function label(): string
    {
        return __('users::enums.user_role.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Admin => 'warning',
            self::User => 'gray',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::SuperAdmin => 300,
            self::Admin => 200,
            self::User => 100,
        };
    }
}
