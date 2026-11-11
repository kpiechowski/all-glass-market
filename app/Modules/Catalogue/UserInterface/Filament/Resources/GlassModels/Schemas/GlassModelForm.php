<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Schemas;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\DictionaryValueQueries;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Template B, layout (a). The Eurocode is entered on creation and shown locked on edit: Eurocodes
 * are managed on the model's page (ticket 04). Options come from `DictionaryValueQueries`; nothing
 * here saves a relation, the ids travel in the command.
 */
class GlassModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::identitySection(),
            self::codesSection(),
        ]);
    }

    private static function identitySection(): Section
    {
        return Section::make(__('catalogue::resources.glass_model.sections.identity'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('main_eurocode')
                    ->label(__('catalogue::resources.glass_model.fields.main_eurocode'))
                    ->helperText(fn (?GlassModelModel $record): string => __($record === null
                        ? 'catalogue::resources.glass_model.helpers.main_eurocode'
                        : 'catalogue::resources.glass_model.helpers.main_eurocode_locked'))
                    ->required()
                    ->disabled(fn (?GlassModelModel $record): bool => $record !== null)
                    ->dehydrated(fn (?GlassModelModel $record): bool => $record === null)
                    ->columnSpan(['default' => 'full', 'md' => 6]),
                TagsInput::make('further_eurocodes')
                    ->label(__('catalogue::resources.glass_model.fields.further_eurocodes'))
                    ->helperText(__('catalogue::resources.glass_model.helpers.further_eurocodes'))
                    ->visible(fn (?GlassModelModel $record): bool => $record === null)
                    ->columnSpan(['default' => 'full', 'md' => 6]),
                Select::make('position_id')
                    ->label(__('catalogue::resources.glass_model.fields.position'))
                    ->options(fn (?GlassModelModel $record): array => self::options(Dictionary::Position, $record === null ? [] : [$record->position_id]))
                    ->required()
                    ->native(false)
                    ->columnSpan(['default' => 'full', 'md' => 6]),
                Select::make('colour_id')
                    ->label(__('catalogue::resources.glass_model.fields.colour'))
                    ->options(fn (?GlassModelModel $record): array => self::options(Dictionary::Colour, $record === null ? [] : [$record->colour_id]))
                    ->native(false)
                    ->columnSpan(['default' => 'full', 'md' => 6]),
                Select::make('equipment_ids')
                    ->label(__('catalogue::resources.glass_model.fields.equipment'))
                    ->options(fn (?GlassModelModel $record): array => self::options(Dictionary::Equipment, $record === null ? [] : $record->equipment->modelKeys()))
                    ->multiple()
                    ->native(false)
                    ->columnSpan(['default' => 'full', 'md' => 6]),
            ]);
    }

    private static function codesSection(): Section
    {
        return Section::make(__('catalogue::resources.glass_model.sections.codes'))
            ->schema([
                TagsInput::make('oem_codes')
                    ->label(__('catalogue::resources.glass_model.fields.oem_codes'))
                    ->helperText(__('catalogue::resources.glass_model.helpers.oem_codes')),
            ]);
    }

    /**
     * @param  array<int|null>  $held  values the record already holds, offered even when withdrawn
     * @return array<int, string>
     */
    private static function options(Dictionary $dictionary, array $held): array
    {
        return app(DictionaryValueQueries::class)
            ->choosable($dictionary, array_values(array_filter($held)))
            ->pluck('name', 'id')
            ->all();
    }
}
