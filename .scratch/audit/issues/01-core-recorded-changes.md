# 01 - Core: recorded changes carry kind, relations and the Actor

Status: resolved
Blocked by: —

Spec: FR-01, FR-03, FR-04, RULE-02, RULE-03, WF-01 (Core part). Decisions: D-07, D-08, D-14, D-39, D-42. Glossary: Actor, Change entry. Design reference: `docs/adr/0004`.

## What

Today `Core\Domain\Events\RecordChanged` holds only model class, id and field changes. Audit cannot build a Change entry from it. Extend the Core seam so every module's writes arrive complete:

- **Kind of change:** created, changed, deleted, brought back (restored from soft delete).
- **Field changes:** old and new raw values, as today.
- **Relation changes:** per tracked relation, the items added and removed (raw ids). Observers cannot see pivot syncs, so the Core repository base gives adapters one way to sync a relation that also reports the diff. Adapters must use it for relations a module wants audited.
- **The Actor** of the command being handled:
  - The command bus holds the current command's `Actor` for the duration of the command. Commands carry it as `public Actor $actor`. In a nested command the inner Actor applies, and the outer one comes back after it.
  - Nothing in V2 writes a model outside a command (read-only guard). A recorded change with no current command is a programming error and fails loudly. Do not invent a fallback System actor.
- **Impersonation (D-42):** record the Actor exactly as the command carries it. Nothing special for impersonation.
- **Same transaction (D-39):** recording happens synchronously inside the command's transaction. If it fails, the command rolls back. Nothing in this ticket may defer it after commit.
- Update the observer stubs (`app/Core/Support/Stubs/observer.stub`, `test-observer.stub`) and the `module-events` skill's "Observers and audit" section to the new payload and the relation-sync rule.

## Done when

- `RecordChanged` carries kind, field changes, relation changes and the Actor, and is tested for each kind.
- A test shows a nested command records the inner Actor, and a write outside a command fails.
- The relation-sync helper reports added and removed items, and has a test.
- Both stubs and the `module-events` skill are updated.
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** `RecordChanged` now carries `ChangeKind` (Created/Changed/Deleted/Restored), field changes, relation changes (`added`/`removed` raw ids per relation) and the `Actor` (FR-01, FR-03, FR-04, RULE-02, RULE-03). `ActorStack` + `HoldCommandActor` bus interceptor hold the command's Actor (nested inner applies, outer restored); `NoCurrentActorException` when recording outside a command (no fallback). `ChangeRecorder` builds/emits the event synchronously in the command transaction (D-39; nothing after-commit). `EloquentRepository::syncRelation()` syncs a pivot relation and reports the diff. Observer stubs, test-observer and test-repository stubs and the `module-events` skill updated. Tests: unit (RecordChanged, ActorStack), feature (HoldCommandActor incl. nested, ChangeRecorder for each kind, outside-command failure, listener failure rollback, relation sync).
- **Deviations:** (1) `ReadOnlyModel::forceDelete()` lost its `?bool` return type: the `SoftDeletes` trait declares it untyped and any soft-deleting model was a fatal error (needed for "brought back"). (2) `ModelFactory::create()` records as `Actor::system('factory')` when no Actor is current; factories are test/seed tooling and would otherwise fail on every audited model. Repository/model writes outside a command still fail. (3) A restore save is emitted once as Restored (the accompanying `updated` is suppressed, so field changes in that same save are not carried). (4) `syncRelation()` emits its own `RecordChanged` (Changed, relation changes only); a command that changes fields and relations of one record yields two events, Audit (issue 02) must merge them per command/record to keep "exactly one entry".
- **Seams left:** Audit listener (issue 02) consumes `RecordChanged`; Users observer wiring (issue 03). `HoldCommandActor` relies on Ecotone auto-discovery as `WrapCommandInTransaction` does.
- **Open questions:** Is the two-events-per-record case in deviation 4 acceptable for Audit to merge, or should `syncRelation` stash its diff for the observer instead?
- **Check:** composer check green

### Worker — fix 1
- **Built:** One `RecordChanged` per record per command. `ChangeRecorder` now buffers into the current `ActorStack` frame; on successful frame close `RecordChangeCoalescer` folds Changed into an earlier Created/Changed of the same record (first old, last new, reverted field dropped, relation diffs united with add/remove cancelling, net-empty Changed dropped; Deleted/Restored never folded) and dispatches with the frame's Actor; a throwing command discards its buffer; nested frames flush with the inner Actor; `ModelFactory` frames flush the same way. `WrapCommandInTransaction` (precedence 10) is the outer interceptor and `HoldCommandActor` (20) inner, so the flush happens before commit; verified that swapping them fails the rollback test. Tests: coalescer unit tests, ChangeRecorder (fields+sync one event, update+delete two events, throw dispatches nothing), and a through-the-bus test (Created with relations in one event, nested inner Actor, throw, throwing listener rolls back). ChangeRecorder docblock fixed; skill and observer stub reworded.
- **Deviations:** none (deviation 4 resolved).
- **Seams left:** Audit (02) listens to one event per record per command.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 1 fix round
- **Notes:** Core delivers one `RecordChanged` per record per command: `ChangeRecorder` buffers into the `ActorStack` frame, `RecordChangeCoalescer` folds Changed into an earlier Created/Changed (Deleted/Restored never folded) and the frame flushes on success, before commit (`WrapCommandInTransaction` precedence 10 outer, `HoldCommandActor` 20). Accepted deviations: untyped `ReadOnlyModel::forceDelete()`; `ModelFactory` records as `Actor::system('factory')` outside a command; a restore save yields only Restored. Seams: Audit listener on `RecordChanged` (02), Users observer + `syncRelation` (03). Commands must carry `public Actor $actor`, or writes inside them throw `NoCurrentActorException`.
