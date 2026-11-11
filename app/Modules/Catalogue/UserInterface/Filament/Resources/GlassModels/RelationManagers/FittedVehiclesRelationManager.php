<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\RelationManagers;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Catalogue\Application\Commands\FitVehicles\FitVehiclesCommand;
use App\Modules\Catalogue\Application\Commands\RemoveFitment\RemoveFitmentCommand;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\VehicleQueries;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Support\VehicleYears;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The "Dopasowanie" tab of a Glass model (FR-10, template C): the vehicles the model fits. The
 * fitment is edited here, and every write is a command; the relationship only feeds the table.
 */
class FittedVehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return is_subclass_of($pageClass, ViewRecord::class)
            && CurrentActor::can(CataloguePermission::Manage);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalogue::resources.glass_model.fitment.title');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('catalogue::resources.vehicle.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->url(fn (VehicleModel $record): string => VehicleResource::getUrl('view', ['record' => $record])),
                TextColumn::make('make.name')
                    ->label(__('catalogue::resources.vehicle.fields.make')),
                TextColumn::make('years')
                    ->label(__('catalogue::resources.vehicle.fields.years'))
                    ->state(fn (VehicleModel $record): ?string => VehicleYears::label($record))
                    ->placeholder('—'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('make'))
            ->headerActions([$this->fitAction()])
            ->recordActions([$this->removeAction()])
            ->defaultSort('name')
            ->paginated([10, 25, 50]);

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedTruck,
            heading: __('catalogue::resources.glass_model.fitment.empty.heading'),
            description: __('catalogue::resources.glass_model.fitment.empty.description'),
        );
    }

    private function fitAction(): Action
    {
        return Action::make('fitVehicles')
            ->label(__('catalogue::resources.glass_model.fitment.fit.label'))
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading(__('catalogue::resources.glass_model.fitment.fit.heading'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model.fitment.fit.label'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->schema([
                Select::make('vehicle_ids')
                    ->label(__('catalogue::resources.vehicle.plural_label'))
                    ->multiple()
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => app(VehicleQueries::class)
                        ->searchByName($search)
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all())
                    ->getOptionLabelsUsing(fn (array $values): array => VehicleModel::query()
                        ->whereKey($values)
                        ->pluck('name', 'id')
                        ->all()),
            ])
            ->action(function (array $data): void {
                /** @var GlassModelModel $owner */
                $owner = $this->getOwnerRecord();

                CommandSender::send(new FitVehiclesCommand(
                    CurrentActor::get(),
                    (int) $owner->getKey(),
                    array_map('intval', $data['vehicle_ids']),
                ));

                Notification::make()->success()->title(__('catalogue::resources.glass_model.fitment.fit.done'))->send();
            });
    }

    private function removeAction(): Action
    {
        return Action::make('removeFitment')
            ->label(__('catalogue::resources.glass_model.fitment.remove.label'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('catalogue::resources.glass_model.fitment.remove.heading'))
            ->modalDescription(__('catalogue::resources.glass_model.fitment.remove.description'))
            ->modalSubmitActionLabel(__('catalogue::resources.glass_model.fitment.remove.label'))
            ->visible(fn (): bool => CurrentActor::can(CataloguePermission::Manage))
            ->action(function (VehicleModel $record): void {
                /** @var GlassModelModel $owner */
                $owner = $this->getOwnerRecord();

                CommandSender::send(new RemoveFitmentCommand(CurrentActor::get(), (int) $owner->getKey(), (int) $record->getKey()));

                Notification::make()->success()->title(__('catalogue::resources.glass_model.fitment.remove.done'))->send();
            });
    }
}
