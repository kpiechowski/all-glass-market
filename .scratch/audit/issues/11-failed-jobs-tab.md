# 11 - "Nieudane zadania" tab

Status: resolved
Blocked by: 10

Spec: FR-18, WF-04, §7 failed background job. Decisions: D-31, D-38.

## What

- A **Nieudane zadania** tab on the Logi techniczne page. It lists failed background jobs with a readable job name, the time and the error, and opens the full exception.
- **Ponów:** puts the job back on the queue, with no confirmation.
- **Usuń:** asks for confirmation, then forgets the job.
- Both actions are commands carrying the Actor, guarded by "logi techniczne". They never touch the Change history.
- A retried job that fails again shows up again with the new error.

## Done when

- Tests cover the listing, retry, remove with confirmation, and access refused without the Permission.
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** FR-18 / WF-04: a **Nieudane zadania** tab on `TechnicalLog` (`#[Url] $activeTab`, tab links to `?activeTab=failed`; the file form and download are hidden on it). Custom-data table `FailedJobsTable`: readable job name (class basename), time, one-line error; details modal with job class, queue and the full exception. Record actions in one `ActionGroup`: Ponów (no confirmation) and Usuń (confirmation). Both send `RetryFailedJobCommand` / `RemoveFailedJobCommand` (Actor + failed-job id); handlers `ensure` `AuditPermission::TechnicalLogs`, call the port, throw `FailedJobNotFound` when the job is gone. No RecordChanged, Change history untouched (tested). Port `Domain/Contracts/FailedJobs` (+ `ValueObjects/FailedJob`), adapter `Infrastructure/Queue/LaravelFailedJobs` over `FailedJobProviderInterface` (list/find/forget) and `queue:retry` (re-queues the payload the framework's way, then forgets the entry). Read side `Application/Queries/TechnicalLog/ListFailedJobs` + `FailedJobEntry`. A retried job that fails again is a new entry in the store, so it reappears with the new error (tested). Access re-checked in `records()`; tab is 403 without the Permission.
- **What the tab covers:** the queue is Laravel's own (`QUEUE_CONNECTION=database`, `jobs` table); Ecotone is used only as the synchronous command bus (no async channels configured). The failed-job store is `queue.failed` = `database-uuids` (`failed_jobs` table); the tab shows whatever Laravel's provider holds, i.e. jobs that exhausted their tries on any queue/connection. Failures of synchronous Ecotone commands never reach this store. The UI never touches `failed_jobs` directly.
- **Deviations:** the list is loaded whole from the provider and paginated in memory (provider has no paging API; failed-job volume expected small). The tab switch is a plain link (full page load) rather than a Livewire state change, because Filament caches the table definition per request. Handler tests live in `Retry/RemoveFailedJobCommandHandlerTest` (required by the presence test); UI tests in `FailedJobsTabTest`.
- **Seams left:** none (D-32 bell alerts remain a later option).
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 0 fix rounds
- **Notes:** Tab via `#[Url] activeTab` (full page load per switch). Ponów/Usuń are `RetryFailedJobCommand`/`RemoveFailedJobCommand`, Permission checked in the handlers, through the `Domain/Contracts/FailedJobs` port; adapter `LaravelFailedJobs` over `FailedJobProviderInterface` + `queue:retry`. Covers Laravel's database queue (`database-uuids` failed store); Ecotone runs the synchronous bus only. Paging is in memory (the provider has none) — `all()` loads every failed job with payload and exception; revisit if the failed store grows large.
