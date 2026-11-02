<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Tables;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\DeleteJournalEntries\DeleteJournalEntriesCommand;
use App\Modules\Users\Application\Commands\MarkJournalEntryRead\MarkJournalEntryReadCommand;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Actions\GoToSubjectAction;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Support\JournalKindLabel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class JournalEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_read')
                    ->label(__('users::resources.journal_entry.fields.is_read'))
                    ->boolean(),

                TextColumn::make('severity')
                    ->label(__('users::resources.journal_entry.fields.severity'))
                    ->badge(),

                TextColumn::make('kind')
                    ->label(__('users::resources.journal_entry.fields.kind'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => JournalKindLabel::for($state)),

                TextColumn::make('title')
                    ->label(__('users::resources.journal_entry.fields.title'))
                    ->limit(80)
                    ->weight(fn (JournalEntryModel $record) => $record->is_read ? null : 'bold'),

                TextColumn::make('created_at')
                    ->label(__('users::resources.journal_entry.fields.created_at'))
                    ->dateTime(),
            ])
            ->filters(JournalEntriesTableFilters::make())
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    GoToSubjectAction::make(),
                    Action::make('mark_read')
                        ->label(__('users::resources.journal_entry.actions.mark_read'))
                        ->icon(Heroicon::OutlinedCheck)
                        ->visible(fn (JournalEntryModel $record): bool => ! $record->is_read)
                        ->action(fn (JournalEntryModel $record) => CommandSender::send(
                            new MarkJournalEntryReadCommand(CurrentActor::get(), $record->id)
                        )),
                    DeleteAction::make()
                        ->using(fn (JournalEntryModel $record) => CommandSender::send(
                            new DeleteJournalEntriesCommand(CurrentActor::get(), [$record->id])
                        )),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(fn (Collection $records) => CommandSender::send(
                            new DeleteJournalEntriesCommand(CurrentActor::get(), $records->pluck('id')->map(fn ($id): int => (int) $id)->all())
                        )),
                ]),
            ]);
    }
}
