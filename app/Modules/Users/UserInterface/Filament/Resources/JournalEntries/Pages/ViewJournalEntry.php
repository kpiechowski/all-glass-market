<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\DeleteJournalEntries\DeleteJournalEntriesCommand;
use App\Modules\Users\Application\Commands\MarkJournalEntryRead\MarkJournalEntryReadCommand;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Actions\GoToSubjectAction;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\JournalEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;

    /** Opening the details marks the entry read (FR-31). */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var JournalEntryModel $entry */
        $entry = $this->getRecord();

        if (! $entry->is_read) {
            CommandSender::send(new MarkJournalEntryReadCommand(CurrentActor::get(), $entry->id));

            $entry->refresh();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            GoToSubjectAction::make(),
            DeleteAction::make()
                ->using(fn (JournalEntryModel $record) => CommandSender::send(
                    new DeleteJournalEntriesCommand(CurrentActor::get(), [$record->id])
                ))
                ->successRedirectUrl(fn (): string => JournalEntryResource::getUrl('index')),
        ];
    }
}
