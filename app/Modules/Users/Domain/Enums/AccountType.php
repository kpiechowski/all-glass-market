<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** RULE-05. Only Private exists until ticket 04 records the declaration. */
enum AccountType: string implements HasColor, HasLabel
{
    case Private = 'private';
    case Company = 'company';

    public function getLabel(): string
    {
        return __('users::enums.account_type.'.$this->value);
    }

    /** An Account type is a category, so its badge stays neutral (D-21). */
    public function getColor(): string
    {
        return 'gray';
    }
}
