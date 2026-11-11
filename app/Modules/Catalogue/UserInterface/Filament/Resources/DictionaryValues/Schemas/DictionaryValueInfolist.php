<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Schemas;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Support\DictionaryValueViewSections;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Template C, layout (a): one sheet section, then the sections other modules register (FR-25).
 */
class DictionaryValueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::valueSection(),
            self::extensionSections(),
        ]);
    }

    private static function valueSection(): Section
    {
        return Section::make(__('catalogue::resources.dictionary_value.sections.value'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('name')
                    ->label(__('catalogue::resources.dictionary_value.fields.name')),
                TextEntry::make('dictionary')
                    ->label(__('catalogue::resources.dictionary_value.fields.dictionary'))
                    ->badge()
                    ->color('gray'),
                TextEntry::make('withdrawn')
                    ->label(__('catalogue::resources.dictionary_value.fields.state'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => self::stateLabel($state))
                    ->color(fn (bool $state): string => $state ? 'gray' : 'success'),
            ]);
    }

    /** FR-25: renders nothing until a module registers a section. */
    private static function extensionSections(): Group
    {
        return Group::make()
            ->schema(fn (DictionaryValueModel $record): array => app(DictionaryValueViewSections::class)->for($record));
    }

    public static function stateLabel(bool $withdrawn): string
    {
        return __('catalogue::resources.dictionary_value.state.'.($withdrawn ? 'withdrawn' : 'active'));
    }
}
