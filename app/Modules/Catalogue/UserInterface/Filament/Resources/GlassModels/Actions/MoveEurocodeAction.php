<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\Commands\MoveEurocode\MoveEurocodeCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\GlassModelQueries;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/** Template E, modal with a form (WF-02 move): the target is an active or a withdrawn model. */
final class MoveEurocodeAction
{
    public static function make(): Action
    {
        return Action::make('moveEurocode')
            ->label(__('catalogue::resources.glass_model.actions.move_eurocode.label'))
            ->modalHeading(__('catalogue::resources.glass_model.actions.move_eurocode.heading'))
            ->modalDescription(__('catalogue::resources.glass_model.actions.move_eurocode.description'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model.actions.move_eurocode.label'))
            ->visible(fn (GlassModelModel $record): bool => $record->eurocodes->count() > 1
                && CurrentActor::can(CataloguePermission::Manage))
            ->schema([
                Select::make('eurocode')
                    ->label(__('catalogue::resources.glass_model.fields.eurocode'))
                    ->options(fn (GlassModelModel $record): array => EurocodeOptions::of($record))
                    ->required()
                    ->native(false),
                Select::make('target_id')
                    ->label(__('catalogue::resources.glass_model.fields.target_model'))
                    ->helperText(__('catalogue::resources.glass_model.helpers.target_model'))
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search, GlassModelModel $record): array => app(GlassModelQueries::class)
                        ->moveTargets($search, (int) $record->getKey())
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (GlassModelModel $model): array => [$model->getKey() => (string) $model->mainEurocode?->code])
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => GlassModelModel::query()->with('mainEurocode')->find($value)?->mainEurocode?->code),
            ])
            ->action(function (GlassModelModel $record, array $data): void {
                CommandSender::send(new MoveEurocodeCommand(
                    CurrentActor::get(),
                    (int) $record->getKey(),
                    (string) $data['eurocode'],
                    (int) $data['target_id'],
                ));

                $record->refresh();

                Notification::make()->success()->title(__('catalogue::resources.glass_model.actions.move_eurocode.done'))->send();
            });
    }
}
