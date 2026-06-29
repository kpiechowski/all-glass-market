# Audit — V2 Plan

**Status:** ✅ Done
**Scan:** `.ai/modules/audit/scan.md`
**Phase:** 1a

---

## Health decisions

**Concern 1 — JournalAction hierarchy:**
Replace entirely. Do NOT port the abstract JournalAction classes. In V2:

- Offer domain events fire (Phase 2b: `OfferCreated`, `OfferArchived`, etc.)
- Two separate Listeners handle them in this module:
    - `OfferJournalListener` → creates `UserJournal` entry for the offer owner
    - `OfferPanelNotificationListener` → sends Filament in-panel notification to the offer owner
- In Phase 1a these listeners are scaffolded as stubs (registered but noop) — filled in Phase 2b when Offer events exist.

**Concern 2 — UserNotification skeleton model:**
Drop the `UserNotification` model and its table. The feature it was meant for — tracking business-relevant User model changes (e.g. `company_account` toggle that affects offer pricing) — is handled by applying `HasChangesLog` to the `User` model. This writes to the `changes_logs` table with `loggable_type = User`. The Audit Filament UI can filter the changes log by user records.

**Schema migration note:**
Schemas do not need to be 1:1 from V1. They must be _migratable_ — V1 data must be mappable to the new format, even if column names or types change. For Audit, `changes_logs` and `user_journals` schemas are straightforward V1→V2 ports with no structural changes needed.

---

## Domain objects

### Models

```
ChangesLog
  Table: changes_logs
  Key fields: user_id (nullable FK), loggable_type, loggable_id, changes (json), message (text), meta_data (json)
  Casts: changes => array, meta_data => array
  Relationships:
    - belongsTo: User (nullable)
    - morphTo: loggable (withTrashed)
  Implements: nothing — it is the log target, not the loggable
  Note: matchColorToAction() method kept — returns Filament color string based on meta_data.action

UserJournal
  Table: user_journals
  Key fields: user_id (FK, cascadeOnDelete), offer_id (nullable FK — raw FK only, no Eloquent relationship until Phase 2b), type (JournalTypeEnum), title (string), content (text), is_read (boolean default false)
  Casts: type => JournalTypeEnum, is_read => boolean
  Relationships:
    - belongsTo: User
    - offer_id is a plain column; Offer relationship added in Phase 2b
```

### Enums

```
JournalTypeEnum (string, backed)
  Cases: Info, Success, Warning, Rejection
  Implements: HasLabel, HasColor, HasIcon
  Values map directly from V1 JournalType (same string values — safe migration)
  Methods:
    - getLabel(): translated string
    - getColor(): 'primary' | 'success' | 'warning' | 'danger'
    - getIcon(): heroicon name
```

### Contracts (Domain/Contracts/)

```
LoggableModel (interface)
  Methods:
    - getLoggableTitle(): string
    - getLoggableResourceName(): string
    - getLoggableUrl(): ?string
    - getLoggableIcon(): ?string
```

### Traits (Domain/Traits/)

```
HasChangesLog
  bootHasChangesLog(): hooks into created/updated/deleted Eloquent events → calls ChangesLogService
  Respects static $loggable (fields to track) and $notLoggable (fields to exclude, default: id/timestamps)
  changesLogs(): morphMany(ChangesLog::class, 'loggable')
  Applied to: User model (Phase 1a), Offer model (Phase 2b)
```

### Domain events

None fired by Audit itself. Audit _listens to_ events from other modules.

---

## Application layer

### Services

```
ChangesLogService (Application/Services/)
  - createCreatedEntry(Model $model): void
  - createUpdatedEntry(Model $model): void
  - createDeletedEntry(Model $model): void
  Reads model's $loggable/$notLoggable arrays to diff changes.
  Writes to changes_logs table.
```

### Commands & Queries

No commands — Audit is write-via-trait, read-via-resource (With few exceptions related to user interaction).

```
Queries (optional, add if Filament needs them):
  - GetChangesForModelQuery → returns paginated ChangesLog collection for a given loggable
```

### Listeners (Phase 1a: scaffolded as stubs)

