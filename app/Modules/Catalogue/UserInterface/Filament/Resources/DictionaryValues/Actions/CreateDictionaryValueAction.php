<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\CreateDictionaryValue\CreateDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/** Template E, short form: the dictionary defaults to the list tab the admin is on. */
final class CreateDictionaryValueAction
{
    public static function make(): Action
    {
        return Action::make('create')
            ->label(__('catalogue::resources.dictionary_value.actions.create.label'))
            ->modalHeading(__('catalogue::resources.dictionary_value.actions.create.heading'))
            ->modalSubmitActionLabel(__('catalogue::resources.dictionary_value.actions.create.submit'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->fillForm(fn ($livewire): array => [
                'dictionary' => Dictionary::tryFrom((string) ($livewire->activeTab ?? ''))?->value ?? Dictionary::Position->value,
            ])
            ->schema([
                Select::make('dictionary')
                    ->label(__('catalogue::resources.dictionary_value.fields.dictionary'))
                    ->options(collect(Dictionary::cases())->mapWithKeys(fn (Dictionary $dictionary): array => [$dictionary->value => $dictionary->getLabel()])->all())
                    ->required()
                    ->native(false),
                TextInput::make('name')
                    ->label(__('catalogue::resources.dictionary_value.fields.name'))
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data): void {
                CommandSender::send(new CreateDictionaryValueCommand(
                    actor: CurrentActor::get(),
                    dictionary: $data['dictionary'],
                    name: $data['name'],
                ));

                Notification::make()->success()->title(__('catalogue::resources.dictionary_value.actions.create.done'))->send();
            });
    }
}
