# 06 - Restore from a Change entry

Status: resolved
Blocked by: 04

Spec: FR-13, FR-14, RULE-05, WF-02, §3 (Restore). Decisions: D-11, D-16, D-36, D-38, D-41. Glossary: Restore.

## What

- On a registered type with restorable fields, a **Przywróć** action appears on a **zmieniono** entry in the record's history (and on the central page) when:
  - the entry holds at least one restorable field;
  - the record still exists.

  There is never a Restore on utworzono or usunięto entries, on relation-only entries, or on relations (D-38).
- **Confirmation (D-36):** a simple dialog lists the fields and values to be written, for example "Przywrócić: status → Aktywna, cena → 1200 zł?". There is no comparison with the current state.
- **Restore is literal (D-16):** it writes exactly the raw old values of the entry's restorable fields, and nothing else.
- **The owning module carries it out (FR-14).** Audit defines a contract (a `Domain/Contracts` port) that a module implements per restorable type. It receives the record id, the field values and the Actor, and dispatches the module's own command. Validation, events and syncs therefore run as for any edit.
  - If the module refuses, nothing changes and the reason is shown.
  - The resulting Change entry is marked "przywrócono z wpisu z {data}" and points to the source entry (D-38). Ticket 01/02 recording supplies the entry; add the marker through the command context.
- Guarded by the Permission "przywracanie zmian" (ticket 02), checked in the handler. It is granted to no Role, so only the Super Admin holds it by default; a Super Admin can grant it as an Extra Permission (D-41).
- Test with a test-only restorable type. No real module offers Restore yet: Users declares none.

## Done when

- Tests cover:
  - the action shown and hidden in every case above;
  - the confirmation lists the fields;
  - the literal write;
  - a refusal leaves the record unchanged and shows the reason;
  - the new marked entry;
  - a User without "przywracanie zmian" is refused in the handler, and an Admin holding it as an Extra Permission may Restore.
- `composer check` passes.

## Comments

### Triage — 2026-11-05
D-38 says Restore is a fixed Super Admin rule with no Permission of its own. The Core `Authorizer` only checks Permissions, so there are two ways to build it:
- **a)** A Permission "przywracanie zmian" that is granted to no Role: only Super Admin passes. Because Users FR-06 lets a Super Admin grant any registered Permission as an Extra Permission, an Admin could get it right away. That is the later topic D-16 mentions.
- **b)** A hard "is Super Admin" check, which needs a new Users `ModuleApi` read. It cannot be granted to anyone.

**Answer (user, 2026-11-05):** a) — recorded as D-41.

### Worker — 2026-11-05
- **Built:** FR-13/FR-14/RULE-05/WF-02. Core `CommandContext` contract (implemented by `ActorStack`): a value set around a callback is copied into every frame pushed inside it and stamped on that frame's `RecordChanged::$context` at flush. `RestoreChangeCommand` + handler: `audit.restore_changes` checked in the handler, entry loaded, type must `offersRestore()`, only a Changed entry with restorable fields, record must exist and not be soft-deleted, then the module's `Domain/Contracts/RestoresRecord` port runs inside `CommandContext::with('audit.restored_from', entryId)`. The module's nested command flushes its own frame, the listener passes the marker into `RecordChangeCommand`, and the handler writes it into the new entry (`restored_from_id`, `restored_from_at`) at creation, so entries stay immutable. The marker counts only for the same type and record as the source entry. `AuditableRecordType::restorer` + `offersRestore()` + `restorableValues()`. A module refusal propagates (DomainException), rolls back the transaction and is shown by `CommandSender`. UI: `RestoreChangeAction` ("Przywróć", confirmation lists "Przywrócić: Cena → 1 200 zł, ...?") on both the record history and the central page; the marker shows under the kind column and in the details. Tests: `RestoreChangeCommandHandlerTest`, `RestoreActionTest` (test-only Rzecz type with `ThingRestorer`).
- **Deviations:** (1) New migration adds `restored_from_id` (no FK, so retention can remove the source) and `restored_from_at` (denormalised so the marker date survives that). (2) The restorable field list is declared as before (`restorableFields`) and Restore is offered only when the type also supplies a `restorer`. (3) `ChangeRepository` gained `find()`. (4) Core gained `CommandContext` and `RecordChanged::$context` (needed because Application cannot depend on Core Infrastructure).
- **Seams left:** Users declares no restorer. Ticket 07 retention may delete source entries (marker keeps its date). Real modules implement `RestoresRecord` by sending their own update command with the given Actor.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 0 fix rounds
- **Notes:** Marker travels through Core's new `CommandContext` (implemented by `ActorStack`): frames opened inside `with()` copy the context and stamp it on `RecordChanged::$context` at flush; Audit stores `restored_from_id`/`restored_from_at` when the entry is created, so entries stay immutable. Restore is offered when a type has `restorableFields` and a `restorer` (`Domain/Contracts/RestoresRecord`, which dispatches the module's own command). Permission checked in the handler first. New migration on `audit_changes` (no FK to the source, so retention can remove it). No real module offers Restore yet.
