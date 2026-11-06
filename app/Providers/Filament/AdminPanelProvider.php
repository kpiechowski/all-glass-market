<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Core\Concerns\DiscoverModuleFilament;
use App\Core\UserInterface\Filament\Pages\Styleguide;
use App\Core\UserInterface\Filament\Tables\TableDefaults;
use App\Modules\Audit\UserInterface\Filament\Pages\AuditLog\AuditLog;
use App\Modules\Audit\UserInterface\Filament\Pages\TechnicalLog\TechnicalLog;
use App\Modules\Users\UserInterface\Filament\Auth\AcceptInvitation;
use App\Modules\Users\UserInterface\Filament\Auth\Login;
use App\Modules\Users\UserInterface\Filament\Auth\RequestPasswordReset;
use App\Modules\Users\UserInterface\Filament\Auth\ResetPassword;
use App\Modules\Users\UserInterface\Filament\Auth\SetNewPassword;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use App\Modules\Users\UserInterface\Http\Middleware\AuthenticatePanel;
use App\Modules\Users\UserInterface\Http\Middleware\RequirePasswordChange;
use App\Modules\Users\UserInterface\Support\ImpersonationSession;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Resources\Resource as FilamentResource;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    use DiscoverModuleFilament;

    /** Panel-wide Filament component defaults (`configureUsing`) live here, never in CoreServiceProvider. */
    public function boot(): void
    {
        Table::configureUsing(TableDefaults::apply(...));
    }

    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->font('Manrope')
            ->brandLogo(fn (): Htmlable => $this->brandLogo())
            ->brandLogoHeight('2rem')
            ->viteTheme('resources/css/admin/theme.css')
            ->darkMode(false)
            ->sidebarWidth('16rem')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->routes(fn () => Route::get('/invitation', AcceptInvitation::class)->middleware('signed')->name('auth.invitation'))
            ->authenticatedRoutes(fn () => Route::get('/set-new-password', SetNewPassword::class)->name(SetNewPassword::ROUTE))
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => '<meta name="robots" content="noindex, nofollow">')
            ->renderHook(PanelsRenderHook::BODY_START, fn (): string => ImpersonationSession::isActive()
                ? Blade::render('@livewire("users-impersonation-banner")')
                : '')
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn (): View => view('core::filament.topbar.ornament'))
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action
                    ->label(__('users::profile.user_menu_label'))
                    ->url(EditProfile::getUrl()),
            ])
            ->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $this->navigation($builder))
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

    /**
     * D-40: both logo versions, as one Htmlable inside Filament's `.fi-logo` box (which carries the
     * height). The theme shows the dark one on white (login, topbar) and the client's white one on
     * the navy sidebar (`components/brand-logo.css`, `filament/layout/sidebar-navy.css`).
     */
    private function brandLogo(): Htmlable
    {
        $alt = e(__('filament-panels::layout.logo.alt', ['name' => filament()->getBrandName()]));

        return new HtmlString(sprintf(
            '<img class="brand-logo brand-logo--on-light" src="%s" alt="%s"><img class="brand-logo brand-logo--on-dark" src="%s" alt="%s">',
            e(asset('images/brand/allglass-logotype.svg')),
            $alt,
            e(asset('images/brand/allglass-logotype-white.svg')),
            $alt,
        ));
    }

    /**
     * D-25, D-25a: the sidebar is built here, not discovered. Three blocks: the top one holds only
     * the dashboard and is closed by a divider (`.sidebar-group--top`); the main one holds the work
     * screens right under it; the bottom one (profile, later settings) is pushed to the foot of the
     * sidebar by the theme (`.sidebar-group--bottom`). A new resource or page appears only once it
     * is listed here.
     */
    private function navigation(NavigationBuilder $builder): NavigationBuilder
    {
        $main = [
            UserResource::class,
            JournalEntryResource::class,
            AuditLog::class,
            TechnicalLog::class,
        ];

        /* D-33: the Styleguide route exists only locally, so its item may not be built elsewhere. */
        if (app()->isLocal()) {
            $main[] = Styleguide::class;
        }

        return $builder->groups([
            NavigationGroup::make()
                ->extraSidebarAttributes(['class' => 'sidebar-group--top'])
                ->items($this->navigationItems([
                    Dashboard::class,
                ])),
            NavigationGroup::make()
                ->items($this->navigationItems($main)),
            NavigationGroup::make()
                ->extraSidebarAttributes(['class' => 'sidebar-group--bottom'])
                ->items($this->navigationItems([
                    EditProfile::class,
                ])),
        ]);
    }

    /**
     * Each page or resource builds its own item (label, icon, badge, active state). The builder
     * skips Filament's registration checks, so the item repeats them: hidden unless the class
     * registers navigation and the current user may open it.
     *
     * @param  list<class-string<Page|FilamentResource>>  $classes
     * @return list<NavigationItem>
     */
    private function navigationItems(array $classes): array
    {
        return collect($classes)
            ->flatMap(fn (string $class): array => array_map(
                fn (NavigationItem $item): NavigationItem => $item->visible(
                    fn (): bool => $class::shouldRegisterNavigation() && $class::canAccess(),
                ),
                $class::getNavigationItems(),
            ))
            ->values()
            ->all();
    }
}
