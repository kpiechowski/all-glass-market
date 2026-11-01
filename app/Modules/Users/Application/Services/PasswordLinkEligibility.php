<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Services;

use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Entities\User;

/**
 * Who may receive and use a password link (WF-03, RULE-14): a User who may
 * enter the panel. Setting a password signs the User in, so a User whose Role
 * is not registered, or who is Deactivated (RULE-14), gets no link.
 */
final class PasswordLinkEligibility
{
    public function __construct(private RoleRegistry $registry) {}

    public function allows(User $user): bool
    {
        return $user->isActive() && $this->registry->isRegistered($user->roleKey());
    }
}
