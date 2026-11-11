<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\DeleteDictionaryValue\DeleteDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\DictionaryValueResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Template E, dangerous confirmation (FR-24). The handler refuses a value that is in use and says
 * where; the admin then withdraws it instead.
 */
final class DeleteDictionaryValueAction
{
    /** @param  bool  $thenGoToList  on the value's own page, which no longer exists afterwards */
    public static function make(bool $thenGoToList = false): Action
    {
        return Action::make('delete')
            ->label(__('catalogue::resources.dictionary_value.actions.delete.label'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('catalogue::resources.dictionary_value.actions.delete.heading'))
            ->modalDescription(__('catalogue::resources.dictionary_value.actions.delete.description'))
            ->modalSubmitActionLabel(__('catalogue::resources.dictionary_value.actions.delete.label'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->action(function (DictionaryValueModel $record, Action $action) use ($thenGoToList): void {
                CommandSender::send(new DeleteDictionaryValueCommand(CurrentActor::get(), (int) $record->getKey()));

                Notification::make()->success()->title(__('catalogue::resources.dictionary_value.actions.delete.done'))->send();

                if ($thenGoToList) {
                    $action->redirect(DictionaryValueResource::getUrl('index'));
                }
            });
    }
}
