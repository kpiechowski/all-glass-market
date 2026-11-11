<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\UpdateGlassModel\UpdateGlassModelCommand;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/** Template B (form), WF-03: also for a withdrawn model; the write is a command. */
class EditGlassModel extends EditRecord
{
    protected static string $resource = GlassModelResource::class;

    public static bool $formActionsAreSticky = true;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var GlassModelModel $model */
        $model = $this->getRecord();

        $data['main_eurocode'] = $model->mainEurocode?->code;
        $data['equipment_ids'] = $model->equipment->modelKeys();
        $data['oem_codes'] = $model->oemCodes->pluck('code')->all();

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        CommandSender::send(new UpdateGlassModelCommand(
            actor: CurrentActor::get(),
            glassModelId: (int) $record->getKey(),
            positionId: $data['position_id'] === null ? null : (int) $data['position_id'],
            colourId: ($data['colour_id'] ?? null) === null ? null : (int) $data['colour_id'],
            equipmentIds: array_map(intval(...), $data['equipment_ids'] ?? []),
            oemCodes: array_values($data['oem_codes'] ?? []),
        ));

        return $record->refresh();
    }
}
