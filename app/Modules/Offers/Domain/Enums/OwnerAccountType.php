<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

/** What the buyer's price needs to know about the owner (RULE-08): whether the owner is a Company. Values match Users' Account type keys. */
enum OwnerAccountType: string
{
    case Private = 'private';
    case Company = 'company';
}
