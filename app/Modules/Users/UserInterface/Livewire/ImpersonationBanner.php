<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Livewire;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\EndImpersonation\EndImpersonationCommand;
use App\Modules\Users\UserInterface\Support\ImpersonationSession;
use Ecotone\Modelling\CommandBus;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** WF-06: the banner with the return button, shown while an impersonation lasts. */
class ImpersonationBanner extends Component
{
    public function leave(): void
    {
        $impersonatorId = ImpersonationSession::impersonatorId();
        $targetId = (int) auth()->id();

        if ($impersonatorId !== null) {
            app(CommandBus::class)->send(
                new EndImpersonationCommand(Actor::user($impersonatorId), $targetId, EndImpersonationCommand::RETURNED),
            );
        }

        ImpersonationSession::leave();

        $this->redirect(Filament::getUrl());
    }

    public function render(): View
    {
        return view('users::impersonation.banner', [
            'impersonatorName' => ImpersonationSession::impersonator()?->name,
            'targetName' => auth()->user()?->name,
        ]);
    }
}
