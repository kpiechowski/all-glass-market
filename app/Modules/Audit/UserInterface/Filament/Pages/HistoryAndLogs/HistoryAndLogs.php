<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Pages\HistoryAndLogs;

use App\Modules\Audit\Application\Queries\TechnicalLog\FailedJobEntry;
use App\Modules\Audit\Application\Queries\TechnicalLog\ListFailedJobs;
use App\Modules\Audit\Application\Queries\TechnicalLog\LogEntry;
use App\Modules\Audit\Application\Queries\TechnicalLog\LogFiles;
use App\Modules\Audit\Application\Queries\TechnicalLog\ReadLogEntries;
use App\Modules\Audit\Domain\Enums\LogLevel;
use App\Modules\Audit\Infrastructure\Persistence\Queries\ChangeQueries;
use App\Modules\Audit\UserInterface\Filament\Tables\AuditLogTable;
use App\Modules\Audit\UserInterface\Filament\Tables\FailedJobsTable;
use App\Modules\Audit\UserInterface\Filament\Tables\TechnicalLogTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * "Historia i logi" (D-24a): template A per page tab (A, page tabs on a standalone page).
 *
 * - Historia zmian (FR-12, `AuditLog`): every Change, filters, Przywróć.
 * - Logi techniczne (FR-16, `TechnicalLogs`): one channel's day, read-only, the file to download.
 * - Nieudane zadania (FR-18, `TechnicalLogs`): failed background jobs; Ponów and Usuń are commands.
 *
 * One table switched by the active tab. The tab bar links (`?zakladka=historia|logi|zadania`), so
 * every tab is a fresh page load and only its own table is ever built. The tab is locked against
 * client updates, and every server entry re-checks its tab's Permission.
 */
class HistoryAndLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $slug = 'history-and-logs';

    protected static ?int $navigationSort = 90;

    protected string $view = 'audit::history-and-logs';

    #[Locked]
    #[Url(as: 'zakladka')]
    public string $activeTab = '';

    public static function canAccess(): bool
    {
        return HistoryAndLogsTab::allowed() !== [];
    }

    public static function getNavigationLabel(): string
    {
        return __('audit::pages.history_and_logs.navigation_label');
    }

    public function getTitle(): string
    {
        return __('audit::pages.history_and_logs.title');
    }

    /** A missing or forbidden tab in the URL opens the first allowed one. */
    public function mount(): void
    {
        $allowed = HistoryAndLogsTab::allowed();
        abort_if($allowed === [], 403);

        $requested = HistoryAndLogsTab::tryFrom($this->activeTab);

        $this->activeTab = ($requested?->isAllowed() ? $requested : $allowed[0])->value;
    }

    public function tab(): HistoryAndLogsTab
    {
        return HistoryAndLogsTab::from($this->activeTab);
    }

    /** @return list<HistoryAndLogsTab> */
    public function getVisibleTabs(): array
    {
        return HistoryAndLogsTab::allowed();
    }

    public function getTabUrl(HistoryAndLogsTab $tab): string
    {
        return static::getUrl(['zakladka' => $tab->value]);
    }

    public function table(Table $table): Table
    {
        return match ($this->tab()) {
            HistoryAndLogsTab::History => AuditLogTable::configure($table)
                ->query(fn (): Builder => $this->historyQuery()),
            HistoryAndLogsTab::TechnicalLogs => TechnicalLogTable::configure($table)
                ->records(fn (?string $search, array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->logRecords(
                    $search,
                    LogLevel::tryFrom((string) ($filters['level']['value'] ?? '')),
                    $page,
                    (int) $recordsPerPage,
                )),
            HistoryAndLogsTab::FailedJobs => FailedJobsTable::configure($table)
                ->records(fn (int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->failedJobRecords($page, (int) $recordsPerPage)),
        };
    }

    /** @return list<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label(__('audit::pages.history_and_logs.technical_logs.download'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => $this->tab() === HistoryAndLogsTab::TechnicalLogs && $this->logFile() !== null)
                ->action(fn (): ?BinaryFileResponse => $this->download()),
        ];
    }

    /** Re-checked on every entry: Livewire state is client-controlled. */
    private function guardTab(HistoryAndLogsTab $tab): void
    {
        abort_unless($this->tab() === $tab && $tab->isAllowed(), 403);
    }

    private function historyQuery(): Builder
    {
        $this->guardTab(HistoryAndLogsTab::History);

        return app(ChangeQueries::class)->all();
    }

    private function download(): ?BinaryFileResponse
    {
        $this->guardTab(HistoryAndLogsTab::TechnicalLogs);

        $path = $this->logFile();

        return $path === null ? null : response()->download($path, basename($path));
    }

    /** The selected day's existing file, or null when the selection is invalid or has no file. */
    private function logFile(): ?string
    {
        return app(LogFiles::class)->existingFor(
            (string) ($this->tableFilters['channel']['channel'] ?? ''),
            (string) ($this->tableFilters['day']['day'] ?? ''),
        );
    }

    private function logRecords(?string $search, ?LogLevel $level, int $page, int $perPage): LengthAwarePaginator
    {
        $this->guardTab(HistoryAndLogsTab::TechnicalLogs);

        $path = $this->logFile();
        $result = $path === null
            ? null
            : app(ReadLogEntries::class)->page($path, $level, $search, $page, $perPage);

        return new LengthAwarePaginator(
            collect($result?->entries ?? [])->mapWithKeys(fn (LogEntry $entry): array => [
                $entry->key => [
                    'time' => $entry->time,
                    'level' => $entry->level,
                    'summary' => $entry->summary(),
                    'message' => $entry->message,
                    'context' => $entry->context,
                    'stack_trace' => $entry->stackTrace,
                ],
            ]),
            total: $result?->total ?? 0,
            perPage: $perPage,
            currentPage: $page,
        );
    }

    private function failedJobRecords(int $page, int $perPage): LengthAwarePaginator
    {
        $this->guardTab(HistoryAndLogsTab::FailedJobs);

        $entries = collect(app(ListFailedJobs::class)->all());

        return new LengthAwarePaginator(
            $entries->forPage($page, $perPage)->mapWithKeys(fn (FailedJobEntry $entry): array => [
                $entry->id => [
                    'id' => $entry->id,
                    'name' => $entry->name,
                    'job_class' => $entry->jobClass,
                    'queue' => $entry->queue,
                    'time' => $entry->time,
                    'error' => $entry->error,
                    'exception' => $entry->exception,
                ],
            ]),
            total: $entries->count(),
            perPage: $perPage,
            currentPage: $page,
        );
    }
}
