<?php

declare(strict_types=1);

namespace App\Filament\RenderHooks;

use App\Modules\Users\UserInterface\Support\ImpersonationSession;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;

/** Everything the panel injects into Filament's layout through render hooks. */
final class PanelRenderHooks
{
    public static function register(Panel $panel): Panel
    {
        return $panel
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => '<meta name="robots" content="noindex, nofollow">')
            ->renderHook(PanelsRenderHook::BODY_START, fn (): string => ImpersonationSession::isActive()
                ? Blade::render('@livewire("users-impersonation-banner")')
                : '')
            /* D-44: the glass pane where the topbar meets the navy sidebar. */
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn (): View => view('panel::topbar.ornament'))
            /* Login geodes (issue 09): one pair in opposite corners of every simple auth page. */
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, fn (): View => view('panel::auth.geode'));
    }
}
