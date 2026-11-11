<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\RestoreGlassModel\RestoreGlassModelCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** Template E, confirmation (WF-04). */
final class RestoreGlassModelAction
{
    public static function make(): Action
    {
        return Action::make('restore')
            ->label(__('catalogue::resources.glass_model.actions.restore.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('catalogue::resources.glass_model.actions.restore.label'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model.actions.restore.label'))
            ->modalSubmitAction(fn (Action $action): Action => $action->color('danger'))
            ->visible(fn (GlassModelModel $record): bool => $record->withdrawn
                && CurrentActor::can(CataloguePermission::Manage))
            ->action(function (GlassModelModel $record): void {
                CommandSender::send(new RestoreGlassModelCommand(CurrentActor::get(), (int) $record->getKey()));

                $record->refresh();

                Notification::make()->success()->title(__('catalogue::resources.glass_model.actions.restore.done'))->send();
            });
    }
}
