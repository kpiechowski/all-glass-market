<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\WithdrawDictionaryValue\WithdrawDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** Template E, confirmation (FR-13). A menu item, grey: withdrawing deletes nothing. */
final class WithdrawDictionaryValueAction
{
    public static function make(): Action
    {
        return Action::make('withdraw')
            ->label(__('catalogue::resources.dictionary_value.actions.withdraw.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('catalogue::resources.dictionary_value.actions.withdraw.heading'))
            ->modalDescription(__('catalogue::resources.dictionary_value.actions.withdraw.description'))
            ->modalSubmitActionLabel(__('catalogue::resources.dictionary_value.actions.withdraw.label'))
            ->visible(fn (DictionaryValueModel $record): bool => ! $record->withdrawn
                && CurrentActor::can(CataloguePermission::Manage))
            ->action(function (DictionaryValueModel $record): void {
                CommandSender::send(new WithdrawDictionaryValueCommand(CurrentActor::get(), (int) $record->getKey()));

                $record->refresh();

                Notification::make()->success()->title(__('catalogue::resources.dictionary_value.actions.withdraw.done'))->send();
            });
    }
}
