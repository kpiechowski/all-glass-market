# 12 - Review fixes: log file age and locked record type

Status: resolved
Blocked by: —

Spec: FR-21, WF-03 step 2, FR-09, §3 permissions. Decisions: D-12, D-30. Source: module spec review 2026-11-05 (two major findings).

## What

1. **Log files older than 14 days are really removed (FR-21, WF-03 step 2).** Monolog's daily driver `days` keeps the N newest files, not N days, and prunes only when that channel writes a new file (`RotatingFileHandler::rotate()`). A rarely written channel (`kolejka`, later `woocommerce`, even `bezpieczenstwo` with e-mails and IPs) keeps files for months. The nightly `audit:cleanup-retention` (ticket 07) must delete, for every channel in `config('logging.labels')`, the files `<channel>-YYYY-MM-DD.log` in that channel's configured directory whose date is older than 14 days. Match the exact file-name pattern only; never touch other files. Keep the existing config check. A failure is logged to `aplikacja` and the next night retries, as today. The deletion is outside the Change history.
2. **The record history tab cannot be switched to another record type (FR-09, §3).** `RecordHistoryRelationManager::$recordType` is a public Livewire property without `#[Locked]`, so a client could change it and read another type's history for the same id without "dziennik audytu". Lock it.

## Done when

- Tests: a 20-day-old and a 15-day-old file of a channel are deleted, a 13-day-old and today's file stay, a non-matching file in the directory stays; a rarely written channel is cleaned too.
- A test shows that setting `recordType` from the client is refused.
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** FR-21/WF-03 step 2: `audit:cleanup-retention` deletes `<channel>-YYYY-MM-DD.log` files (exact pattern, real date, strictly older than 14 days, symlinks skipped) in each `logging.labels` channel's configured directory; existing days check kept, failures logged to `aplikacja`. FR-09/§3: `RecordHistoryRelationManager::$recordType` is `#[Locked]`.
- **Deviations:** "older than 14 days" read as date < today-14 days (a 14-day-old file stays; 15 is deleted).
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 0 fix rounds
- **Notes:** `audit:cleanup-retention` now deletes `<channel>-YYYY-MM-DD.log` files with a real date older than 14 days in each labelled channel's directory (symlinks and non-matching files skipped), because Monolog's `days` keeps N newest files and prunes only on write. `RecordHistoryRelationManager::$recordType` is `#[Locked]`. Both found by the module spec review of 2026-11-05.
