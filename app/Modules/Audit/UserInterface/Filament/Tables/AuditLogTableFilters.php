<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Infrastructure\Persistence\Queries\ChangeQueries;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class AuditLogTableFilters
{
    /** @return list<Filter|SelectFilter> */
    public static function make(): array
    {
        return [
            SelectFilter::make('record_type')
                ->label(__('audit::resources.audit_log.fields.record_type'))
                ->options(fn (): array => self::typeOptions()),
            Filter::make('record')
                ->label(__('audit::resources.audit_log.fields.record'))
                ->schema([
                    TextInput::make('record_id')
                        ->label(__('audit::resources.audit_log.fields.record_id'))
                        ->numeric(),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when(filled($data['record_id'] ?? null), fn (Builder $q) => $q->where('record_id', (int) $data['record_id']))),
            SelectFilter::make('actor')
                ->label(__('audit::resources.record_history.fields.actor'))
                ->options(fn (): array => self::actorOptions())
                ->query(fn (Builder $query, array $data): Builder => match (true) {
                    blank($data['value'] ?? null) => $query,
                    $data['value'] === RecordHistoryTableFilters::SYSTEM => $query->whereNull('actor_id'),
                    default => $query->where('actor_id', (int) $data['value']),
                }),
            SelectFilter::make('kind')
                ->label(__('audit::resources.record_history.fields.kind'))
                ->options(array_combine(
                    array_map(fn (ChangeKind $kind): string => $kind->value, ChangeKind::cases()),
                    array_map(fn (ChangeKind $kind): string => __('audit::enums.change_kind.'.$kind->value), ChangeKind::cases()),
                )),
            Filter::make('period')
                ->schema([
                    DatePicker::make('from')->label(__('audit::resources.audit_log.fields.from')),
                    DatePicker::make('until')->label(__('audit::resources.audit_log.fields.until')),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when(filled($data['from'] ?? null), fn (Builder $q) => $q->where('occurred_at', '>=', $data['from'].' 00:00:00'))
                    ->when(filled($data['until'] ?? null), fn (Builder $q) => $q->where('occurred_at', '<=', $data['until'].' 23:59:59'))),
        ];
    }

    /** @return array<string, string> */
    private static function typeOptions(): array
    {
        $options = [];

        foreach (app(AuditableRecords::class)->all() as $type) {
            $options[$type->key] = $type->typeName();
        }

        return $options;
    }

    /** @return array<string, string> */
    private static function actorOptions(): array
    {
        $options = app(ChangeQueries::class)->allActors();

        if (app(ChangeQueries::class)->anySystemEntries()) {
            $options[RecordHistoryTableFilters::SYSTEM] = __('audit::resources.record_history.system_option');
        }

        return $options;
    }
}
