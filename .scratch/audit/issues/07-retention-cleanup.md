# 07 - Retention cleanup

Status: resolved
Blocked by: 02

Spec: FR-20, FR-21, WF-03, §7. Decisions: D-01, D-13, D-30, D-38.

## What

- A nightly scheduled task, run under a System Actor with a named reason, that:
  - removes Change entries older than the retention period, counted from each entry's time;
  - checks that daily log files older than 14 days are gone. Laravel's daily driver does this through its `days` option, so this part only verifies it.
- **Retention period:** one value, 365 days by default. The spec puts it in Settings, but there is no Settings module yet. Read it from Audit's config for now, behind a small seam that Settings can take over, and note it in Comments.
- A failed run is logged as an error in the aplikacja channel and runs again the next night.
- Removal is the only way entries go. There is no other delete path.

## Done when

- Tests show that entries older than the period are removed and newer ones kept, the period is configurable, and a failure is logged.
- The task is in the schedule. Production cron already runs the scheduler every minute (G-04).
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** FR-20/WF-03 step 1: `CleanUpExpiredChangesCommand` + handler (System actor only, reason key `audit::actors.retention_cleanup`; a User actor gets AuthorizationException) calling `ChangeRepository::removeOlderThan()`, the only delete path. Retention period behind `Domain\Contracts\RetentionPeriod`, adapter `Infrastructure\Settings\ConfigRetentionPeriod` reading `audit.retention_days` (env `AUDIT_RETENTION_DAYS`, default 365). Console `audit:cleanup-retention` scheduled daily at 03:00 from the module provider; it also verifies the daily log driver's `days` is 1..14 (FR-21).
- **Deviations:** the schedule is registered in `AuditModuleServiceProvider` (callAfterResolving Schedule), not routes/console.php, to keep it in the module. Cutoff is `now - N days` (entries strictly older are removed). Failure (including a bad `days` config) logs `error` and exits non-zero; next night's run retries. Added `removeOlderThan` to the test fixture `FailingChangeRepository`.
- **Seams left:** Settings module rebinds `RetentionPeriod`. Ticket 08: `logFailure()` in the console command uses `Log::channel('aplikacja')` once that channel is configured, otherwise the default channel.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 0 fix rounds
- **Notes:** `audit:cleanup-retention` (03:00, scheduled from `AuditModuleServiceProvider`) sends `CleanUpExpiredChangesCommand` under `Actor::system('audit::actors.retention_cleanup')`; only a System Actor may send it. `ChangeRepository::removeOlderThan()` is the only delete path. Retention seam: `Domain/Contracts/RetentionPeriod` ← `ConfigRetentionPeriod` (`audit.retention_days`, 365, `AUDIT_RETENTION_DAYS`) until Settings exists. Seams for 08: failure logs to `aplikacja` once it exists; the log-retention check reads only `logging.channels.daily.days` — 08 must extend it to every daily channel it defines.
