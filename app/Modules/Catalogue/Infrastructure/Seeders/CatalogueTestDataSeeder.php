<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Seeders;

use App\Modules\Catalogue\Domain\Contracts\CatalogueTestDataSeeding;
use App\Modules\Catalogue\Domain\Enums\CatalogueSeedSize;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\ThumbnailMappingModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use Illuminate\Support\Facades\DB;

/**
 * Test data for hand-checking the panel. Built on the model factories (the Core base factory opens
 * the write scope), deterministic, so a rerun after the wipe gives identical counts. Thumbnails of
 * Glass models are drawn from the mappings (D-57), so there are no Catalogue files to remove.
 */
final class CatalogueTestDataSeeder implements CatalogueTestDataSeeding
{
    /** Child tables first: the order the foreign keys allow. Every `catalogue_*` table must be here. */
    public const array TABLES = [
        'catalogue_thumbnail_mappings',
        'catalogue_glass_model_vehicles',
        'catalogue_glass_model_equipment',
        'catalogue_glass_model_oem_codes',
        'catalogue_glass_model_eurocodes',
        'catalogue_glass_models',
        'catalogue_vehicles',
        'catalogue_dictionary_values',
    ];

    /** @var array<string, array{shape: GlassShape, mirrored: bool}> */
    private const array POSITIONS = [
        'Przednia' => ['shape' => GlassShape::Windscreen, 'mirrored' => false],
        'Tylna' => ['shape' => GlassShape::Rear, 'mirrored' => false],
        'Boczna przednia lewa' => ['shape' => GlassShape::SideDrop, 'mirrored' => false],
        'Boczna przednia prawa' => ['shape' => GlassShape::SideDrop, 'mirrored' => true],
        'Boczna tylna lewa' => ['shape' => GlassShape::SideDrop, 'mirrored' => false],
        'Boczna tylna prawa' => ['shape' => GlassShape::SideDrop, 'mirrored' => true],
        'Boczna stała lewa' => ['shape' => GlassShape::SideFixed, 'mirrored' => false],
        'Boczna stała prawa' => ['shape' => GlassShape::SideFixed, 'mirrored' => true],
        'Dach panoramiczny' => ['shape' => GlassShape::Roof, 'mirrored' => false],
        'Szyba dachowa' => ['shape' => GlassShape::Roof, 'mirrored' => false],
        'Szyba trójkątna' => ['shape' => GlassShape::Accessory, 'mirrored' => false],
    ];

    /** @var array<string, array{fill: string, opacity: int}> */
    private const array COLOURS = [
        'Zielona' => ['fill' => '#7fb685', 'opacity' => 55],
        'Szara' => ['fill' => '#8d949b', 'opacity' => 60],
        'Brązowa' => ['fill' => '#8a6a4f', 'opacity' => 60],
        'Niebieska' => ['fill' => '#6b9bd1', 'opacity' => 55],
        'Przyciemniana' => ['fill' => '#3b4148', 'opacity' => 75],
    ];

    /** @var array<string, Pictogram> */
    private const array EQUIPMENT = [
        'Szyba ogrzewana' => Pictogram::Heated,
        'Czujnik deszczu' => Pictogram::RainSensor,
        'Kamera' => Pictogram::Camera,
        'Antena' => Pictogram::Antenna,
        'Szyba akustyczna' => Pictogram::Acoustic,
        'Wyświetlacz HUD' => Pictogram::HeadUpDisplay,
        'Czujnik światła' => Pictogram::Sensor,
        'Pakiet ADAS' => Pictogram::Universal,
    ];

    /** Withdrawn entries: unused by any active data, so the restore and withdraw paths can be tried. */
    private const array WITHDRAWN = [
        Dictionary::Position->value => ['Szyba przesuwna'],
        Dictionary::Colour->value => ['Fioletowa'],
        Dictionary::Equipment->value => ['Czujnik parkowania'],
        Dictionary::Producer->value => ['Guardian'],
        Dictionary::VehicleMake->value => ['Saab'],
        Dictionary::VehicleBody->value => ['Roadster'],
    ];

