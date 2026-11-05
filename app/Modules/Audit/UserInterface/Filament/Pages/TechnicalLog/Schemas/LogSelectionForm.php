<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Pages\TechnicalLog\Schemas;

use App\Modules\Audit\Application\Queries\TechnicalLog\LogFiles;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

/** The channel and day pickers of the Technical log page (FR-16). */
class LogSelectionForm
{
    public static function configure(Schema $schema, callable $onChange): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('channel')
                        ->label(__('audit::pages.technical_log.fields.channel'))
                        ->options(fn (): array => app(LogFiles::class)->channels())
                        ->required()
                        ->selectablePlaceholder(false)
                        ->live()
                        ->afterStateUpdated($onChange),
                    DatePicker::make('day')
                        ->label(__('audit::pages.technical_log.fields.day'))
                        ->maxDate(now())
                        ->required()
                        ->live()
                        ->afterStateUpdated($onChange),
                ]),
            ])
            ->statePath('data');
    }
}
