<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\AddEurocodeAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\ChangeMainEurocodeAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\DeleteGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\MoveEurocodeAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\RemoveEurocodeAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\RestoreGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\WithdrawGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Template C (record view). FR-25: sections of other modules sit in the infolist. */
class ViewGlassModel extends ViewRecord
{
    protected static string $resource = GlassModelResource::class;

    /** Edit is primary, Przywróć the one secondary on a withdrawn model; Wycofaj and Usuń in "⋮". */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            RestoreGlassModelAction::make(),
            ActionGroup::make([
                AddEurocodeAction::make(),
                ChangeMainEurocodeAction::make(),
                MoveEurocodeAction::make(),
                RemoveEurocodeAction::make(),
            ])->label(__('catalogue::resources.glass_model.actions.eurocodes.label'))
                ->icon('heroicon-o-hashtag')
                ->button()
                ->color('gray'),
            ActionGroup::make([
                WithdrawGlassModelAction::make(),
                DeleteGlassModelAction::make(thenGoToList: true),
            ]),
        ];
    }
}
