# 01 - Core: recorded changes carry kind, relations and the Actor

Status: ready-for-agent
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
