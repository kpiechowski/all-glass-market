<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/** Template A (list): one primary action. */
class ListGlassModels extends ListRecords
{
    protected static string $resource = GlassModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('catalogue::resources.glass_model.actions.create.label')),
        ];
    }
}
