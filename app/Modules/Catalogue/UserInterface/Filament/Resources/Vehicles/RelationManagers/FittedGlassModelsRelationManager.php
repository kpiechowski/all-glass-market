<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\RelationManagers;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** The "Pasujące modele szkła" tab of a vehicle (FR-09): read-only, since fitment is edited from the Glass model. */
class FittedGlassModelsRelationManager extends RelationManager
{
    protected static string $relationship = 'glassModels';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return is_subclass_of($pageClass, ViewRecord::class);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalogue::resources.vehicle.fitment.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mainEurocode.code')
                    ->label(__('catalogue::resources.glass_model.fields.main_eurocode'))
                    ->url(fn (GlassModelModel $record): string => GlassModelResource::getUrl('view', ['record' => $record])),
                TextColumn::make('position.name')
                    ->label(__('catalogue::resources.glass_model.fields.position'))
                    ->badge()
                    ->color('gray'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['mainEurocode', 'position']))
            ->emptyStateHeading(__('catalogue::resources.vehicle.fitment.empty'))
            ->paginated([10, 25, 50]);
    }
}
