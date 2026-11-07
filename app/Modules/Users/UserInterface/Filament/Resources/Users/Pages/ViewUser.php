<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\UserInterface\Filament\Resources\Users\Actions\ReactivateUserAction;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Actions\UserRecordMenu;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

/** Template C (record view): the infolist is tab 1, the audit history ("Historia zmian") follows lazily. */
class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    /** No lone "Szczegóły" tab when the user sees no relation manager. */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return $this->getCachedRelationManagers() !== [];
    }

    public function getContentTabLabel(): ?string
    {
        return __('users::resources.user.tabs.details');
    }

    /** Edit is primary; Aktywuj ponownie is the one secondary on an inactive account; the rest in "⋮". */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Model $record): bool => UserResource::canEdit($record)),
            ReactivateUserAction::make(),
            UserRecordMenu::make(),
        ];
    }
}
