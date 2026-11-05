<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Infrastructure\Persistence\Queries\ChangeQueries;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class RecordHistoryTableFilters
{
    public const SYSTEM = 'system';

    /** @return list<SelectFilter> */
    public static function make(string $recordType, int $recordId): array
    {
        return [
            SelectFilter::make('actor')
                ->label(__('audit::resources.record_history.fields.actor'))
                ->options(fn (): array => self::actorOptions($recordType, $recordId))
                ->query(fn (Builder $query, array $data): Builder => match (true) {
                    blank($data['value'] ?? null) => $query,
                    $data['value'] === self::SYSTEM => $query->whereNull('actor_id'),
                    default => $query->where('actor_id', (int) $data['value']),
                }),
            SelectFilter::make('kind')
                ->label(__('audit::resources.record_history.fields.kind'))
                ->options(array_combine(
                    array_map(fn (ChangeKind $kind): string => $kind->value, ChangeKind::cases()),
                    array_map(fn (ChangeKind $kind): string => __('audit::enums.change_kind.'.$kind->value), ChangeKind::cases()),
                )),
        ];
    }

    /** @return array<string, string> */
    private static function actorOptions(string $recordType, int $recordId): array
    {
        $options = app(ChangeQueries::class)->actorsOf($recordType, $recordId);

        if (app(ChangeQueries::class)->hasSystemEntries($recordType, $recordId)) {
            $options[self::SYSTEM] = __('audit::resources.record_history.system_option');
        }

        return $options;
    }
}
