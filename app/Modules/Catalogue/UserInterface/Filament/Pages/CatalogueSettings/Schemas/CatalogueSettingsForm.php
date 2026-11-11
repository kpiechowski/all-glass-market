<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Pages\CatalogueSettings\Schemas;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelThumbnails;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\DictionaryValueQueries;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

/**
 * Template D, layout (a): one section per mapping (FR-15-FR-17), a fieldset per dictionary value.
 * Fields are keyed by the value id: `positions.{id}.shape`, `colours.{id}.fill`, `equipment.{id}.pictogram`.
 * A field left empty (or the colour toggle off) means "unmapped", so FR-18's default applies.
 */
class CatalogueSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(1)
            ->components([
                self::positionsSection(),
                self::coloursSection(),
                self::equipmentSection(),
            ]);
    }

    private static function positionsSection(): Section
    {
        return Section::make(__('catalogue::pages.catalogue_settings.positions.heading'))
            ->description(__('catalogue::pages.catalogue_settings.positions.description'))
            ->schema(self::valueFieldsets(Dictionary::Position, fn (int $id): array => [
                Select::make("positions.{$id}.shape")
                    ->label(__('catalogue::pages.catalogue_settings.positions.shape'))
                    ->options(self::shapeOptions())
                    ->allowHtml()
                    ->placeholder(__('catalogue::pages.catalogue_settings.positions.unmapped'))
                    ->live()
                    ->columnSpan(['default' => 'full', 'md' => 8]),
                Toggle::make("positions.{$id}.mirrored")
                    ->label(__('catalogue::pages.catalogue_settings.positions.mirrored'))
                    ->visible(fn (Get $get): bool => filled($get("positions.{$id}.shape")))
                    ->columnSpan(['default' => 'full', 'md' => 4]),
            ]));
    }

    private static function coloursSection(): Section
    {
        return Section::make(__('catalogue::pages.catalogue_settings.colours.heading'))
            ->description(__('catalogue::pages.catalogue_settings.colours.description'))
            ->schema(self::valueFieldsets(Dictionary::Colour, fn (int $id): array => [
                Toggle::make("colours.{$id}.custom")
                    ->label(__('catalogue::pages.catalogue_settings.colours.custom'))
                    ->live()
                    ->columnSpan(['default' => 'full', 'md' => 4]),
                ColorPicker::make("colours.{$id}.fill")
                    ->label(__('catalogue::pages.catalogue_settings.colours.fill'))
                    ->required(fn (Get $get): bool => (bool) $get("colours.{$id}.custom"))
                    ->visible(fn (Get $get): bool => (bool) $get("colours.{$id}.custom"))
                    ->columnSpan(['default' => 'full', 'md' => 4]),
                TextInput::make("colours.{$id}.opacity")
                    ->label(__('catalogue::pages.catalogue_settings.colours.opacity'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->required(fn (Get $get): bool => (bool) $get("colours.{$id}.custom"))
                    ->visible(fn (Get $get): bool => (bool) $get("colours.{$id}.custom"))
                    ->columnSpan(['default' => 'full', 'md' => 4]),
            ]));
    }

    private static function equipmentSection(): Section
    {
        return Section::make(__('catalogue::pages.catalogue_settings.equipment.heading'))
            ->description(__('catalogue::pages.catalogue_settings.equipment.description'))
            ->schema(self::valueFieldsets(Dictionary::Equipment, fn (int $id): array => [
                Select::make("equipment.{$id}.pictogram")
                    ->label(__('catalogue::pages.catalogue_settings.equipment.pictogram'))
                    ->options(self::pictogramOptions())
                    ->allowHtml()
                    ->placeholder(__('catalogue::pages.catalogue_settings.equipment.unmapped'))
                    ->columnSpan(['default' => 'full', 'md' => 8]),
            ]));
    }

    /**
     * One fieldset per value of the dictionary, titled with its name.
     *
     * @param  callable(int): array<mixed>  $fields
     * @return list<Fieldset>
     */
    private static function valueFieldsets(Dictionary $dictionary, callable $fields): array
    {
        /** @var Collection<int, DictionaryValueModel> $values */
        $values = app(DictionaryValueQueries::class)->ofDictionary($dictionary)->orderBy('name')->get();

        return $values
            ->map(fn (DictionaryValueModel $value): Fieldset => Fieldset::make($value->name)
                ->columns(['default' => 1, 'md' => 12])
                ->schema($fields($value->id)))
            ->all();
    }

    /** @return array<string, string> */
    private static function shapeOptions(): array
    {
        $thumbnails = app(GlassModelThumbnails::class);
        $options = [];

        foreach (GlassShape::cases() as $shape) {
            $options[$shape->value] = self::withPreview($thumbnails->shapeSvg($shape), $shape->getLabel(), 'shape');
        }

        return $options;
    }

    /** @return array<string, string> */
    private static function pictogramOptions(): array
    {
        $thumbnails = app(GlassModelThumbnails::class);
        $options = [];

        foreach (Pictogram::cases() as $pictogram) {
            $options[$pictogram->value] = self::withPreview($thumbnails->pictogramSvg($pictogram), $pictogram->getLabel(), 'pictogram');
        }

        return $options;
    }

    /** Trusted SVG from the shipped set plus an escaped label, for a Select that allows HTML. */
    private static function withPreview(string $svg, string $label, string $kind): string
    {
        return '<span class="glass-thumbnail-option glass-thumbnail-option--'.$kind.'">'.$svg.'<span>'.e($label).'</span></span>';
    }
}
