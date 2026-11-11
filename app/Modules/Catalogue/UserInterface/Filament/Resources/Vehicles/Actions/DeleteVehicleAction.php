<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\DeleteVehicle\DeleteVehicleCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** Template E, dangerous confirmation (FR-11, WF-05): the warning says how many Glass models the vehicle fits. */
final class DeleteVehicleAction
{
    /** @param  bool  $thenGoToList  on the vehicle's own page, which no longer exists afterwards */
    public static function make(bool $thenGoToList = false): Action
    {
        return Action::make('delete')
            ->label(__('catalogue::resources.vehicle.actions.delete.label'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('catalogue::resources.vehicle.actions.delete.heading'))
            ->modalDescription(function (VehicleModel $record): string {
                $count = $record->glassModels()->count();

                return $count === 0
                    ? __('catalogue::resources.vehicle.actions.delete.description')
                    : __('catalogue::resources.vehicle.actions.delete.description_fitted', ['count' => $count]);
            })
            ->modalSubmitActionLabel(__('catalogue::resources.vehicle.actions.delete.label'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->action(function (VehicleModel $record, Action $action) use ($thenGoToList): void {
                CommandSender::send(new DeleteVehicleCommand(CurrentActor::get(), (int) $record->getKey()));

                Notification::make()->success()->title(__('catalogue::resources.vehicle.actions.delete.done'))->send();

                if ($thenGoToList) {
                    $action->redirect(VehicleResource::getUrl('index'));
                }
            });
    }
}
