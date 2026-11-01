<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Http\Middleware;

use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;

/**
 * Keeps a User out of the panel when their Role is not registered (FR-07),
 * also for a session that started before the Role went away. Replaces the
 * FilamentUser contract on the model, which the Infrastructure layer may not use.
 */
class AuthenticatePanel extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        abort_unless(app(RoleRegistry::class)->isRegistered((string) $guard->user()->role), 403);
    }
}
