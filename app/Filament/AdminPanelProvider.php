<?php

declare(strict_types=1);

namespace App\Filament;

use App\Core\Concerns\DiscoverModuleFilament;
use App\Filament\Branding\BrandLogo;
use App\Filament\Configuration\ComponentDefaults;
use App\Filament\Navigation\PanelNavigation;
use App\Filament\Pages\Styleguide;
use App\Filament\RenderHooks\PanelRenderHooks;
use App\Modules\Users\UserInterface\Filament\Auth\AcceptInvitation;
use App\Modules\Users\UserInterface\Filament\Auth\Login;
use App\Modules\Users\UserInterface\Filament\Auth\RequestPasswordReset;
use App\Modules\Users\UserInterface\Filament\Auth\ResetPassword;
use App\Modules\Users\UserInterface\Filament\Auth\SetNewPassword;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Http\Middleware\AuthenticatePanel;
use App\Modules\Users\UserInterface\Http\Middleware\RequirePasswordChange;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The admin panel's table of contents. Everything that extends the panel lives in `app/Filament`
 * in its own class (navigation, render hooks, component defaults, branding, panel-only pages and
 * views); this provider only wires them together. Module screens are discovered from the modules.
 */
class AdminPanelProvider extends PanelProvider
{
    use DiscoverModuleFilament;

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'panel');

        ComponentDefaults::apply();
    }

    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->font('Manrope')
            ->brandLogo(fn () => BrandLogo::html())
            ->brandLogoHeight('2rem')
            ->viteTheme('resources/css/admin/theme.css')
            ->darkMode(false)
            ->sidebarWidth('16rem')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->routes(fn () => Route::get('/invitation', AcceptInvitation::class)->middleware('signed')->name('auth.invitation'))
            ->authenticatedRoutes(fn () => Route::get('/set-new-password', SetNewPassword::class)->name(SetNewPassword::ROUTE))
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action
                    ->label(__('users::profile.user_menu_label'))
                    ->url(EditProfile::getUrl()),
            ])
            ->navigation(PanelNavigation::build(...))
            ->databaseNotifications()
            ->colors([
                'primary' => '#003eb2',
            ])
            ->pages([
                Dashboard::class,
            ])
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

        PanelRenderHooks::register($panel);

        $this->discoverModuleFilament($panel);

        /* D-33: the Styleguide exists only locally; elsewhere its route is never registered. */
        if (app()->isLocal()) {
            $panel->pages([Styleguide::class]);
        }

        return $panel;
    }
}
