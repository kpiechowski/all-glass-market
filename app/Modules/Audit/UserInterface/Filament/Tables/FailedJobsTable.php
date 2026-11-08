<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Audit\Application\Commands\RemoveFailedJob\RemoveFailedJobCommand;
use App\Modules\Audit\Application\Commands\RetryFailedJob\RetryFailedJobCommand;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

/**
 * The "Nieudane zadania" tab of "Historia i logi" (template A, technical table, FR-18): failed
 * background jobs, newest first. Custom data: the page supplies `records()`. The row opens the
 * details; Ponów and Usuń sit in the row "⋮" and send commands. They never touch the Change history.
 */
class FailedJobsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->recordAction('details')
            ->recordActions([ActionGroup::make([self::retryAction(), self::removeAction()])])
            ->headerActions([])
            ->toolbarActions([])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedQueueList,
            heading: __('audit::pages.history_and_logs.failed_jobs.empty.heading'),
            description: __('audit::pages.history_and_logs.failed_jobs.empty.description'),
        );
    }

    /**
     * Technical table: no card, every column on every width, the long text last.
     *
     * @return list<TextColumn>
     */
    private static function columns(): array
    {
        return [
            TextColumn::make('time')
                ->label(__('audit::pages.history_and_logs.failed_jobs.fields.time'))
                ->dateTime('d.m.Y H:i')
                ->action(self::detailsAction()),
            TextColumn::make('name')
                ->label(__('audit::pages.history_and_logs.failed_jobs.fields.name')),
            TextColumn::make('error')
                ->label(__('audit::pages.history_and_logs.failed_jobs.fields.error'))
                ->limit(120),
        ];
    }

    /** Read-only details (E): no submit, "Zamknij". */
    private static function detailsAction(): Action
    {
        return Action::make('details')
            ->label(__('audit::pages.history_and_logs.details'))
            ->modalHeading(__('audit::pages.history_and_logs.failed_jobs.details_heading'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('audit::pages.history_and_logs.close'))
            ->modalWidth('5xl')
            ->modalContent(fn (?array $record): ?View => $record === null ? null : view('audit::failed-job-details', ['job' => $record]));
    }

    private static function retryAction(): Action
    {
        return Action::make('retry')
            ->label(__('audit::pages.history_and_logs.failed_jobs.retry'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn (): bool => CurrentActor::can(AuditPermission::TechnicalLogs))
            ->action(function (array $record): void {
                CommandSender::send(new RetryFailedJobCommand(CurrentActor::get(), $record['id']));

                Notification::make()->success()->title(__('audit::pages.history_and_logs.failed_jobs.retried'))->send();
            });
    }

    /** Dangerous: a red menu item; the confirmation names the act (E). */
    private static function removeAction(): Action
    {
        return Action::make('remove')
            ->label(__('audit::pages.history_and_logs.failed_jobs.remove'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('audit::pages.history_and_logs.failed_jobs.remove_heading'))
            ->modalDescription(__('audit::pages.history_and_logs.failed_jobs.remove_description'))
            ->modalSubmitActionLabel(__('audit::pages.history_and_logs.failed_jobs.remove_submit'))
            ->visible(fn (): bool => CurrentActor::can(AuditPermission::TechnicalLogs))
            ->action(function (array $record): void {
                CommandSender::send(new RemoveFailedJobCommand(CurrentActor::get(), $record['id']));

                Notification::make()->success()->title(__('audit::pages.history_and_logs.failed_jobs.removed'))->send();
            });
    }
}
