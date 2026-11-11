<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Schemas;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Support\VehicleYears;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Template C, layout (a): one sheet section. */
class VehicleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::vehicleSection(),
        ]);
    }

    private static function vehicleSection(): Section
    {
        return Section::make(__('catalogue::resources.vehicle.sections.vehicle'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('name')->label(__('catalogue::resources.vehicle.fields.name')),
                TextEntry::make('make.name')->label(__('catalogue::resources.vehicle.fields.make')),
                TextEntry::make('model')->label(__('catalogue::resources.vehicle.fields.model'))->placeholder('—'),
                TextEntry::make('body.name')->label(__('catalogue::resources.vehicle.fields.body'))->placeholder('—'),
                TextEntry::make('generation')->label(__('catalogue::resources.vehicle.fields.generation'))->placeholder('—'),
                TextEntry::make('years')
                    ->label(__('catalogue::resources.vehicle.fields.years'))
                    ->state(fn (VehicleModel $record): ?string => VehicleYears::label($record))
                    ->placeholder('—'),
                TextEntry::make('argic_code')->label(__('catalogue::resources.vehicle.fields.argic_code'))->placeholder('—'),
            ]);
    }
}
