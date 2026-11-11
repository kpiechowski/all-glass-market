<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Pages;

use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\DeleteDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\RenameDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\RestoreDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\WithdrawDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\DictionaryValueResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

/** Template C (record view): the infolist is tab 1, the audit history follows. FR-25: sections of other modules sit in the infolist. */
class ViewDictionaryValue extends ViewRecord
{
    protected static string $resource = DictionaryValueResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return $this->getCachedRelationManagers() !== [];
    }

    public function getContentTabLabel(): ?string
    {
        return __('catalogue::resources.dictionary_value.tabs.details');
    }

    /** Rename is primary, Przywróć the one secondary on a withdrawn value; Wycofaj and Usuń in "⋮". */
    protected function getHeaderActions(): array
    {
        return [
            RenameDictionaryValueAction::make()->color('primary'),
            RestoreDictionaryValueAction::make(),
            ActionGroup::make([
                WithdrawDictionaryValueAction::make(),
                DeleteDictionaryValueAction::make(thenGoToList: true),
            ]),
        ];
    }
}
