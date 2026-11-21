<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\DuplicateOffer\DuplicateOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

/** FR-27, RULE-07, template E (confirmation): the copy is a new Draft owned by whoever duplicates, an admin included; the user lands on it. */
final class DuplicateOfferAction
{
    public static function make(): Action
    {
        return Action::make('duplicate')
            ->label(__('offers::resources.offer.actions.duplicate.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.duplicate.heading'))
            ->modalDescription(__('offers::resources.offer.actions.duplicate.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.duplicate.label'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canDuplicate($record))
            ->action(function (OfferModel $record, Component $livewire): void {
                $id = CommandSender::send(new DuplicateOfferCommand(CurrentActor::get(), (int) $record->getKey()));

                Notification::make()->success()->title(__('offers::resources.offer.actions.duplicate.done'))->send();

                $livewire->redirect(OfferResource::getUrl('view', ['record' => $id]));
            });
    }
}