```
OfferJournalListener (Application/Listeners/)
  - Handles: OfferCreated, OfferArchived, OfferSold, OfferRejected, ... (one listener, multiple events)
  - Creates UserJournal entry for offer owner
  - Status: stub in Phase 1a (Offer events don't exist yet); fill in Phase 2b

OfferPanelNotificationListener (Application/Listeners/)
  - Handles: same Offer events
  - Sends Filament in-panel notification to offer owner via notifyNow()
  - Status: stub in Phase 1a; fill in Phase 2b
```

---

## Repository API

```
ChangesLogRepository extends ModelRepository
  - forModel(Model $model): Builder — filter by loggable_type + loggable_id
  - forUser(int $userId): Builder — filter by user_id (who made the change)
  - byAction(string $action): Builder — filter by meta_data->action (created/updated/deleted)

UserJournalRepository extends ModelRepository
  - forUser(int $userId): Builder
  - unread(int $userId): Builder — is_read = false
  - markRead(int $id): void — sets is_read = true
```

---

## Filament UI

### Resources

```
ChangesLogResource
  Model: ChangesLog
  Navigation: 'Dziennik zmian', heroicon-o-clipboard-document-list
  Pages: ListChangesLogs (read-only; no Create/Edit/Delete)
  Table:
    - loggable type (badge)
    - loggable title (via getLoggableTitle(), links to getLoggableUrl())
    - user name (who made the change)
    - action from meta_data (created/updated/deleted badge with color)
    - created_at
  Filters: by loggable type, by action, by user
  No form (read-only)

UserJournalResource
  Model: UserJournal
  Navigation: 'Dziennik ofert', heroicon-o-newspaper
  Pages: ListUserJournals (read-only)
  Table:
    - user name
    - offer_id (raw, no link until Phase 2b)
    - type (badge via JournalTypeEnum — HasColor/HasLabel)
    - title
    - is_read (icon column, toggle action)
    - created_at
  Actions: MarkAsRead action on table row
  Filters: by user, by type, by is_read
```

### Widgets

None in Phase 1a. After Phase 2b: widget showing unread journal count for current user.

---

## Done checklist

- [x] Module scaffolded (manually — docker unavailable in session)
- [x] Migrations: `create_changes_logs_table`, `create_user_journals_table`
- [x] `ChangesLog` model in `Domain/Models/`
- [x] `UserJournal` model in `Domain/Models/`
- [x] `JournalTypeEnum` in `Domain/Enums/` (implements HasLabel, HasColor, HasIcon)
- [x] `LoggableModel` interface in `Domain/Contracts/`
- [x] `HasChangesLog` trait in `Domain/Traits/`
- [x] `ChangesLogService` in `Application/Services/`
- [x] `ChangesLogRepository` and `UserJournalRepository` in `Domain/Repositories/`
- [x] `AuditEventServiceProvider` wires Observer (if any) + stub Listeners
- [x] `OfferJournalListener` stub in `Application/Listeners/`
- [x] `OfferPanelNotificationListener` stub in `Application/Listeners/`
- [x] `MarkJournalReadCommand` + Handler in `Application/Commands/`
- [x] `HasChangesLog` applied to `User` model in Users module (`Domain/Models/User.php`)
- [x] `User` implements `LoggableModel` (getLoggableTitle, etc.)
- [x] `ChangesLogResource` in `UserInterface/Filament/`
- [x] `UserJournalResource` in `UserInterface/Filament/`
- [x] Translation file `UserInterface/resources/lang/pl/resource.php`
- [x] Verify grep suite passes (no anti-patterns)
- [x] `dartisan migrate` passes
- [x] `pint` passes (24 files, 0 changes)
- [x] `refactor-plan.md` status updated to ✅

---

## Cross-module notes

```
Emits events: none

Listens to (Phase 2b, when Offers module exists):
  - OfferCreated → OfferJournalListener, OfferPanelNotificationListener
  - OfferArchived → same
  - OfferSold → same
  - OfferRejected → same
  - (full list determined during Offers scan)

Provides to other modules:
  - HasChangesLog trait → applied to User (Phase 1a), Offer (Phase 2b), others as needed
  - LoggableModel interface → implemented by any model that wants rich audit links
  - ChangesLogService → injected by HasChangesLog trait

Dependency: none (Audit is standalone)
```
