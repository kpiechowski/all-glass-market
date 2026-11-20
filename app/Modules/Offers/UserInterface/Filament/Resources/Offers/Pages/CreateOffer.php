<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\CreateOffer\CreateOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferFormData;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Template B (form). The creator becomes the owner; the Offer is saved as a Draft. */
class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

    public static bool $formActionsAreSticky = true;

    protected function handleRecordCreation(array $data): Model
    {
        $id = CommandSender::send(
            new CreateOfferCommand(...['actor' => CurrentActor::get(), ...OfferFormData::commandArguments($data)]),
        );

        return OfferModel::findOrFail($id);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('offers::resources.offer.notifications.created');
    }
}
