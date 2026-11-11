<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\UpdateVehicle\UpdateVehicleCommand;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Actions\DeleteVehicleAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Support\VehicleFormData;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/** Template B (form). The name is never rewritten by a field change: only what the admin typed is saved (FR-08). */
class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    public static bool $formActionsAreSticky = true;

    /** No primary in the header (Zapisz sits in the sticky footer); Podgląd is secondary, Usuń sits in "⋮". */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ActionGroup::make([
                DeleteVehicleAction::make(thenGoToList: true),
            ]),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        CommandSender::send(
            new UpdateVehicleCommand(...[
                'actor' => CurrentActor::get(),
                'vehicleId' => (int) $record->getKey(),
                ...VehicleFormData::commandArguments($data),
            ]),
            ['catalogue::exceptions.vehicle.name_is_taken' => ['data.name']],
        );

        return $record->refresh();
    }
}
