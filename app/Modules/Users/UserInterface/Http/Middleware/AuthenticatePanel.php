<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Http\Middleware;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\EndImpersonation\EndImpersonationCommand;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\UserInterface\Support\ImpersonationSession;
use Ecotone\Modelling\CommandBus;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Keeps a User out of the panel when their Role is not registered (FR-07)
 * or the account is deactivated (WF-05), also for a session that started before the Role went away. Replaces the
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

        $this->endImpersonationWhenAnEndIsDeactivated($guard->user());

        if (! $guard->check() || ! $guard->user()->isActive()) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $this->unauthenticated($request, $guards);

            return;
        }

        abort_unless(app(RoleRegistry::class)->isRegistered((string) $guard->user()->role), 403);
    }

    /**
     * WF-05, §11: a Deactivated target loses the impersonation and the
     * impersonator returns to their own account; a Deactivated impersonator
     * ends both sessions.
     */
    private function endImpersonationWhenAnEndIsDeactivated(object $user): void
    {
        if (! ImpersonationSession::isActive()) {
            return;
        }

        $impersonator = ImpersonationSession::impersonator();
        $impersonatorGone = $impersonator === null || ! $impersonator->isActive();

        if ($user->isActive() && ! $impersonatorGone) {
            return;
        }

        app(CommandBus::class)->send(new EndImpersonationCommand(
            Actor::user((int) ImpersonationSession::impersonatorId()),
            (int) $user->getKey(),
            $user->isActive() ? EndImpersonationCommand::IMPERSONATOR_DEACTIVATED : EndImpersonationCommand::TARGET_DEACTIVATED,
        ));

        if (ImpersonationSession::leave() !== null) {
            throw new HttpResponseException(redirect()->to(Filament::getUrl()));
        }
    }
}
