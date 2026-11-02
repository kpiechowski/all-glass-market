<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Tables;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Queries\JournalQueries;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Support\JournalKindLabel;
use Filament\Tables\Filters\SelectFilter;

class JournalEntriesTableFilters
{
    /** @return array<int, SelectFilter> */
    public static function make(): array
    {
        return [
            SelectFilter::make('kind')
                ->label(__('users::resources.journal_entry.fields.kind'))
                ->options(fn (): array => collect(app(JournalQueries::class)->kindsOf(CurrentActor::get()->id))
                    ->mapWithKeys(fn (string $kind): array => [$kind => JournalKindLabel::for($kind)])
                    ->all()),

            SelectFilter::make('severity')
                ->label(__('users::resources.journal_entry.fields.severity'))
                ->options(JournalSeverity::class),
        ];
    }
}
