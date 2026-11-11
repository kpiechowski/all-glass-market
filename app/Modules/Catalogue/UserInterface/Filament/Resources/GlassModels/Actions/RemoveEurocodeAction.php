<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\RemoveEurocode\RemoveEurocodeCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/** Template E, confirmation with a choice (WF-02 remove). */
final class RemoveEurocodeAction
{
    public static function make(): Action
    {
        return Action::make('removeEurocode')
            ->label(__('catalogue::resources.glass_model.actions.remove_eurocode.label'))
            ->color('danger')
            ->modalHeading(__('catalogue::resources.glass_model.actions.remove_eurocode.heading'))
            ->modalDescription(__('catalogue::resources.glass_model.actions.remove_eurocode.description'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model.actions.remove_eurocode.label'))
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
                CommandSender::send(new RemoveEurocodeCommand(CurrentActor::get(), (int) $record->getKey(), (string) $data['eurocode']));

                $record->refresh();

                Notification::make()->success()->title(__('catalogue::resources.glass_model.actions.remove_eurocode.done'))->send();
            });
    }
}
