<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\ResumeOffer\ResumeOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;

/** FR-24, template E: "Wznów sprzedaż", only offered where the Offer may go straight back to Active; otherwise the owner edits it. */
final class ResumeOfferAction
{
    public static function make(bool $primary = false): Action
    {
        return Action::make('resume')
            ->label(__('offers::resources.offer.actions.resume.label'))
            ->color($primary ? 'primary' : 'gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.resume.heading'))
            ->modalDescription(__('offers::resources.offer.actions.resume.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.resume.label'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canResume($record))
            ->action(function (OfferModel $record): void {
                CommandSender::send(new ResumeOfferCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.resume.done'));
    }
}
