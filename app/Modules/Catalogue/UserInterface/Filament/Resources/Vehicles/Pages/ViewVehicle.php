<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages;

use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Actions\DeleteVehicleAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Template C (record view): the infolist is tab 1, the audit history follows. */
class ViewVehicle extends ViewRecord
{
    protected static string $resource = VehicleResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return $this->getCachedRelationManagers() !== [];
    }

    public function getContentTabLabel(): ?string
    {
        return __('catalogue::resources.vehicle.tabs.details');
    }

    /** Edit is primary; Usuń sits in "⋮". */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ActionGroup::make([
                DeleteVehicleAction::make(thenGoToList: true),
            ]),
        ];
    }
}
