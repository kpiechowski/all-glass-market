<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Http\Middleware;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Auth\SetNewPassword;
use App\Modules\Users\UserInterface\Support\ImpersonationSession;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-37, WF-07: while "password change required" is set, every panel page
 * leads to "Ustaw nowe hasło"; only that page and signing out stay open.
 */
class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user instanceof UserModel || ! $user->password_change_required || $this->isImpersonating($request)) {
            return $next($request);
        }

        $panel = Filament::getCurrentOrDefaultPanel();

        if ($request->routeIs($panel->generateRouteName(SetNewPassword::ROUTE), $panel->generateRouteName('auth.logout'))) {
            return $next($request);
        }

        return redirect()->route($panel->generateRouteName(SetNewPassword::ROUTE));
    }

    /** WF-06, WF-07: an impersonator is not redirected. */
    protected function isImpersonating(Request $request): bool
    {
        return ImpersonationSession::isActive();
    }
}
