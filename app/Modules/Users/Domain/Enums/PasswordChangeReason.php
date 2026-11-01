<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

/** Why a password changed; Audit records it (FR-38 "as forced"). */
enum PasswordChangeReason: string
{
    case Own = 'own';
    case ByAdmin = 'by_admin';
    case Forced = 'forced';
    case Reset = 'reset';
    case Invitation = 'invitation';
}
