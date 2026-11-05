<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Pages\TechnicalLog;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\Application\Queries\TechnicalLog\LogEntry;
use App\Modules\Audit\Application\Queries\TechnicalLog\LogFiles;
use App\Modules\Audit\Application\Queries\TechnicalLog\ReadLogEntries;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Audit\Domain\Enums\LogLevel;
use App\Modules\Audit\UserInterface\Filament\Pages\TechnicalLog\Schemas\LogSelectionForm;
use App\Modules\Audit\UserInterface\Filament\Tables\TechnicalLogTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The "Logi techniczne" page (FR-16, WF-04): holders of "logi techniczne" only.
 * Reads the daily files and offers one for download; nothing is written or deleted.
 */
class TechnicalLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $slug = 'technical-logs';

    protected static ?int $navigationSort = 91;

    protected string $view = 'audit::technical-log';

    /** @var array{channel?: string|null, day?: string|null} */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return CurrentActor::can(AuditPermission::TechnicalLogs);
    }

    public static function getNavigationLabel(): string
    {
        return __('audit::pages.technical_log.navigation_label');
    }

    public function getTitle(): string
    {
        return __('audit::pages.technical_log.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'channel' => app(LogFiles::class)->defaultChannel(),
            'day' => now()->format('Y-m-d'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return LogSelectionForm::configure($schema, fn () => $this->resetPage());
    }

    public function table(Table $table): Table
    {
        return TechnicalLogTable::configure($table)
            ->records(fn (?string $search, array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->records(
                $search,
                LogLevel::tryFrom((string) ($filters['level']['value'] ?? '')),
                $page,
                (int) $recordsPerPage,
            ));
    }

    /** @return list<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label(__('audit::pages.technical_log.download'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => $this->file() !== null)
                ->action(fn (): ?BinaryFileResponse => $this->download()),
        ];
    }

    /** Re-checked here: Livewire state is client-controlled. */
    private function download(): ?BinaryFileResponse
    {
        abort_unless(self::canAccess(), 403);

        $path = $this->file();

        return $path === null ? null : response()->download($path, basename($path));
    }

    /** The selected day's existing file, or null when the selection is invalid or has no file. */
    private function file(): ?string
    {
        return app(LogFiles::class)->existingFor((string) ($this->data['channel'] ?? ''), (string) ($this->data['day'] ?? ''));
    }

    private function records(?string $search, ?LogLevel $level, int $page, int $perPage): LengthAwarePaginator
    {
        abort_unless(self::canAccess(), 403);

        $path = $this->file();
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
}
