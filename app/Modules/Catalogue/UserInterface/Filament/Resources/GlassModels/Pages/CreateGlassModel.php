<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\CreateGlassModel\CreateGlassModelCommand;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Template B (form), WF-01: the write is a command. */
class CreateGlassModel extends CreateRecord
{
    protected static string $resource = GlassModelResource::class;

    public static bool $formActionsAreSticky = true;

    /** @param  array<string, mixed>  $data */
    protected function handleRecordCreation(array $data): Model
    {
        $id = CommandSender::send(new CreateGlassModelCommand(
            actor: CurrentActor::get(),
            mainEurocode: (string) $data['main_eurocode'],
            positionId: $data['position_id'] === null ? null : (int) $data['position_id'],
            colourId: (['colour_id'] ?? null) === null ? null : (int) $data['colour_id'],
            equipmentIds: array_map(intval(...), $data['equipment_ids'] ?? []),
            oemCodes: array_values($data['oem_codes'] ?? []),
            furtherEurocodes: array_values($data['further_eurocodes'] ?? []),
        ));

        return GlassModelModel::query()->findOrFail($id);
    }
}
