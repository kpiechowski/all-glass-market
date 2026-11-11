<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;

/** The Eurocodes of a model as choices for the Eurocode actions; the main one is marked. */
final class EurocodeOptions
{
    /** @return array<string, string> */
    public static function of(GlassModelModel $record): array
    {
        return $record->eurocodes
            ->sortBy('id')
            ->mapWithKeys(fn ($row): array => [
                $row->code => $row->is_main
                    ? $row->code.' ('.__('catalogue::resources.glass_model.fields.main_marker').')'
                    : $row->code,
            ])
            ->all();
    }
}
