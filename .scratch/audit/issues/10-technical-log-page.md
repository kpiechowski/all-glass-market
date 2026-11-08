# 10 - "Logi techniczne" page

Status: resolved
Blocked by: 08

Spec: FR-16, WF-04, §3. Decisions: D-02, D-12, D-29, D-38.

## What

A panel page **Logi techniczne**, only with "logi techniczne" (Super Admin by default):
- **Opening:** shows the aplikacja channel, today. Channel and day pickers.
- **Table** of the day's entries, newest first, paged. Keep it lean: time, level, and the message shortened to one line.
- **Filters:** by level (debug, info, notice, warning, error, critical, alert, emergency). Search covers the message and the context.
- **Empty state:** a day with no file or no entries shows "brak wpisów".
- **Entry detail:** full message, context, and stack trace if present.
- **Download:** the day's file.
- **No deleting** from the panel.
- **Large files:** they must not exhaust memory. Read lazily or page through the file, and say how in Comments.

## Done when

- Tests cover:
  - access refused without the Permission;
  - the default channel and day;
  - level filter and search;
  - parsing a multi-line entry with a stack trace;
  - the empty day;
  - the download.
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** FR-16/WF-04 steps 1-4 (download; Nieudane zadania is FR-18, a later ticket). Page `Pages/TechnicalLog/TechnicalLog` (gate: `AuditPermission::TechnicalLogs`), opens on `aplikacja`, today; channel select (from `config('logging.labels')`, resolved with `__()`) and day picker. Custom-data table (`records()`, Filament v5): time, level badge, message cut to one line; level filter (`LogLevel` enum, 8 levels); search over message and context; empty state "Brak wpisów" (no file or no entries); details modal with full message, context, stack trace; header action "Pobierz plik". No delete anywhere. Read side in `Application/Queries/TechnicalLog/` (`LogFiles`, `ReadLogEntries`, `LogEntry`, `LogEntryPage`). Tests: `ReadLogEntriesTest` (parsing, paging, multi-chunk file, search, path safety, symlink) and `TechnicalLogTest` (access, defaults, order, level, search, details, empty day, download, tampered input).
- **Large files:** `ReadLogEntries` reads the file backwards in 64 KB chunks and yields one entry at a time (generator), so memory is one chunk plus one entry, never the file. One pass per page view applies level and search, counts all matches (for the pager) and keeps only the requested page's entries. Cost is a full pass per view (time grows with file size, memory does not). An entry's key is its byte offset, so the details modal finds it without keeping an index. Files are capped by 14-day retention.
- **Path safety:** the only way to a file is `LogFiles`: channel must be a key of `logging.labels` (directory comes from that channel's configured path), day must match `\d{4}-\d{2}-\d{2}` and pass `checkdate`, name is built as `<channel>-<day>.log`; an existing file must be a regular file whose real path sits in the channel directory (symlinks out are refused). Invalid input gives null, so the page shows the empty state and the download is hidden. Access is re-checked in `records()` and the download action, since Livewire state is client-controlled. A tampered day can also hit the DatePicker's own parse error (an exception, no file read).
- **Deviations:** the log line is split into message/context at the first ` {"`, and a `[stacktrace]` block inside the context is shown separately; a message containing ` {"` itself would be split early (display only). Download action is hidden when there is no file. `InvalidLogSelection` (Domain exception) is caught inside `LogFiles::existingFor`, so the UI never imports it. Filament's `assertCountTableRecords` cannot see a no-query table, so the test registers an `assertTableTotal` macro.
- **Seams left:** FR-18 "Nieudane zadania" tab joins this page (ticket 11/12); a new channel appears automatically once added to `logging.labels`.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 0 fix rounds
- **Notes:** Read side in `Application/Queries/TechnicalLog` (file I/O there, like `ChangePresenter`, because UI may reach Application only through Queries). `ReadLogEntries` reads backwards in 64 KB chunks and yields entries: memory bounded, time linear in file size per page view. `LogFiles` is the only path to a file: channel ∈ `logging.labels`, strict `Y-m-d` + `checkdate`, realpath must stay in the channel's directory. Permission re-checked in `records()` and the download. Message/context split on the first ` {"` (display only). Ticket 11 adds its tab to this page.
