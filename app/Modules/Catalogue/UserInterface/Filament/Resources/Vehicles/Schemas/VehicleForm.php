<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Schemas;

use App\Modules\Catalogue\Domain\Policies\VehicleNameDomainPolicy;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\VehicleQueries;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

/**
 * Template B, layout (a): one column of sections, no side column (the record has no status).
 * On create the name field is filled from the other fields as long as the admin has not typed
 * their own name (FR-08); on edit nothing touches it. Pickers offer no withdrawn make or body,
 * except the one the vehicle already has (FR-24).
 */
class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::detailsSection(),
            self::nameSection(),
        ]);
    }

    private static function detailsSection(): Section
    {
        return Section::make(__('catalogue::resources.vehicle.sections.details'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                self::makeField(),
                self::modelField(),
                self::bodyField(),
                self::generationField(),
                self::yearFromField(),
                self::yearToField(),
                self::argicCodeField(),
            ]);
    }

    private static function makeField(): Select
    {
        return Select::make('make_id')
            ->live(onBlur: true)
            ->afterStateUpdated(self::refreshProposal(...))
            ->label(__('catalogue::resources.vehicle.fields.make'))
            ->options(fn (?VehicleModel $record): array => app(VehicleQueries::class)->offeredMakes($record?->make_id)->pluck('name', 'id')->all())
            ->required()
            ->searchable()
            ->columnSpan(['default' => 'full', 'md' => 6])
            ->columnStart(['default' => 1]);
    }

    private static function modelField(): TextInput
    {
        return TextInput::make('model')
            ->live(onBlur: true)
            ->afterStateUpdated(self::refreshProposal(...))
            ->label(__('catalogue::resources.vehicle.fields.model'))
            ->maxLength(255)
            ->columnSpan(['default' => 'full', 'md' => 6])
            ->columnStart(['default' => 1]);
    }

    private static function bodyField(): Select
    {
        return Select::make('body_id')
            ->live(onBlur: true)
            ->afterStateUpdated(self::refreshProposal(...))
            ->label(__('catalogue::resources.vehicle.fields.body'))
            ->options(fn (?VehicleModel $record): array => app(VehicleQueries::class)->offeredBodies($record?->body_id)->pluck('name', 'id')->all())
            ->searchable()
            ->columnSpan(['default' => 'full', 'md' => 6])
            ->columnStart(['default' => 1]);
    }

    private static function generationField(): TextInput
    {
        return TextInput::make('generation')
            ->live(onBlur: true)
            ->afterStateUpdated(self::refreshProposal(...))
            ->label(__('catalogue::resources.vehicle.fields.generation'))
            ->maxLength(255)
            ->columnSpan(['default' => 'full', 'md' => 6])
            ->columnStart(['default' => 1]);
    }

    /** Years from and to are a pair that belongs together. */
    private static function yearFromField(): TextInput
    {
        return TextInput::make('year_from')
            ->live(onBlur: true)
            ->afterStateUpdated(self::refreshProposal(...))
            ->label(__('catalogue::resources.vehicle.fields.year_from'))
            ->numeric()
            ->minValue(1000)
            ->maxValue(9999)
            ->columnSpan(['default' => 'full', 'md' => 3])
            ->columnStart(['default' => 1]);
    }

    private static function yearToField(): TextInput
    {
        return TextInput::make('year_to')
            ->live(onBlur: true)
            ->afterStateUpdated(self::refreshProposal(...))
            ->label(__('catalogue::resources.vehicle.fields.year_to'))
            ->helperText(__('catalogue::resources.vehicle.helpers.year_to'))
            ->numeric()
            ->minValue(1000)
            ->maxValue(9999)
            ->columnSpan(['default' => 'full', 'md' => 3]);
    }

    private static function argicCodeField(): TextInput
    {
        return TextInput::make('argic_code')
            ->live(onBlur: true)
            ->afterStateUpdated(self::refreshProposal(...))
            ->label(__('catalogue::resources.vehicle.fields.argic_code'))
            ->placeholder('0000')
            ->regex('/^\d{4}$/')
            ->maxLength(4)
            ->columnSpan(['default' => 'full', 'md' => 3])
            ->columnStart(['default' => 1]);
    }

    /** The name section's only visible field is named like the section, so its label is hidden. */
    private static function nameSection(): Section
    {
        return Section::make(__('catalogue::resources.vehicle.sections.name'))
            ->description(__('catalogue::resources.vehicle.descriptions.name'))
            ->schema([
                TextInput::make('name')
                    ->label(__('catalogue::resources.vehicle.sections.name'))
                    ->hiddenLabel()
                    ->required()
                    ->maxLength(255),
                Hidden::make('proposed_name')->dehydrated(false),
            ]);
    }

    /** FR-08: only for a new vehicle, and only while the name is empty or still the last proposal. */
    private static function refreshProposal(Get $get, Set $set, string $operation): void
    {
        if ($operation !== 'create') {
            return;
        }

        $proposal = VehicleNameDomainPolicy::propose(
            self::valueName($get('make_id')),
            $get('model'),
            self::valueName($get('body_id')),
            $get('generation'),
            self::year($get('year_from')),
            self::year($get('year_to')),
            $get('argic_code'),
        );

        $name = trim((string) $get('name'));

        if ($name === '' || $name === (string) $get('proposed_name')) {
            $set('name', $proposal);
        }

        $set('proposed_name', $proposal);
    }

    private static function valueName(mixed $id): ?string
    {
        return filled($id) ? DictionaryValueModel::query()->find($id)?->name : null;
    }

    private static function year(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }
}
