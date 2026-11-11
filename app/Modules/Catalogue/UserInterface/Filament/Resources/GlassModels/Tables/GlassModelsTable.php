<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\DeleteGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\RestoreGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\WithdrawGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Schemas\GlassModelInfolist;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Template A (list), plain: cards, search and filters come with ticket 06. */
class GlassModelsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->recordUrl(fn (GlassModelModel $record): string => GlassModelResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    WithdrawGlassModelAction::make(),
                    RestoreGlassModelAction::make(),
                    DeleteGlassModelAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedRectangleStack,
            heading: __('catalogue::resources.glass_model.empty.heading'),
            description: __('catalogue::resources.glass_model.empty.description'),
            actions: [CreateAction::make()->label(__('catalogue::resources.glass_model.actions.create.label'))],
        );
    }

    /** @return array<mixed> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (GlassModelModel $record): string => (string) $record->mainEurocode?->code)
                ->subtitle(fn (GlassModelModel $record): string => implode(' · ', array_filter([$record->position?->name, $record->colour?->name])))
                ->status(fn (GlassModelModel $record): array => self::state($record)),

            TextColumn::make('mainEurocode.code')
                ->label(__('catalogue::resources.glass_model.fields.main_eurocode'))
                ->weight('bold')
                ->visibleFrom('md'),

            TextColumn::make('position.name')
                ->label(__('catalogue::resources.glass_model.fields.position'))
                ->visibleFrom('md'),

            TextColumn::make('colour.name')
                ->label(__('catalogue::resources.glass_model.fields.colour'))
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('withdrawn')
                ->label(__('catalogue::resources.glass_model.fields.state'))
                ->badge()
                ->formatStateUsing(fn (bool $state): string => GlassModelInfolist::stateLabel($state))
                ->color(fn (bool $state): string => $state ? 'gray' : 'success')
                ->visibleFrom('md'),
        ];
    }

    /** @return array{label: string, color: string} */
    private static function state(GlassModelModel $record): array
    {
        return [
            'label' => GlassModelInfolist::stateLabel($record->withdrawn),
            'color' => $record->withdrawn ? 'gray' : 'success',
        ];
    }
}
