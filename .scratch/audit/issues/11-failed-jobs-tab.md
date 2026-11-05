# 11 - "Nieudane zadania" tab

Status: ready-for-agent
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
