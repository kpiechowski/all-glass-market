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
use Illuminate\Support\Carbon;

class AuditLogTableFilters
{
    /**
     * Five filters, so the funnel panel, the frequent ones first (A, Tabs and filters).
     *
     * @return list<Filter|SelectFilter>
     */
    public static function make(): array
    {
        return [
            SelectFilter::make('record_type')
                ->label(__('audit::pages.history_and_logs.history.fields.record_type'))
                ->options(fn (): array => self::typeOptions()),
            SelectFilter::make('kind')
                ->label(__('audit::resources.record_history.fields.kind'))
                ->options(array_combine(
                    array_map(fn (ChangeKind $kind): string => $kind->value, ChangeKind::cases()),
                    array_map(fn (ChangeKind $kind): string => __('audit::enums.change_kind.'.$kind->value), ChangeKind::cases()),
                )),
            SelectFilter::make('actor')
                ->label(__('audit::resources.record_history.fields.actor'))
                ->options(fn (): array => self::actorOptions())
                ->query(fn (Builder $query, array $data): Builder => match (true) {
                    blank($data['value'] ?? null) => $query,
                    $data['value'] === RecordHistoryTableFilters::SYSTEM => $query->whereNull('actor_id'),
                    default => $query->where('actor_id', (int) $data['value']),
                }),
            self::periodFilter(),
            self::recordFilter(),
        ];
    }

    private static function periodFilter(): Filter
    {
        return Filter::make('period')
            ->label(__('audit::pages.history_and_logs.history.fields.period'))
            ->schema([
                DatePicker::make('from')->label(__('audit::pages.history_and_logs.history.fields.from')),
                DatePicker::make('until')->label(__('audit::pages.history_and_logs.history.fields.until')),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when(filled($data['from'] ?? null), fn (Builder $q) => $q->where('occurred_at', '>=', $data['from'].' 00:00:00'))
                ->when(filled($data['until'] ?? null), fn (Builder $q) => $q->where('occurred_at', '<=', $data['until'].' 23:59:59')))
            ->indicateUsing(fn (array $data): array => array_values(array_filter([
                filled($data['from'] ?? null) ? __('audit::pages.history_and_logs.history.indicators.from', ['date' => Carbon::parse($data['from'])->format('d.m.Y')]) : null,
                filled($data['until'] ?? null) ? __('audit::pages.history_and_logs.history.indicators.until', ['date' => Carbon::parse($data['until'])->format('d.m.Y')]) : null,
            ])));
    }

    private static function recordFilter(): Filter
    {
        return Filter::make('record')
            ->label(__('audit::pages.history_and_logs.history.fields.record'))
            ->schema([
                TextInput::make('record_id')
                    ->label(__('audit::pages.history_and_logs.history.fields.record_id'))
                    ->numeric(),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when(filled($data['record_id'] ?? null), fn (Builder $q) => $q->where('record_id', (int) $data['record_id'])))
            ->indicateUsing(fn (array $data): array => filled($data['record_id'] ?? null)
                ? [__('audit::pages.history_and_logs.history.indicators.record_id', ['id' => (int) $data['record_id']])]
                : []);
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
