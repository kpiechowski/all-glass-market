<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RoleEnum: string implements HasColor, HasLabel
{
    case Root = 'root';
    case Admin = 'admin';
    case Client = 'client';

    public function getLabel(): string
    {
        return __('users::resource.roles.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Root => 'danger',
            self::Admin => 'warning',
            self::Client => 'gray',
        };
    }
}
