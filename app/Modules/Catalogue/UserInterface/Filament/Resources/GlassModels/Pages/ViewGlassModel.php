<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\DeleteGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\RestoreGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\WithdrawGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Template C (record view): the infolist is tab 1, the fitment and the audit history follow. FR-25: sections of other modules sit in the infolist. */
class ViewGlassModel extends ViewRecord
{
    protected static string $resource = GlassModelResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return $this->getCachedRelationManagers() !== [];
    }

    public function getContentTabLabel(): ?string
    {
        return __('catalogue::resources.glass_model.tabs.details');
    }

    /** Edit is primary, Przywróć the one secondary on a withdrawn model; Wycofaj and Usuń in "⋮". The Eurocode actions sit in the header of the infolist section "Szkło". */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            RestoreGlassModelAction::make(),
            ActionGroup::make([
                WithdrawGlassModelAction::make(),
                DeleteGlassModelAction::make(thenGoToList: true),
            ]),
        ];
    }
}
