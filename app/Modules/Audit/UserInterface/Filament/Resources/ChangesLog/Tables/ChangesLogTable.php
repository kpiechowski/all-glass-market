<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Resources\ChangesLog\Tables;

use App\Modules\Audit\Domain\Models\ChangesLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChangesLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loggable_type')
                    ->label(__('audit::resource.changes_log.fields.loggable_type'))
                    ->formatStateUsing(fn (ChangesLog $record): string => $record->getLoggableResourceName())
                    ->badge()
                    ->sortable(),

                TextColumn::make('loggable_title')
                    ->label(__('audit::resource.changes_log.fields.loggable_title'))
                    ->state(fn (ChangesLog $record): string => $record->getLoggableTitle())
                    ->url(fn (ChangesLog $record): ?string => $record->getLoggableUrl()),

                TextColumn::make('user.name')
                    ->label(__('audit::resource.changes_log.fields.user'))
                    ->sortable(),

                TextColumn::make('action')
                    ->label(__('audit::resource.changes_log.fields.action'))
                    ->state(fn (ChangesLog $record): string => $record->meta_data['action'] ?? '')
                    ->badge()
                    ->color(fn (ChangesLog $record): string => $record->matchColorToAction()),

                TextColumn::make('created_at')
                    ->label(__('audit::resource.changes_log.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label(__('audit::resource.changes_log.filters.action'))
                    ->options([
                        'created' => __('audit::resource.changes_log.actions.created'),
                        'updated' => __('audit::resource.changes_log.actions.updated'),
                        'deleted' => __('audit::resource.changes_log.actions.deleted'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->where('meta_data->action', $data['value'])
                        : $query),

                SelectFilter::make('loggable_type')
                    ->label(__('audit::resource.changes_log.filters.loggable_type'))
                    ->options(fn () => ChangesLog::query()
                        ->distinct()
                        ->pluck('loggable_type')
                        ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
                        ->toArray()),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
