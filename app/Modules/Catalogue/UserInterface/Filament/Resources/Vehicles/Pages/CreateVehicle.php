<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\CreateVehicle\CreateVehicleCommand;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Support\VehicleFormData;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\VehicleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Template B (form). The form proposes the name from the fields; the admin may change it (FR-08). */
class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    public static bool $formActionsAreSticky = true;

    protected function handleRecordCreation(array $data): Model
    {
        $id = CommandSender::send(
            new CreateVehicleCommand(...['actor' => CurrentActor::get(), ...VehicleFormData::commandArguments($data)]),
            ['catalogue::exceptions.vehicle.name_is_taken' => ['data.name']],
        );

        return VehicleModel::findOrFail($id);
    }
}
