# 07 - Retention cleanup

Status: ready-for-agent
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
