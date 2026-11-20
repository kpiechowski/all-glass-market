<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\UpdateOffer\UpdateOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferFormData;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Template B (form). The entity decides the status on saving (FR-07, FR-08); the owner and the number never change.
 * A seller's change that sends an Active or Rejected Offer back to Pending says so.
 */
class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;

    private bool $sentBackToPending = false;

    public static bool $formActionsAreSticky = true;

    /** No primary in the header (Zapisz sits in the sticky footer); Podgląd is secondary. */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return OfferFormData::formState($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $before = $record->getAttribute('status');

        CommandSender::send(new UpdateOfferCommand(...[
            'actor' => CurrentActor::get(),
            'offerId' => (int) $record->getKey(),
            ...OfferFormData::commandArguments($data),
        ]));

        $record->refresh();
        $this->sentBackToPending = $before !== OfferStatus::Pending && $record->getAttribute('status') === OfferStatus::Pending;

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->sentBackToPending
            ? __('offers::resources.offer.notifications.saved_pending_again')
            : parent::getSavedNotificationTitle();
    }
}
