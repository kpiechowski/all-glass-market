<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\UpdateOffer\UpdateOfferCommand;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferFormData;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/** Template B (form). Saving keeps the Offer a Draft; the owner and the number never change. */
class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;

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
        CommandSender::send(new UpdateOfferCommand(...[
            'actor' => CurrentActor::get(),
            'offerId' => (int) $record->getKey(),
            ...OfferFormData::commandArguments($data),
        ]));

        return $record->refresh();
    }
}
