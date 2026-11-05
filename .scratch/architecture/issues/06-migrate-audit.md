# 06 - Rework the Audit module onto the architecture

Status: resolved
Blocked by: 02, 03, 04, `.scratch/audit/functional-spec.md` (Audit discovery not started)

Audit predates entities and `RecordChanged`. Rework it to the skills: the change log listens to `Core\Domain\Events\RecordChanged` instead of `HasChangesLog` resolving a service in `static::created()`; repositories get ports and adapters; models move to `Infrastructure/Persistence/Models`; translations split per D6.

Still open from the old skills and decided in this ticket: which fields are logged or skipped, how relation values are rendered, and how `LoggableModel` presentation fits read-only models.

Done when the Audit entries leave the deptrac baseline and `composer check` passes.

## Comments

Resolved by `.scratch/audit/issues/02-change-history-recording-and-registration.md`: the scaffold is removed and Audit is rebuilt on `RecordChanged`, ports and read-only models; no Audit lines remain in the deptrac baseline.
