# 02 - Change history: registration and recording

Status: ready-for-agent
Blocked by: 01

Spec: FR-01..FR-08, RULE-01, RULE-02, RULE-04, RULE-07, WF-01, §2 (Auditable record type, Kind of change), §3 permissions, §8 Change entry. Decisions: D-03, D-06, D-41, D-07, D-10, D-12, D-14, D-17, D-18, D-33, D-35, D-38, D-39, D-40. Glossary: Change history, Change entry, Actor. Also closes `.scratch/architecture/issues/06-migrate-audit.md`.

## What

- **Clear the V1-port scaffold out of Audit.** Remove these, do not move them:
  - `ChangesLog` model, `HasChangesLog`, `LoggableModel`, `ChangesLogService`, `ChangesLogRepository`;
  - the old relation manager, `ChangesLogResource` with its pages and table, and the translations;
  - the `changes_logs` scaffold migration;
  - their deptrac-baseline and test-presence baseline lines.

  V2 never shipped, so an existing dev database keeps an orphaned table. Say so in Comments.
- **Registration (FR-07, D-33).** Add a `ModuleApi` class named by capability (for example `AuditableRecords`). Per record type, a module declares:
  - the record type it observes (its model class);
  - the type name (a translation key);
  - how to get a record's title and its page URL;
  - excluded fields (everything else is audited);
  - field labels;
  - a value presenter per field: a reference resolved to a readable name, an enum by label, money, date, list, or plain;
  - tracked relations, with their label and how an item is named;
  - which fields are restorable, used by ticket 06 (store the declaration now).

  Unregistered types are ignored. Document the API in its docblock, like Users' `JournalWriter`.
- **Recording (FR-01..FR-06, WF-01).** A listener on `RecordChanged` turns each change of a registered type into exactly one **Change entry**, through a command carrying the event's Actor. The entry holds:
  - record type and id;
  - kind;
  - raw old/new per audited field;
  - added/removed items per tracked relation;
  - the Actor (a User id, or System with its reason);
  - the time;
  - for **usunięto**, the record's last title (D-18).

  Rules:
  - A changed entry with no audited field or relation produces nothing.
  - **utworzono** holds the initial values. **usunięto** holds no field values.
  - Store raw values only. Names are resolved at display (D-35, D-40).
  - Entries are never updated or deleted outside retention (ticket 07). There is no command to edit or delete one.
- **Permissions:** an `AuditPermission` enum with "historia rekordu", "dziennik audytu", "logi techniczne" and "przywracanie zmian" (D-41; granted to no Role). Declare them through Users' `RoleRegistry` and grant "historia rekordu" to Admin. Super Admin holds everything.
- **Display support** for tickets 04 and 05: read queries plus a presenter that turns an entry into lines of label, old and new, from the current registration:
  - references resolved to names; a soft-deleted reference shows its name marked removed; a reference gone entirely shows its id marked removed (D-40);
  - a field no longer registered shows under its technical name with raw values.
- Persistence follows `module-persistence`: entity, port, adapter, mapper, read-only model, `{X}Queries`, factory, migration. Translations are in Polish.

## Done when

- A test-only registered record type shows these, each with a test:
  - created, changed, deleted and brought-back entries;
  - excluded fields dropped;
  - no entry for an unaudited change;
  - relation added/removed;
  - Actor User and System;
  - the last title on delete;
  - rollback of the whole command when recording fails.
- The presenter has tests covering a renamed, soft-deleted and gone reference, and an unregistered field.
- No Audit entries remain in the deptrac baseline. Architecture issue 06 is set `resolved`, with a pointer here.
- `composer check` passes.

## Comments
