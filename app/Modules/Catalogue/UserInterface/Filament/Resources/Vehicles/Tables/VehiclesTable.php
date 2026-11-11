<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Actions\DeleteVehicleAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Support\VehicleYears;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

/** Template A (list). Search covers the name and every field (FR-09). */
class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->filters(VehiclesTableFilters::make(), layout: FiltersLayout::AboveContent)
            ->recordUrl(fn (VehicleModel $record): string => VehicleResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteVehicleAction::make(),
                ]),
            ])
            ->defaultSort('name');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedTruck,
            heading: __('catalogue::resources.vehicle.empty.heading'),
            description: __('catalogue::resources.vehicle.empty.description'),
            actions: [CreateAction::make()],
        );
    }

    /** @return array<mixed> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (VehicleModel $record): string => (string) $record->name)
                ->subtitle(fn (VehicleModel $record): ?string => VehicleYears::label($record)),

            TextColumn::make('name')
                ->label(__('catalogue::resources.vehicle.fields.name'))
                ->searchable()
                ->sortable()
                ->visibleFrom('md'),

            TextColumn::make('make.name')
                ->label(__('catalogue::resources.vehicle.fields.make'))
                ->searchable()
                ->visibleFrom('md'),

            TextColumn::make('model')
                ->label(__('catalogue::resources.vehicle.fields.model'))
                ->searchable()
                ->sortable()
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('body.name')
                ->label(__('catalogue::resources.vehicle.fields.body'))
                ->searchable()
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('generation')
                ->label(__('catalogue::resources.vehicle.fields.generation'))
                ->searchable()
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('years')
                ->label(__('catalogue::resources.vehicle.fields.years'))
                ->state(fn (VehicleModel $record): ?string => VehicleYears::label($record))
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('glass_models_count')
                ->label(__('catalogue::resources.vehicle.fields.glass_models_count'))
                ->sortable()
                ->visibleFrom('md'),

            TextColumn::make('argic_code')
                ->label(__('catalogue::resources.vehicle.fields.argic_code'))
                ->searchable()
                ->placeholder('—')
                ->visibleFrom('md'),
        ];
    }
}
