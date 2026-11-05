<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Core\Concerns\DiscoverModuleFilament;
use App\Core\UserInterface\Filament\Pages\Styleguide;
use App\Modules\Users\UserInterface\Filament\Auth\AcceptInvitation;
use App\Modules\Users\UserInterface\Filament\Auth\Login;
use App\Modules\Users\UserInterface\Filament\Auth\RequestPasswordReset;
use App\Modules\Users\UserInterface\Filament\Auth\ResetPassword;
use App\Modules\Users\UserInterface\Filament\Auth\SetNewPassword;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Http\Middleware\AuthenticatePanel;
use App\Modules\Users\UserInterface\Http\Middleware\RequirePasswordChange;
use App\Modules\Users\UserInterface\Support\ImpersonationSession;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    use DiscoverModuleFilament;

    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->font('Poppins')
            ->viteTheme('resources/css/admin/theme.css')
            ->darkMode(false)
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->routes(fn () => Route::get('/invitation', AcceptInvitation::class)->middleware('signed')->name('auth.invitation'))
            ->authenticatedRoutes(fn () => Route::get('/set-new-password', SetNewPassword::class)->name(SetNewPassword::ROUTE))
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => '<meta name="robots" content="noindex, nofollow">')
            ->renderHook(PanelsRenderHook::BODY_START, fn (): string => ImpersonationSession::isActive()
                ? Blade::render('@livewire("users-impersonation-banner")')
                : '')
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action
                    ->label(__('users::profile.user_menu_label'))
                    ->url(EditProfile::getUrl()),
            ])
            ->databaseNotifications()
            ->colors([
                'primary' => '#003eb2',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticatePanel::class,
                RequirePasswordChange::class,
            ]);

        $this->discoverModuleFilament($panel);

        /* D-33: the Styleguide exists only locally; elsewhere its route is never registered. */
        if (app()->isLocal()) {
            $panel->pages([Styleguide::class]);
        }

        return $panel;
    }
}
