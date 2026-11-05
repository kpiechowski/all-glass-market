<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\Application\Commands\RemoveFailedJob\RemoveFailedJobCommand;
use App\Modules\Audit\Application\Commands\RetryFailedJob\RetryFailedJobCommand;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The Nieudane zadania tab (FR-18): failed background jobs, newest first. Custom
 * data: the page supplies `records()`. Ponów and Usuń send commands; they never
 * touch the Change history.
 */
class FailedJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->recordActions([ActionGroup::make([self::detailsAction(), self::retryAction(), self::removeAction()])])
            ->recordAction('details')
            ->headerActions([])
            ->toolbarActions([])
            ->emptyStateHeading(__('audit::pages.technical_log.failed_jobs.empty'))
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    /** @return list<TextColumn> */
    private static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('audit::pages.technical_log.failed_jobs.fields.name')),
            TextColumn::make('time')
                ->label(__('audit::pages.technical_log.fields.time')),
            TextColumn::make('error')
                ->label(__('audit::pages.technical_log.failed_jobs.fields.error'))
                ->limit(120),
        ];
    }

    private static function detailsAction(): Action
    {
        return Action::make('details')
            ->label(__('audit::pages.technical_log.details'))
            ->icon('heroicon-o-eye')
            ->modalHeading(__('audit::pages.technical_log.failed_jobs.details_heading'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('audit::pages.technical_log.close'))
            ->modalWidth('5xl')
            ->modalContent(fn (?array $record) => $record === null ? null : view('audit::failed-job-details', ['job' => $record]));
    }

    private static function retryAction(): Action
    {
        return Action::make('retry')
            ->label(__('audit::pages.technical_log.failed_jobs.retry'))
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (): bool => CurrentActor::can(AuditPermission::TechnicalLogs))
            ->action(function (array $record): void {
                CommandSender::send(new RetryFailedJobCommand(CurrentActor::get(), $record['id']));

                Notification::make()->success()->title(__('audit::pages.technical_log.failed_jobs.retried'))->send();
            });
    }

    private static function removeAction(): Action
    {
        return Action::make('remove')
            ->label(__('audit::pages.technical_log.failed_jobs.remove'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('audit::pages.technical_log.failed_jobs.remove_heading'))
            ->modalDescription(__('audit::pages.technical_log.failed_jobs.remove_description'))
            ->visible(fn (): bool => CurrentActor::can(AuditPermission::TechnicalLogs))
            ->action(function (array $record): void {
                CommandSender::send(new RemoveFailedJobCommand(CurrentActor::get(), $record['id']));

                Notification::make()->success()->title(__('audit::pages.technical_log.failed_jobs.removed'))->send();
            });
    }
}
