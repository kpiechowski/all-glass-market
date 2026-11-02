<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

/** RULE-14: what a single-use password link is for. */
enum PasswordLinkKind: string
{
    case Reset = 'reset';
    case Invitation = 'invitation';
}