    private const array PRODUCERS = ['Pilkington', 'Saint-Gobain Sekurit', 'AGC', 'Fuyao', 'XYG'];

    private const array BODIES = ['Sedan', 'Hatchback', 'Kombi', 'SUV', 'Coupe', 'Van', 'Kabriolet'];

    /** @var array<string, list<string>> */
    private const array MAKES = [
        'Audi' => ['A3', 'A4', 'A6', 'Q5'],
        'BMW' => ['Seria 1', 'Seria 3', 'Seria 5', 'X3'],
        'Volkswagen' => ['Golf', 'Passat', 'Polo', 'Tiguan'],
        'Toyota' => ['Corolla', 'Yaris', 'RAV4', 'Avensis'],
        'Skoda' => ['Octavia', 'Fabia', 'Superb', 'Kodiaq'],
        'Ford' => ['Focus', 'Fiesta', 'Mondeo', 'Kuga'],
        'Opel' => ['Astra', 'Corsa', 'Insignia', 'Mokka'],
        'Renault' => ['Clio', 'Megane', 'Kadjar', 'Trafic'],
        'Peugeot' => ['208', '308', '3008', 'Partner'],
        'Mercedes-Benz' => ['Klasa A', 'Klasa C', 'Klasa E', 'Sprinter'],
    ];

