<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\UserInterface\Filament\Resources\Users\Actions\DeactivateUserAction;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Actions\ImpersonateUserAction;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Actions\ReactivateUserAction;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Model $record): bool => UserResource::canEdit($record)),
            ImpersonateUserAction::make(),
            DeactivateUserAction::make(),
            ReactivateUserAction::make(),
        ];
    }
}
