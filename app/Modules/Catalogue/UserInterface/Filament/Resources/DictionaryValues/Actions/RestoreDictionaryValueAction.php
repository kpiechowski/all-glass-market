<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\RestoreDictionaryValue\RestoreDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** FR-13: harmless and immediate, so no confirmation. */
final class RestoreDictionaryValueAction
{
    public static function make(): Action
    {
        return Action::make('restore')
            ->label(__('catalogue::resources.dictionary_value.actions.restore.label'))
            ->color('gray')
            ->visible(fn (DictionaryValueModel $record): bool => $record->withdrawn
                && CurrentActor::can(CataloguePermission::Manage))
            ->action(function (DictionaryValueModel $record): void {
                CommandSender::send(new RestoreDictionaryValueCommand(CurrentActor::get(), (int) $record->getKey()));

                $record->refresh();

                Notification::make()->success()->title(__('catalogue::resources.dictionary_value.actions.restore.done'))->send();
            });
    }
}
