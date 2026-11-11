<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\AddEurocode\AddEurocodeCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/** Template E, modal with a form (WF-02 add). */
final class AddEurocodeAction
{
    public static function make(): Action
    {
        return Action::make('addEurocode')
            ->label(__('catalogue::resources.glass_model.actions.add_eurocode.label'))
            ->icon('heroicon-o-plus')
            ->modalHeading(__('catalogue::resources.glass_model.actions.add_eurocode.heading'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model.actions.add_eurocode.label'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->schema([
                TextInput::make('eurocode')
                    ->label(__('catalogue::resources.glass_model.fields.eurocode'))
                    ->helperText(__('catalogue::resources.glass_model.helpers.main_eurocode'))
                    ->required(),
            ])
            ->action(function (GlassModelModel $record, array $data): void {
                CommandSender::send(new AddEurocodeCommand(CurrentActor::get(), (int) $record->getKey(), (string) $data['eurocode']));

                $record->refresh();

                Notification::make()->success()->title(__('catalogue::resources.glass_model.actions.add_eurocode.done'))->send();
            });
    }
}