    private const array GENERATIONS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];

    /** @var list<string> */
    private const array VARIANTS = ['AGN', 'ACL', 'AGS', 'BGN', 'BCL', 'AGG'];

    public function wipe(): void
    {
        DB::transaction(function (): void {
            foreach (self::TABLES as $table) {
                DB::table($table)->delete();
            }
        });
    }

    public function seed(CatalogueSeedSize $size): array
    {
        $dictionaries = $this->dictionaries();
        $vehicles = $this->vehicles($size->vehicles(), $dictionaries);
        $this->glassModels($size->glassModels(), $dictionaries, $vehicles);

        return $this->counts();
    }

    /**
     * @return array<string, array<string, DictionaryValueModel>> values by dictionary value and name
     */
    private function dictionaries(): array
    {
        $names = [
            Dictionary::Position->value => array_keys(self::POSITIONS),
            Dictionary::Colour->value => array_keys(self::COLOURS),
            Dictionary::Equipment->value => array_keys(self::EQUIPMENT),
            Dictionary::Producer->value => self::PRODUCERS,
            Dictionary::VehicleMake->value => array_keys(self::MAKES),
            Dictionary::VehicleBody->value => self::BODIES,
        ];

        $values = [];

        foreach (Dictionary::cases() as $dictionary) {
            foreach ($names[$dictionary->value] as $name) {
                $values[$dictionary->value][$name] = DictionaryValueModelFactory::new()->of($dictionary)->named($name)->create();
            }

            foreach (self::WITHDRAWN[$dictionary->value] as $name) {
                DictionaryValueModelFactory::new()->of($dictionary)->named($name)->withdrawn()->create();
            }
        }

        foreach (self::POSITIONS as $name => $drawing) {
            ThumbnailMappingModelFactory::new()->forPosition($values[Dictionary::Position->value][$name], $drawing['shape'], $drawing['mirrored'])->create();
        }

        foreach (self::COLOURS as $name => $drawing) {
            ThumbnailMappingModelFactory::new()->forColour($values[Dictionary::Colour->value][$name], $drawing['fill'], $drawing['opacity'])->create();
        }

        foreach (self::EQUIPMENT as $name => $pictogram) {
            ThumbnailMappingModelFactory::new()->forEquipment($values[Dictionary::Equipment->value][$name], $pictogram)->create();
        }

        return $values;
    }

    /**
     * @param  array<string, array<string, DictionaryValueModel>>  $dictionaries
     * @return list<VehicleModel>
     */
    private function vehicles(int $count, array $dictionaries): array
    {
        $models = [];

        foreach (self::GENERATIONS as $generationIndex => $generation) {
            foreach (self::MAKES as $make => $makeModels) {
                foreach ($makeModels as $model) {
                    $models[] = [$make, $model, $generation, $generationIndex];
                }
            }
        }

        $bodies = array_values($dictionaries[Dictionary::VehicleBody->value]);
        $vehicles = [];

        foreach (array_slice($models, 0, $count) as $index => [$make, $model, $generation, $generationIndex]) {
            $yearFrom = 1998 + $generationIndex * 3 + $index % 3;

            $vehicles[] = VehicleModelFactory::new()
                ->named("{$make} {$model} {$generation}")
                ->ofMake($dictionaries[Dictionary::VehicleMake->value][$make]->id)
                ->withBody($bodies[$index % count($bodies)]->id)
                ->state([
                    'model' => $model,
                    'generation' => $generation,
                    'year_from' => $yearFrom,
                    'year_to' => $yearFrom + 4 + $index % 3,
                ])
                ->create();
        }

        return $vehicles;
    }

    /**
     * Eurocodes are `{family}{variant}V`: four models per family share a prefix, so the search finds
     * several. The additional Eurocodes append a digit, so none can collide with another main one.
     *
     * @param  array<string, array<string, DictionaryValueModel>>  $dictionaries
     * @param  list<VehicleModel>  $vehicles
     */
    private function glassModels(int $count, array $dictionaries, array $vehicles): void
    {
        $positions = array_values($dictionaries[Dictionary::Position->value]);
        $colours = array_values($dictionaries[Dictionary::Colour->value]);
        $equipment = array_values($dictionaries[Dictionary::Equipment->value]);
        $vehicleCount = count($vehicles);

        for ($i = 0; $i < $count; $i++) {
            $main = sprintf('%04d', 1000 + intdiv($i, 4)).self::VARIANTS[$i % 4].'V';

            $factory = GlassModelModelFactory::new()
                ->withMainEurocode($main)
                ->withPosition($positions[$i % count($positions)])
                ->fitting(...$this->fitted($vehicles, $vehicleCount, $i));

            if ($i % 3 !== 0) {
                $factory = $factory->withColour($colours[$i % count($colours)]);
            }

            if ($i % 4 !== 1) {
                $factory = $factory->withEquipment(...array_map(
                    fn (int $k): DictionaryValueModel => $equipment[($i + $k) % count($equipment)],
                    range(0, $i % 3),
                ));
            }

            if ($i % 25 === 24) {
                $factory = $factory->withdrawn();
            }

            $glassModel = $factory->create();

            if ($i % 2 === 0) {
                GlassModelEurocodeModelFactory::new()->forGlassModel($glassModel)->withCode($main.'1')->create();
            }

            if ($i % 5 === 0) {
                GlassModelEurocodeModelFactory::new()->forGlassModel($glassModel)->withCode($main.'2')->create();
            }

            $oem = sprintf('%d%02d-%03d-%03d', 1 + $i % 9, $i % 100, intdiv($i, 7) % 1000, $i % 1000);
            GlassModelOemCodeModelFactory::new()->state(['glass_model_id' => $glassModel->id])->code($oem)->create();

            if ($i % 3 === 0) {
                GlassModelOemCodeModelFactory::new()->state(['glass_model_id' => $glassModel->id])->code('B'.$oem)->create();
            }
        }
    }

    /**
     * One to four distinct vehicles, neighbours of the model's family so related models share them.
     *
     * @param  list<VehicleModel>  $vehicles
     * @return list<VehicleModel>
     */
    private function fitted(array $vehicles, int $vehicleCount, int $i): array
    {
        $fitted = [];

        foreach (range(0, min($vehicleCount, 1 + $i % 4) - 1) as $k) {
            $fitted[] = $vehicles[(intdiv($i, 4) * 2 + $k) % $vehicleCount];
        }

        return $fitted;
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'dictionary_values' => DB::table('catalogue_dictionary_values')->count(),
            'vehicles' => DB::table('catalogue_vehicles')->count(),
            'glass_models' => DB::table('catalogue_glass_models')->count(),
            'eurocodes' => DB::table('catalogue_glass_model_eurocodes')->count(),
            'oem_codes' => DB::table('catalogue_glass_model_oem_codes')->count(),
            'fitments' => DB::table('catalogue_glass_model_vehicles')->count(),
            'thumbnail_mappings' => DB::table('catalogue_thumbnail_mappings')->count(),
        ];
    }
}
