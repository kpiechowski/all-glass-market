<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Auth;

use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Panel sign-in (WF-01): e-mail and password, at most 5 failed attempts a
 * minute per e-mail and IP address (FR-09, RULE-13), and only a User whose
 * Role is registered gets in (FR-07). The deactivated branch is ticket 07.
 */
class Login extends BaseLogin
{
    private const int MAX_FAILED_ATTEMPTS = 5;

    private const int WINDOW_SECONDS = 60;

    #[Exposed]
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $rateLimitKey = 'panel-login:'.Str::lower((string) $data['email']).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_FAILED_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'data.email' => __('users::login.throttled', ['seconds' => RateLimiter::availableIn($rateLimitKey)]),
            ]);
        }

        /** @var SessionGuard $guard */
        $guard = Filament::auth();
        $provider = $guard->getProvider();
        $credentials = $this->getCredentialsFromFormData($data);

        $user = $provider->retrieveByCredentials($credentials);

        if (! $user instanceof UserModel || ! $provider->validateCredentials($user, $credentials)) {
            RateLimiter::hit($rateLimitKey, self::WINDOW_SECONDS);

            throw ValidationException::withMessages(['data.email' => __('users::login.failed')]);
        }

        if (! app(RoleRegistry::class)->isRegistered((string) $user->role)) {
            throw ValidationException::withMessages(['data.email' => __('users::login.no_valid_role')]);
        }

        RateLimiter::clear($rateLimitKey);

        $guard->login($user, (bool) ($data['remember'] ?? false));
        session()->regenerate();

        return app(LoginResponse::class);
    }
}
