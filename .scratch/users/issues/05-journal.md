# 05 - Journal

Status: resolved
Blocked by: 02

Spec: FR-29..FR-33, RULE-12, §7 Journal entry, §11 Journal cases, §9 "Every module" and "Panel notification channel". Decisions: D-03, D-04, D-16..D-18, D-28, D-47. Glossary: Journal, Journal entry.

## What

- **Writing (FR-29):** any module adds an entry for a User through Users' module API.
  - Fields: kind, severity (info, success, warning, rejection), title, plain-text content, and an optional link to the subject.
  - An unknown User gets no entry, and the caller's operation still completes. A Deactivated User still gets entries (D-47).
- **Reading (FR-30, FR-31):** the owner sees their own entries only, newest first.
  - The unread count shows on the navigation item.
  - Entries can be filtered by kind and severity.
  - The owner can mark an entry read, open its details (which marks it read) and follow its link. A missing subject shows a warning.
  - The owner can delete one entry or many. No hand creation or editing.
- **Notification (FR-32):** every new entry sends a panel notification that opens that entry and marks it read.
  - The "go to subject" button is named after the entry's kind; kind `offer` reads "Przejdź do oferty".
  - Use the shared panel notification channel. If Core has no helper yet, add the smallest one that sends to a User and to the "Admins" group (D-04), and note it in Comments.
- **Plain text (FR-33):** content is always escaped.

## Done when

- FR-29..FR-33 are tested, including the owner-only scope, escaping and the missing-subject warning.
- The module API for writing entries is documented in the Users module so other modules can use it.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** FR-29..FR-33 and RULE-12 in Users. `JournalEntry` entity (kind lowercase word, title required, plain content, optional `subjectId`; `markReadBy`/`ensureOwnedBy`), `JournalSeverity` enum (info/success/warning/rejection), `JournalEntryRepository` port + Eloquent adapter + mapper, read-only `JournalEntryModel` + factory, migration `journal_entries` (drops Audit's old `user_journals` scaffold). Commands `AddJournalEntry`, `MarkJournalEntryRead`, `DeleteJournalEntries` (owner is the guard in the entity; a foreign id refuses the whole batch; a system actor is never an owner). Writing API: `Users\Application\ModuleApi\JournalWriter::add(actor, userId, kind, severity, title, content, ?subjectId): ?int` (documented in its docblock); an unknown User returns null, writes nothing, dispatches nothing, so the caller's transaction carries on. Subjects: `JournalSubjects::register(kind, JournalSubjectResolver)` lets the module owning a kind (Offers for `offer`) say how to reach a subject; no resolver or a gone subject means a warning when following the link. Reading: every read goes through `JournalQueries::ownedBy($userId)` (no unscoped query exists); `JournalEntryResource` (slug `journal`) scopes `getEloquentQuery()` to the signed-in User, so a foreign entry is a 404; unread count as navigation badge; filters kind and severity; mark read, open details (view page marks read through the command), delete one and bulk delete through commands; no create or edit. Notification (FR-32): event `JournalEntryAdded` -> listener `SendJournalEntryNotification` (after commit; failures reported, never thrown) -> new shared Core channel `Core\Contracts\PanelNotifier` (impl `Core\UserInterface\FilamentPanelNotifier`, database notifications, content escaped) with an "open entry" action that marks read and a "go to subject" button named after the kind (`offer` -> "Przejdź do oferty", unknown kind -> "Przejdź do obiektu") only when the entry has a reachable subject. Panel now has `->databaseNotifications()` and Core has the `notifications` table migration. Tests for entity, mapper, queries, three handlers, writer, subjects, listener, notifier, list and view pages (owner-only scope, escaping, missing-subject warning).
- **Audit pieces:** removed, not moved: `UserJournal` model, `UserJournalRepository`, `JournalTypeEnum`, `JournalOwnershipException`, `MarkJournalRead` command, `UserJournalResource` + pages/table, the `user_journal` and `journal_types` translations, the `user_journals` migration, the `OfferJournalListener` and `OfferPanelNotificationListener` stubs and the commented Offer wiring in `AuditEventServiceProvider`, plus their deptrac and test-presence baseline lines. Offers will write through `JournalWriter` (one stub did both journal and bell, the writer now does both). Audit keeps only the change log.
- **Deviations:** (1) The `PanelNotifier` helper takes User ids, not a group: Core knows no module, so to reach "Admins" a caller passes `AdminRecipients::userIds()`. (2) "Link with label" is stored as `subject_id` only; the label comes from the kind, as FR-32 words it, and the address from the resolver. Spec §7's "optional link with label" is read this way; a free-form URL could not detect a vanished subject. (3) `AddJournalEntry` needs no Permission (any module may write, spec §3); the actor is recorded in the command only. (4) `JournalEntryUrls` interface lives in `ModuleApi/` (not Domain) because deptrac lets UserInterface depend on ModuleApi but not on Domain contracts; it is implemented by the UI and is not meant for other modules. (5) `AdminPanelProvider` gained `->databaseNotifications()`; ticket 03 edits the same provider (one line, trivial merge). (6) No `JournalEntry` domain policy/delete event; no audit events for Journal (none specified).
- **Seams left:** Offers registers its `offer` resolver in `JournalSubjects` and writes through `JournalWriter`; ticket 04 writes `users` entries for the "Admins" group and registers a `users` resolver; ticket 07 must keep Deactivated Users receiving entries (the writer only checks existence). V1 entry migration (kind `offer`, HTML stripped, `offer_id` -> `subject_id`) is data migration work. Live updating of the bell uses Filament polling only.
- **Open questions:** none
- **Check:** composer check green


### Review — 2026-11-01
- **Verdict:** approved after 0 fix rounds
- **Notes:** The Journal now belongs to Users (`journal_entries`); Audit's Journal pieces, including the `user_journals` migration file, were deleted rather than moved — an existing dev database keeps an orphaned `user_journals` table, and ticket 10 migrates V1 entries. Core gained `PanelNotifier` (User ids; "Admins" = `AdminRecipients::userIds()`) and the `notifications` table. Links are `subject_id` + a per-kind `JournalSubjects` resolver the owning module registers; 04 registers `users`, Offers registers `offer`. `AddJournalEntry` needs no Permission by design (any module writes).
