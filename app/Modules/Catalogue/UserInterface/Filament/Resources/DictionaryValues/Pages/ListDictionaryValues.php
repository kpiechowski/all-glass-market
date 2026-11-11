<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\DictionaryValueQueries;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\CreateDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\DictionaryValueResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/** Template A (list): one primary action, one list tab per dictionary. */
class ListDictionaryValues extends ListRecords
{
    protected static string $resource = DictionaryValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateDictionaryValueAction::make(),
        ];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $tabs = [];

        foreach (Dictionary::cases() as $dictionary) {
            $tabs[$dictionary->value] = Tab::make($dictionary->pluralLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => app(DictionaryValueQueries::class)->ofDictionary($dictionary, $query));
        }

        return $tabs;
    }
}
