<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\DeleteGlassModel\DeleteGlassModelCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** Template E, confirmation (WF-04). */
final class DeleteGlassModelAction
{
    /** @param  bool  $thenGoToList  on the model's own page, which no longer exists afterwards */
    public static function make(bool $thenGoToList = false): Action
    {
        return Action::make('delete')
            ->label(__('catalogue::resources.glass_model.actions.delete.label'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('catalogue::resources.glass_model.actions.delete.heading'))
            ->modalDescription(__('catalogue::resources.glass_model.actions.delete.description'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model.actions.delete.label'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->action(function (GlassModelModel $record, Action $action) use ($thenGoToList): void {
                CommandSender::send(new DeleteGlassModelCommand(CurrentActor::get(), (int) $record->getKey()));

                Notification::make()->success()->title(__('catalogue::resources.glass_model.actions.delete.done'))->send();
                if ($thenGoToList) {
                    $action->redirect(GlassModelResource::getUrl('index'));
                }
            });
    }
}
