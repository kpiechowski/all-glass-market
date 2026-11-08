# 02 - Change history: registration and recording

Status: resolved
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

### Worker — 2026-11-05
- **Built:** V1 scaffold removed (`ChangesLog`, `HasChangesLog`, `LoggableModel`, service, repository, relation manager, resource, translations, `changes_logs` migration, deptrac baseline lines; test-presence baseline was already empty). Registration (FR-07, D-33): `AuditableRecords` + `AuditableRecordType`, `TrackedRelation`, `ValuePresenter` in `Application/ModuleApi`. Recording (FR-01..FR-06, RULE-01/02/04, WF-01): `RecordChangeInHistory` listens to `RecordChanged` and sends `RecordChangeCommand` with the event's Actor; the handler filters excluded fields and untracked relations, drops net-empty Changed, keeps the last title on Deleted, and adds a `Change` entity through an add-only port (no edit/delete path). `ChangeModel` has no observer, so entries never produce `RecordChanged` (tested). No re-merging: Core already coalesces. A failing add rolls back the outer command (tested with a failing repository). `AuditPermission` (4 cases) declared via Users' `RoleRegistry`, `RecordHistory` granted to Admin. Display support: `ChangeQueries` (`forRecord`, `all`) and `ChangePresenter` (references by current name; soft-deleted shows name + removed; gone shows id + removed; unregistered field/relation/type shows technical name and raw values). Table `audit_changes`. Translations in Polish (`permissions`, `exceptions`, `presentation`). Architecture issue 06 set `resolved`.
- **Deviations:** (see fix 1 for 3 and 6) (1) Orphaned `changes_logs` table stays on an existing dev database (V2 never shipped; no drop migration). (2) `ChangePresenter` lives in `Application/Queries/PresentChange/` as a plain service, not a bus query: deptrac lets UserInterface reach Application only through Commands/Queries/ModuleApi, and it is a display read (ADR 0002). It takes the entry's stored arrays so no model enters Application. (3) The record id and timestamps appear as dirty fields on Created, so a module must list `id` etc. in `excludedFields` (documented in `AuditableRecords`); Audit does not guess the key. (4) Money presenter reads the raw value as a number of zloty ("1 200 zł"), the spec does not fix the unit; Offers can revisit. (5) `usunięto` title is read from the record at flush time (soft-deleted rows are found); a force-deleted record is already gone, so its title is null and display must fall back to the id. (6) Record type is stored as the model class string, not a morph alias. (7) No Permission check in the recording handler: any actor's write must be recorded. (8) The `Change` entity `record()` factory carries no "restored from" reference; ticket 06 adds it. (9) `ModelRepository` (Core, legacy) is now unused; left untouched.
- **Seams left:** `ChangeQueries`/`ChangePresenter` for tickets 04/05; `restorableFields`, `title`/`url` closures stored for 04/05/06; retention (07) adds its removal method to `ChangeRepository`; Users registers its record type and observer (03).
- **Open questions:** Should Audit exclude the primary key automatically instead of every module listing `id`? Money unit (zloty vs grosze) for Offers.
- **Check:** composer check green

### Worker — fix 1
- **Built:** (1) Audit always ignores `id`, `created_at`, `updated_at`, `deleted_at` (`AuditableRecordType::TECHNICAL_FIELDS`, applied in `isAudited`); `excludedFields` is for business fields only; documented in the `AuditableRecords` docblock. Tested: Created holds no id or timestamps, an update touching only `updated_at` leaves no entry. (2) Registration has a stable `key` (first argument, e.g. `things.thing`); `audit_changes.record_type`, the `Change` entity, `ChangeQueries::forRecord` and `ChangePresenter::present` work from the key; `AuditableRecords::findByKey()` resolves it, the listener still matches events by model class. Tested: stored type is the key, found by key (and not by class).
- **Deviations:** none (previous deviations 3 and 6 are resolved by this round).
- **Seams left:** tickets 04-06 read and filter by the type key.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 1 fix round
- **Notes:** Record types are stored by a stable registration `key` (e.g. `users.user`), never the model class; the listener matches events by model class. `id`, `created_at`, `updated_at`, `deleted_at` are always ignored (`AuditableRecordType::TECHNICAL_FIELDS`). Accepted: orphaned `changes_logs` table on dev DBs; `ChangePresenter` as a plain service in `Application/Queries/PresentChange`; money presenter reads zloty (Offers may revisit); a force-deleted record's `usunięto` has no title; restore-source reference added in 06. Seams: `ChangeQueries`/`ChangePresenter` (04, 05), `restorableFields` (06), retention removal on `ChangeRepository` (07), Users registration + observer (03).
