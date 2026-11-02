<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Actions;

use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

/**
 * Follows the entry's link. The button is named after the entry's kind, and a
 * subject that no longer exists ends in a warning instead (FR-31, FR-32).
 */
final class GoToSubjectAction
{
    public static function make(): Action
    {
        return Action::make('go_to_subject')
            ->label(fn (JournalEntryModel $record): string => app(JournalSubjects::class)->goToSubjectLabel($record->kind))
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->visible(fn (JournalEntryModel $record): bool => $record->subject_id !== null)
            ->action(function (JournalEntryModel $record, Component $livewire): void {
                $url = app(JournalSubjects::class)->urlFor($record->kind, (int) $record->subject_id);

                if ($url === null) {
                    Notification::make()
                        ->warning()
                        ->title(__('users::resources.journal_entry.subject_missing'))
                        ->send();

                    return;
                }

                $livewire->redirect($url);
            });
    }
}
