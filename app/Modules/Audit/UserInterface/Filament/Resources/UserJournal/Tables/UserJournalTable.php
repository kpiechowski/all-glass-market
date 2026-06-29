<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Resources\UserJournal\Tables;

use App\Modules\Audit\Application\Commands\MarkJournalRead\MarkJournalReadCommand;
use App\Modules\Audit\Domain\Enums\JournalTypeEnum;
use App\Modules\Audit\Domain\Models\UserJournal;
use Ecotone\Modelling\CommandBus;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserJournalTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('audit::resource.user_journal.fields.user'))
                    ->sortable(),

                TextColumn::make('offer_id')
                    ->label(__('audit::resource.user_journal.fields.offer_id'))
                    ->placeholder('—'),

                TextColumn::make('type')
                    ->label(__('audit::resource.user_journal.fields.type'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('audit::resource.user_journal.fields.title'))
                    ->limit(60),

                IconColumn::make('is_read')
                    ->label(__('audit::resource.user_journal.fields.is_read'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('audit::resource.user_journal.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('audit::resource.user_journal.fields.type'))
                    ->options(JournalTypeEnum::class),

                TernaryFilter::make('is_read')
                    ->label(__('audit::resource.user_journal.filters.is_read')),
            ])
            ->recordActions([
                Action::make('mark_read')
                    ->label(__('audit::resource.user_journal.actions.mark_read'))
                    ->icon('heroicon-o-check')
                    ->visible(fn (UserJournal $record): bool => ! $record->is_read)
                    ->action(fn (UserJournal $record) => app(CommandBus::class)->send(
                        new MarkJournalReadCommand($record->id, Auth::id())
                    )),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
