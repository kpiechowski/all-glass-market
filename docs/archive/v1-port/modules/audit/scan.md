# Audit — V1 Scan

> Written from V1 exploration during agent setup redesign (2026-06-29).
> V1 source: `/home/kp/laravel/projekty/AllGlass/`

---

## Models

### `ChangesLog` (`app/Models/ChangesLog.php`)

```
Table: changes_logs
Fields:
  - id: bigIncrements
  - user_id: foreignId nullable (nullOnDelete)
  - loggable_type: string (morphs)
  - loggable_id: bigUnsigned (morphs)
  - changes: json
  - message: text
  - meta_data: json
  - timestamps
Casts: changes => array, meta_data => array
Relationships:
  - belongsTo: User
  - morphTo: loggable (withTrashed)
Methods:
  - getLoggableResourceName(): delegates to loggable if LoggableModel, else class_basename
  - getLoggableTitle(): delegates
  - getLoggableUrl(): delegates
  - getLoggableIcon(): delegates
  - matchColorToAction(): returns Filament color string based on meta_data.action
```

### `UserJournal` (`app/Models/UserJournal.php`)

```
Table: user_journals
Fields:
  - id: bigIncrements
  - offer_id: foreignId nullable
  - user_id: foreignId constrained (cascadeOnDelete)
  - type: enum (JournalType cases)
  - title: string
  - content: text
  - is_read: boolean default false
  - timestamps
Casts: none explicitly set
Relationships:
  - belongsTo: Offer
  - belongsTo: User
Traits: HasFactory
Note: $guarded = [] (accepts all mass assignment)
```

### `UserNotification` (`app/Models/UserNotification.php`)

```
Table: user_notifications
Fields: (from 2025_03_29_174632_create_user_notifications_table.php — not read, infer from model)
  - id, user_id, timestamps (minimal model, no casts, no fillables set)
Relationships:
  - belongsTo: User
Note: model body is almost empty — feature was never fully built
```

---

## Filament UI

### Resources

```
UserJournalResource (app/Filament/Resources/UserJournalResource.php)
  Navigation label: 'Mój dziennik'
  Icon: heroicon-o-newspaper
  Model: UserJournal
  Pages: ListUserJournals, CreateUserJournal, EditUserJournal
  Table: complex layout (Split + Stack columns), shows offer data, journal type, read status
  Imports: JournalType enum, OfferResource (cross-resource link in table row action)
  Notable: filters by Auth::user() — shows only the logged-in user's journal entries
```

### No standalone Audit pages in V1.

### V1 ChangesLog module (`app/Modules/ChangesLog/`)

```
ChangesLogServiceProvider — registers service, facade, trait
HasChangesLog (trait):
  - bootHasChangesLog: hooks into created/updated/deleted Eloquent events
  - logs via ChangesLogger facade → ChangesLogService
  - respects $loggable and $notLoggable static arrays on the model
  - changesLogs() morphMany relationship
LoggableModel (interface):
  - getLoggableTitle(): string
  - getLoggableResourceName(): string
  - getLoggableUrl(): ?string
  - getLoggableIcon(): ?string
ChangesLogService — creates entries in changes_logs table
ChangesLogger (facade) — wraps ChangesLogService
ChangesDetailsInfolistSchema, ChangesTableSchema, UserChangesTableSchema — Filament schema helpers
```

---

## Services / Actions / Jobs / Listeners

```
Services:
  - ChangesLogService: creates created/updated/deleted log entries in changes_logs

Actions (Filament):
  - JournalAction (abstract, app/Filament/Actions/Abstracts/JournalAction.php):
      abstract processAction(Offer $record): void
      __invoke($record, $info): creates UserJournal entry + sends Filament notification to offer owner
      Concrete subclasses (app/Filament/Actions/Journal/):
        - ActivateFromDraftAction
        - DraftOfferCreatedAction
        - OfferAcceptedAction
        - OfferAmountReservedAction
        - OfferAmountSoldAction
        - OfferArchivedAction
        - OfferCreatedAction
        - OfferFullyReservedAction
        - OfferFullySoldAction
        - OfferRejectedAction
      Each subclass sets $entryTitle, $entryContent, $entryType and calls processAction()

NotificationService:
  - PanelNotification (app/Services/NotificationService/PanelNotification.php): helper
  - OfferNotificationHandler: sends in-panel notifications to offer owners

Notifier:
  - app/Services/Notifier/Notifier.php: generic notification wrapper
```

---

## Console commands

None directly for Audit.

---

## Events & Policies

```
Events fired: none — JournalAction is called imperatively from Filament pages/observers
Policies:
  - UserJournalPolicy (app/Policies/UserJournalPolicy.php): exists, details not scanned
```

---

## Migrations

```
Table: changes_logs
  - id
  - user_id: foreignId nullable (nullOnDelete)
  - loggable_type + loggable_id: morphs
  - changes: json
  - message: text
  - meta_data: json
  - timestamps

Table: user_journals
  - id
  - offer_id: foreignId nullable
  - user_id: foreignId constrained (cascadeOnDelete)
  - type: enum (JournalType values)
  - title: string
  - content: text
  - is_read: boolean default false
  - timestamps

Table: user_notifications
  - id
  - user_id (inferred)
  - timestamps
  (schema not fully read — minimal model suggests minimal table)
```

---

## Cross-module bleeds

```
- Offer model uses HasChangesLog trait → logs to changes_logs
- JournalAction subclasses are invoked from Offer-related Filament pages (not listed here — discovered during Offers scan)
- UserJournalResource references OfferResource for URL generation in table row action
- UserNotification referenced from Services/NotificationService/
```

---

## Health Assessment

- [x] Module is fully implemented — ChangesLog sub-module is solid
- [x] Migration and model exist for all three tables
- ⚠️ MIXED CONCERNS — JournalAction abstract hierarchy
- ⚠️ EMPTY MODEL — UserNotification is a skeleton
- ⚠️ ABSTRACT HIERARCHY — not needed in V2

**⚠️ CONCERNS:**

```
1. Class: JournalAction (and all 10 subclasses)
   Problem: Mixes two unrelated concerns in one abstract:
     (a) Creating a UserJournal record for the offer owner
     (b) Sending an in-panel Filament notification
   These are invoked imperatively from Filament pages/observers rather than via domain events.
   This creates direct coupling: Audit depends on Offers at the UI layer.
   Impact on V2: In V2, offer status changes fire domain events (OfferCreated, OfferArchived, etc.).
   The correct V2 pattern is TWO separate listeners on those events:
     - AuditJournalListener: creates UserJournal entry
     - AuditNotificationListener: sends panel notification
   The JournalAction hierarchy should NOT be ported — replace entirely.

2. Class: UserNotification (app/Models/UserNotification.php)
   Problem: Model body is nearly empty. Table migration exists but no fillables, no casts,
   no usage in resources. The feature was started and abandoned in V1.
   Impact on V2: Decide before planning whether to include this or drop it.
   Suggestion: Filament's built-in database notifications (via notifyNow) are already used in V1.
   UserNotification as a custom model adds no value — can be dropped in V2.
```

**→ STOP: Show concerns to user before proceeding to PLAN step.**
