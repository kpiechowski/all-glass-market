<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\ChangeMainEurocode\ChangeMainEurocodeCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/** Template E, modal with a form (WF-02 change main). */
final class ChangeMainEurocodeAction
{
    public static function make(): Action
    {
        return Action::make('changeMainEurocode')
            ->label(__('catalogue::resources.glass_model.actions.change_main_eurocode.label'))
            ->modalHeading(__('catalogue::resources.glass_model.actions.change_main_eurocode.heading'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model.actions.change_main_eurocode.label'))
            ->visible(fn (GlassModelModel $record): bool => $record->eurocodes->count() > 1
                && CurrentActor::can(CataloguePermission::Manage))
            ->schema([
                Select::make('eurocode')
                    ->label(__('catalogue::resources.glass_model.fields.eurocode'))
                    ->options(fn (GlassModelModel $record): array => EurocodeOptions::of($record))
                    ->required()
                    ->native(false),
            ])
            ->action(function (GlassModelModel $record, array $data): void {
                CommandSender::send(new ChangeMainEurocodeCommand(CurrentActor::get(), (int) $record->getKey(), (string) $data['eurocode']));

                $record->refresh();

                Notification::make()->success()->title(__('catalogue::resources.glass_model.actions.change_main_eurocode.done'))->send();
            });
    }
}
