<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\RenameDictionaryValue\RenameDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/** Template E, short form. FR-14: the new name takes effect wherever the value is used. */
final class RenameDictionaryValueAction
{
    public static function make(): Action
    {
        return Action::make('rename')
            ->label(__('catalogue::resources.dictionary_value.actions.rename.label'))
            ->modalHeading(__('catalogue::resources.dictionary_value.actions.rename.heading'))
            ->modalSubmitActionLabel(__('catalogue::resources.dictionary_value.actions.rename.submit'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->fillForm(fn (DictionaryValueModel $record): array => ['name' => $record->name])
            ->schema([
                TextInput::make('name')
                    ->label(__('catalogue::resources.dictionary_value.fields.name'))
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (DictionaryValueModel $record, array $data): void {
                CommandSender::send(new RenameDictionaryValueCommand(CurrentActor::get(), (int) $record->getKey(), $data['name']));

                $record->refresh();

                Notification::make()->success()->title(__('catalogue::resources.dictionary_value.actions.rename.done'))->send();
            });
    }
}
