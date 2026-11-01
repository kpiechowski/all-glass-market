<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Auth;

use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\Support\Htmlable;

/** Sets a password from an invitation link (FR-24, WF-04): same flow as a reset, 7-day link. */
class AcceptInvitation extends ResetPassword
{
    protected function linkKind(): PasswordLinkKind
    {
        return PasswordLinkKind::Invitation;
    }

    #[Unexposed]
    public function getTitle(): string|Htmlable
    {
        return __('users::login.invitation.title');
    }

    #[Unexposed]
    public function getHeading(): string|Htmlable|null
    {
        return __('users::login.invitation.heading');
    }
}
