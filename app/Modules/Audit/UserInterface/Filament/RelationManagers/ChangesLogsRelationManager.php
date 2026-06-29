<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\RelationManagers;

use App\Modules\Audit\Domain\Models\ChangesLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ChangesLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'changesLogs';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('audit::resource.changes_log.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action')
                    ->label(__('audit::resource.changes_log.fields.action'))
                    ->state(fn (ChangesLog $record): string => $record->meta_data['action'] ?? '')
                    ->badge()
                    ->color(fn (ChangesLog $record): string => $record->matchColorToAction()),

                TextColumn::make('message')
                    ->label(__('audit::resource.changes_log.fields.message'))
                    ->wrap(),

                TextColumn::make('user.name')
                    ->label(__('audit::resource.changes_log.fields.user'))
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(__('audit::resource.changes_log.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
