# Audit — functional spec

<!-- The authoritative functional reference for implementing Audit in V2.
     It describes behaviour, not implementation: no classes, tables, patterns or framework terms,
     except inside legacy traces. Data is described as information the business needs, not as schema.
     Every requirement carries a trace: (F-/BR-/W- ids, rNN; D- decision). -->

- **Agreed:** 2026-11-02 · **Discovery:** `.scratch/audit/discovery/discovery.md` · **V1 baseline:** `511ce3b`
- **Decision counts:** preserve 0 · correct 23 · new 9 · exclude 5 · pending 1
  - D-04, D-26 and D-34 are process records and are not counted. D-38 (accepted details) is counted as correct. D-15 was superseded by D-42 and is not counted.

## 1. Purpose and responsibilities

Audit is a core module. It answers two questions for the people who run the panel:
- **What happened to this record, who did it and when?** The Change history.
- **What went wrong in the system?** The Technical log.

V1 spread this over four unconnected mechanisms. Each covered different records and fields, and only one developer could read most of it. V2 replaces them with one Change history that any module joins by registering its records, plus a readable Technical log. (D-03, D-05, D-06)

**Audit owns:**
- the **Change history** of every record that a module registers as auditable;
- the **registration** through which modules declare what is audited and how it is shown;
- **Restore** from a Change entry, on record types where the owning module enables it;
- the per-record history section, the central audit page and their retention;
- the **Technical log page**: reading daily log files, the bezpieczeństwo channel and failed background jobs;
- the rules every module follows when writing to the Technical log.

**Audit does not own:**
- the Journal, which belongs to Users (Users spec §4.6);
- the notification bell;
- business rules of the audited records (for example "offer accepted"), which belong to their modules;
- the choice of what each module audits, which each module makes in its own discovery (D-22);
- the setup of log channels, which whoever needs a channel adds in configuration (D-28).

(D-05, D-14, D-22, D-28)

## 2. Domain concepts

These terms are defined in `GLOSSARY.md`: **Actor**, **Change history**, **Change entry**, **Restore**, **Technical log**. User, Super Admin, Permission and Extra Permission come from the Users spec.

The module also uses these local concepts:

- **Auditable record type.** A kind of record that a module has registered with Audit, for example "Oferta". Only registered types have a Change history. (D-03, D-33)
- **Kind of change.** Every Change entry is one of four kinds:
  - **utworzono:** the record was created;
  - **zmieniono:** fields or relations changed;
  - **usunięto:** the record was deleted;
  - **przywrócono rekord:** the record was brought back from deletion.

  A Restore produces an ordinary **zmieniono** entry, marked as a restore from the source entry. There are no business-named kinds such as "accepted": a status change is shown as `status: X → Y`. (D-14, D-16, D-18)
- **Channel.** One stream of the Technical log, kept as one file per day: **aplikacja** (application, default), **woocommerce** (WooCommerce/WordPress), **bezpieczeństwo** (security), **kolejka** (queue), and any channel added later. The decisions and the Users spec call a channel an "area". (D-23, D-27, D-28)

## 3. Actors and permissions

| Actor | May | May not | Trace |
|---|---|---|---|
| **Super Admin** | everything an Admin may; open the central audit page; Restore on enabled record types; open the Technical log page, read and download files; see, retry and remove failed background jobs | edit or delete Change entries; delete log files from the panel | D-10, D-12, D-16, D-29, D-31 |
| **Admin** | see the history section of any record they can open | open the central audit page or the Technical log page, or Restore, unless granted that Permission as an Extra Permission; edit or delete entries | D-12, D-16, D-41 |
| **User** (seller) | — | see any Change history, including that of their own offers; see the Technical log | D-12 |
| **System** | appear as the Actor of changes made by scheduled or integration work, under a named reason | — | D-08 |
| **Other modules** | register auditable record types (§4.2); offer Restore on their types; write to their own log channel | write Change entries directly or bypass recording; delete history | D-03, D-07, D-33 |

Audit declares four Permissions (the fourth, for Restore, is below):
- **"historia rekordu":** granted by default to Admin; Super Admin passes every check.
- **"dziennik audytu":** the central page, Super Admin only by default.
- **"logi techniczne":** the Technical log page and failed jobs, Super Admin only by default.

Restore is guarded by a fourth Permission, **"przywracanie zmian"**, granted to no Role: only the Super Admin passes by default, and a Super Admin may grant it to someone as an Extra Permission. (D-12, D-16, D-41)

## 4. Functional requirements

### 4.1 Recording changes

- **FR-01** [Correct] Every creation, change, deletion and return from deletion of a record of a registered type produces exactly one Change entry. This holds whatever the path: a panel page, a bulk action, a background job, an import, an integration or a console command. No path may change audited data silently. In V1 about twenty paths saved quietly and bypassed every history. (F-01, F-07, BR-05, BR-08, r01, r03; D-07)
- **FR-02** [Correct] A **zmieniono** entry holds, for each audited field that changed, its old and new value. An update that changes no audited field produces no entry. A **utworzono** entry holds the initial value of each audited field. A **usunięto** entry holds no field values but keeps the record's last title. (F-01, BR-03, r01; D-06, D-18)
- **FR-03** [Correct] Changes of tracked relations are recorded as items added and items removed, shown by their readable names (for example "dodano eurokod X, usunięto eurokod Y"). V1 recorded no relation changes. (BR-09, r03; D-07, D-14)
- **FR-04** [Correct] Every entry has an Actor:
  - the User who did it, including when their action finished in the background (for example a bulk price change);
  - otherwise the System, with the reason it names (for example "Import WooCommerce", "Harmonogram").

  The Actor is recorded exactly as the action carries it. During impersonation that is the impersonated User; the impersonation's start and end in the bezpieczeństwo channel give the context. Users are never deleted in V2 (Users spec), so an Actor's name always stays shown, also for a Deactivated User. V1 recorded no actor for background work, always wrote "Użytkownik…", and emptied the actor when a user was deleted. (BR-02, BR-12, Q-11, IMP-03, r01, r05; D-08, D-42)
- **FR-05** [Correct] Fields the registering module excludes are never recorded. A module excludes fields, never paths. Sensitive fields such as passwords are simply not registered. (BR-01, r01; D-07, D-17)
- **FR-06** [Correct] An entry records the time it happened. Entries cannot be edited or deleted by anyone in the panel. Only retention removes them (FR-20). (F-06, r01; D-10)

### 4.2 Registration by modules

- **FR-07** [New] A module registers each auditable record type with Audit and declares:
  - the type's name in Polish;
  - how to name one record (its title) and where its page is, for linking;
  - the audited fields: all of the record's fields except those it excludes;
  - a label for each audited field;
  - how each value is shown:
    - a reference to another record by that record's readable name (as V1 did for producer);
    - an enumerated value by its label;
    - an amount, a date or a list in readable form;
  - which relations are tracked as added/removed, and how their items are named;
  - whether Restore is offered for this type, and for which fields.

  A new module (for example a future workshops module) becomes auditable by registering, with no change to Audit. (D-03, D-33, D-14)
- **FR-08** [Correct] Every reference and relation item is shown by its readable name, resolved when the entry is displayed (as V1 did for producer). The entry keeps the raw value, which is also what Restore writes back. If the referenced record has been renamed, its current name is shown. If it has been deleted (records are soft-deleted), its name is still shown, marked as removed; if it is gone entirely, its identifier is shown, marked as removed. Field labels come from the module's current registration; a field the module no longer registers is still shown, under its technical name with raw values. V1 showed raw ids for type and colour. (F-04, r01; D-14, D-35, D-40)

### 4.3 Per-record history

- **FR-09** [Correct] The page of every record of a registered type has its own **Historia zmian** section (a tab). Admins and above see it (Permission "historia rekordu"). It lists the record's entries newest first in a plain table:
  - when;
  - Actor (the User's name, or "System: {reason}");
  - kind of change.

  The table can be filtered by Actor and by kind. The changed fields are **not** in the table. In V1 the offer history was a tab for admins with no filters; delete actions were defined on it (whether they showed at runtime was not verified). (F-02, r01; D-12, D-19)
- **FR-10** [Correct] Opening an entry shows its details: date, Actor, kind, and for each changed field or relation its label, the old value and the new value. A custom layout may replace this later. (F-04, r01; D-19)
- **FR-11** [Correct] The user detail shows the User's own history like any other record. V1's "Rejestr zmian użytkownika" (changes made **by** a User) is dropped. (F-03, r01; D-21)

### 4.4 Central audit page

- **FR-12** [New] A **Dziennik audytu** page lists every Change entry of every module, newest first. The record-type filter lists the currently registered types. Holders of "dziennik audytu" can open it; by default that is the Super Admin. Filters:
  - module or record type;
  - one specific record;
  - Actor: a given User, or System;
  - kind of change;
  - date range.

  Each row opens the entry's details (FR-10) and links to the record. The link is not shown if the record no longer exists. There is no export. It replaces V1's "Logi" page, which only one hard-coded e-mail could open. (F-08, Q-06, r02, r05; D-12, D-20)

### 4.5 Restore

- **FR-13** [Correct] On record types where the owning module offers Restore, a holder of "przywracanie zmian" (by default only the Super Admin) sees a **Przywróć** action on an entry in the record's history. Restore writes back exactly the old values of the fields held in that entry, and only of fields the module allows to be restored. If the entry changed only the status, only the status is restored.
  - Nothing else is compared or changed: no snapshots, no check against later changes.
  - Only fields are restored, never relations. An entry holding only relation changes offers no Restore.
  - A **utworzono** or **usunięto** entry, or an entry of a deleted record, offers no Restore.

  (F-05, F-10, Q-02, Q-10, r01, r05; D-11, D-16, D-38)
- **FR-14** [Correct] A Restore is carried out by the owning module as an ordinary change of the record. Its validation and its usual consequences apply, including synchronisation with external channels. It produces a new **zmieniono** entry, by the restoring User, marked as restored from the source entry. Restore asks for confirmation in a simple dialog listing the fields and values to be written ("Przywrócić: status → Aktywna, cena → 1200 zł?"), with no comparison to the current state. If the owning module rejects the change, nothing is written and the reason is shown.
  - V1 had two weakly guarded mechanisms. "Przywróć nowsze/poprzednie" had no confirmation and no permission beyond seeing the history tab. A revisions page let offer owners restore through a direct URL.

  (W-02, W-05, r05; D-11, D-16, D-36, D-38)

### 4.6 Technical log

- **FR-15** [New] The application writes its technical log to **daily files per channel** instead of one ever-growing file. Expected channels:
  - application (default);
  - WooCommerce/WordPress;
  - security;
  - queue.

  Whoever needs a further channel adds it in configuration; Audit does not register channels. (F-11, r04; D-02, D-28)
- **FR-16** [New] A **Logi techniczne** page lets holders of "logi techniczne" (Super Admin by default) work with the daily files:
  - choose a channel and a day; the page opens on aplikacja, today;
  - see that day's entries newest first, paged, in a lean table: time, level, the message shortened to one line;
  - filter by level (debug, info, notice, warning, error, critical, alert, emergency) and search the message and context;
  - a day with no file or no entries shows "brak wpisów";
  - open an entry to see the full message, its context and any stack trace;
  - download the day's file.

  Files cannot be deleted from the panel. In V1 the log was readable only from the server shell. (F-11, F-13, Q-08, r04; D-02, D-29, D-38)
- **FR-17** [New] The **bezpieczeństwo** channel receives:
  - sign-in;
  - failed sign-in;
  - sign-out;
  - password reset requested, and password reset completed;
  - impersonation start and end (also naming whom).

  Every security entry records who, when and from which IP address.

  V1 recorded none of these. (BR-14, r06; D-23, D-27, D-38)
- **FR-18** [New] A **Nieudane zadania** tab on the log page lists background jobs that failed: job name, time and error. A Super Admin can retry each one, or remove it after confirming. Removing a failed job does not touch the Change history. In V1 failed jobs were stored but nobody could see them. (BR-11, BR-15, r04, r06; D-31, D-38)
- **FR-19** [Correct] Every module writing to the Technical log follows three rules:
  - an error is logged at error level, with context: what failed, which record, and the external system's response;
  - no error is swallowed without a trace;
  - a user-facing message may say "szczegóły w logu" only for a log readable on the Technical log page.

  V1 logged errors at info level, swallowed import failures, and pointed users to a "dziennik zdarzeń" the panel did not have. (F-11, F-13, BR-11, r04; D-25)

### 4.7 Retention

- **FR-20** [Correct] Change entries older than the retention period, counted from the entry's time, are removed by a nightly cleanup. There is one period for the whole Change history, set in Settings, 365 days by default. In V1 nothing was ever removed: the 365-day setting was never run, and the other histories had no limit. (BR-10, G-04, r02, r03; D-01, D-13)
- **FR-21** [New] Daily log files older than 14 days are removed automatically. The period is fixed in configuration. (D-30)

## 5. Business rules and constraints

- **RULE-01** Only data changes are Change entries: created, changed, deleted, brought back. Events that change no data, such as impersonation or a sign-in, go to the Technical log. (D-14, D-23)
- **RULE-02** Recording cannot be bypassed for a registered type. A change and its entry are saved together or not at all; a change to audited data that leaves no entry is a defect. (D-07, D-39)
- **RULE-03** Every entry has exactly one Actor, either a User or the System with a reason. Background work started by a User belongs to that User. (D-08; design reference: `docs/adr/0004`)
- **RULE-04** Change entries are immutable. Only retention removes them. History outlives the record it describes. (D-10, D-18)
- **RULE-05** Restore writes exactly the recorded old values of the chosen entry's restorable fields, through the owning module, and is itself recorded. (D-16)
- **RULE-06** A User without "historia rekordu" sees no history, not even for their own offers. (D-12)
- **RULE-07** References are shown by readable name, resolved at display. A raw identifier is shown only when the referenced record no longer exists. (D-14, D-35)

## 6. Workflows

### WF-01 A registered record changes

- **Actors / trigger:** any change to a record of a registered type, from any path.
- **Preconditions:** the record type is registered.
- **Main flow:**
  1. The owning module completes the change.
  2. Audit determines the kind of change and, for **zmieniono**, which audited fields and tracked relations changed.
  3. If nothing audited changed, stop.
  4. Audit stores one Change entry: record type and record, last title, kind, each change with its readable old and new value, the Actor, and the time.
- **Postconditions:** the entry appears in the record's history and on the central page.
- **Alternative paths:**
  - In a bulk action each record gets its own ordinary entry. There is no grouping. (D-09)
  - Work finished in the background carries the User who started it.
- **Errors and exceptional scenarios:** a change and its Change entry are saved together or not at all. If the entry cannot be stored, the whole operation fails and nothing changes (RULE-02). For **usunięto**, the record's last title is kept.
- **Trace:** (W-01, r01, r03; D-07, D-08, D-09, D-14, D-18, D-39)

### WF-02 Super Admin restores from an entry

- **Actors / trigger:** a holder of "przywracanie zmian" (Super Admin by default) chooses **Przywróć** on an entry in a record's history.
- **Preconditions:**
  - the owning module offers Restore for this record type;
  - the entry is a **zmieniono** entry with at least one restorable field.
- **Main flow:**
  1. Audit shows a confirmation listing each restorable field of the entry and the old value that will be written (D-36).
  2. The Super Admin confirms.
  3. Audit asks the owning module to change the record to those values.
  4. The module validates and applies the change as an ordinary change, with its usual consequences.
  5. WF-01 records a new **zmieniono** entry by that User, marked "przywrócono z wpisu z {data}".
- **Postconditions:** the record holds the restored values. Both entries remain in the history.
- **Alternative paths:** none.
- **Errors and exceptional scenarios:**
  - the owning module rejects the values (for example a referenced record no longer exists) → nothing changes and the reason is shown;
  - the record has been deleted → Restore is not offered.
- **Trace:** (W-02, W-05, r01, r05; D-11, D-16)

### WF-03 Retention cleanup

- **Actors / trigger:** System, nightly.
- **Main flow:**
  1. Remove Change entries older than the period set in Settings (365 days by default), counted from each entry's time.
  2. Remove daily log files older than 14 days.
- **Errors and exceptional scenarios:** a failed cleanup is logged as an error in the aplikacja channel and runs again the next night.
- **Trace:** (BR-10; D-01, D-13, D-30, D-38)

### WF-04 Super Admin investigates a failure

- **Actors / trigger:** a Super Admin opens Logi techniczne.
- **Main flow:**
  1. Pick a channel and a day.
  2. Filter by level or search.
  3. Open an entry for its context and stack trace.
  4. If needed, download the file, or switch to Nieudane zadania to retry or remove the failed job.
- **Trace:** (F-11, BR-11, r04; D-29, D-31)

## 7. States and transitions

| Item | States | Transition | By | Effect |
|---|---|---|---|---|
| Change entry | stored → expired (removed) | age exceeds the retention period | System (WF-03) | gone from every view |
| Daily log file | written → removed | older than 14 days | System (WF-03) | gone from the log page |
| Failed background job | failed → retried / removed | Super Admin action | Super Admin | retried: back in the queue; removed: gone from the list |

Change entries have no other state. They are never edited, hidden or deleted by hand. (D-10)

## 8. Data requirements

- **Change entry:**
  - record type and record identity;
  - the record's title at the time;
  - kind of change;
  - each changed field or relation with its label and its readable old and new value;
  - the Actor: a User, or the System with its reason;
  - the time;
  - for a Restore, the entry it was restored from.
- **Restore data:** each changed value is kept exactly as recorded, so Restore can write it back unchanged. (D-16, D-35)
- **Retention:** one period for all entries, in Settings, 365 days by default. Daily log files are kept 14 days. (D-13, D-30)
- **Migrating V1 data:** none. The Change history starts empty on migration day. V1 change-log, activity-log and offer-version data are not imported and stay only in the V1 database backup. See `.scratch/data-migration/audit/spec.md`. (D-37)

## 9. Cross-module interactions

- **Every module:**
  - registers its auditable record types (FR-07);
  - names the System reason for its background work;
  - offers Restore where it wants it, carrying the Restore out as its own ordinary change (FR-14);
  - follows FR-19 when it logs.

  (D-03, D-08, D-16, D-25, D-33)
- **Users:**
  - provides User names for Actors, the Permission checks and the Role defaults;
  - registers the User as an auditable record type. Its "password change required" marker is an ordinary audited field;
  - writes sign-in, failed sign-in, sign-out, password reset and impersonation start/end to the bezpieczeństwo channel;
  - hosts the user's history section on the user detail.

  The Users spec §9 was amended on 2026-11-02 to match. (X-02, X-08, Q-12; D-15, D-21, D-23, D-27)
- **Settings:**
  - holds the retention period of the Change history;
  - registers settings as an auditable record type, if its discovery decides so.

  V1 audited settings changes (r03). (X-06; D-13, D-22)
- **Offers:** the example consumer. It is expected to register offers with their fields, references resolved by name (type, producer, colour) and their relations (eurokody, wyposażenie, zdjęcia), and may offer Restore on, for example, price and status. The final list belongs to Offers discovery. (X-01; D-22)
- **Background work:** jobs started by a User carry that User as Actor. Scheduled work carries a named System reason. (D-08; design reference: `docs/adr/0004`)

## 10. External integrations

- Audit itself talks to no external system.
- A Restore may cause the owning module to synchronise with WooCommerce/WordPress, like any other change of that record (FR-14). (r05)
- Integration failures must be visible on the Technical log page, in their channel, with the external response (FR-19).

## 11. Edge cases

- A record is deleted → its history stays until retention, and the **usunięto** entry shows the last title. The central page shows the entries without a link. (D-18)
- A record is brought back from deletion → **przywrócono rekord** entry. (D-18)
- A referenced record (for example a producer) is renamed after a change → the entry shows the current name. If it is deleted → its name marked as removed; if gone entirely → its identifier marked as removed. (FR-08; D-35, D-40)
- A save changes no audited field (for example only an excluded sync timestamp) → no entry. (FR-02, FR-05)
- A bulk price change on 200 offers, run by Admin A, completes in the background → 200 ordinary entries, each with Actor A. (D-08, D-09)
- An Admin impersonating seller S edits an offer → the Actor is S, as the action carries it. Start and end of the impersonation are in the bezpieczeństwo channel. (D-23, D-42)
- Restore of an entry whose field has since changed again → the entry's old value is written anyway, as recorded. (D-16)
- Restore whose value the owning module refuses → nothing changes and the reason is shown. (FR-14)
- A failed job is retried and fails again → it reappears in Nieudane zadania with the new error. (D-31)
- A User asks to see their own offer's history → not available to them. (D-12)

## 12. Differences from V1

| V1 behaviour | V2 behaviour | Class | Reason | Trace |
|---|---|---|---|---|
| Three histories (change log for offers, activity log for 8 models, offer versions) with different fields and actors | One Change history for every registered record type | correct | One reliable source | D-06 |
| Only Offer on the change log; others hand-picked 2–8 fields | Modules register types; all fields except exclusions | correct / new | Universal; new modules join without Audit changes | D-03, D-07, D-33 |
| ~20 quiet save paths and all relation changes left no trace | Every change of a registered record recorded, relations included | correct | Completeness | D-07 |
| No actor for background work; "Użytkownik…" text always | Actor = User or System with a reason | correct | Accountability | D-08, D-42 |
| Only producer resolved to a name; type and colour shown as raw ids | Every reference and relation resolved to a readable name at display | correct | Readable history | D-14, D-35 |
| Entries deletable singly and in bulk | Immutable; only retention removes | correct | Audit integrity | D-10 |
| Force delete wiped an offer's history; restore not logged | History outlives the record; return from deletion recorded | correct | Audit integrity | D-18 |
| Rollback with no confirmation or permission; revisions page reachable by owners | Literal Restore by Permission (Super Admin by default), on enabled types, through the owning module | correct | Safety | D-11, D-16, D-41 |
| "Logi" visible to one hard-coded e-mail; unused permissions | Permissions: historia rekordu, dziennik audytu, logi techniczne | correct | Proper access | D-12 |
| Activity log 365 days configured but never cleaned; others unlimited | Scheduled cleanup, one period in Settings, 365 days by default | correct | Bounded growth | D-01, D-13 |
| "Rejestr zmian użytkownika" (changes made by a User) | Dropped | exclude | Unused | D-21 |
| One growing log file, readable only by shell | Daily files per channel, Technical log page, 14 days | new | Operability | D-02, D-28..D-30 |
| No record of sign-ins, failed sign-ins, impersonation | bezpieczeństwo channel | new | Incident response | D-27 |
| Failed jobs invisible | Nieudane zadania tab with retry and remove | new | Operability | D-31 |
| Errors at info level, swallowed import failures | Logging rules for every module | correct | Operability | D-25 |
| Otomoto queue monitor with "Wyczyść log" | Gone with Otomoto | exclude | Otomoto not in V2 | D-24 |

## 13. Approved additional functionality

All four approved additions are specified in §4 and §6:
- **ADD-01** module registration (FR-07, FR-08; D-03, D-33);
- **ADD-02** central audit page (FR-12; D-20);
- **ADD-03** Technical log page with daily files, retention and failed jobs (FR-15, FR-16, FR-18, FR-21, WF-04; D-02, D-28..D-31);
- **ADD-04** security events in the Technical log (FR-17; D-27).

(D-34)

## 14. Exclusions and out of scope

- Grouping entries of one bulk operation — history is read per record (D-09).
- Named business actions in the Change history ("accepted", "impersonation started") — not data changes (D-14).
- Masking sensitive values ("password changed") — such fields are simply not audited (D-17).
- Full-record snapshots, version diffs, conflict checks on Restore (D-06, D-16).
- Restore of deleted records and of creation entries (D-16). Restore by Admins only through an Extra Permission (D-41).
- Security events beyond FR-17 (2FA changes, file uploads) — not selected (D-27). Permission grants are Change entries of the User (Users spec §9).
- "Rejestr zmian użytkownika" (D-21).
- Export of the audit page (D-20).
- Deleting log files or Change entries from the panel (D-10, D-29).
- Otomoto queue monitor and its log (D-24).
- The Journal (Users) (D-05).
- Importing V1 history (D-37).

## 15. Pending decisions

- **D-32** Bell alerts to Super Admins on critical errors or failed jobs. Options: add later, once the noise level is known, or never. Blocks nothing.

## 16. Traceability

| Spec item | Discovery items | Reports | Decisions |
|---|---|---|---|
| FR-01, RULE-02, WF-01 | F-01, F-07, BR-05, BR-08 | r01, r03 | D-07, D-39 |
| FR-02 | F-01, BR-03 | r01 | D-06, D-18 |
| FR-03 | BR-09, IMP-01 | r03 | D-07, D-14 |
| FR-04, RULE-03 | BR-02, BR-12, Q-11, IMP-03 | r01, r05 | D-08, D-42 |
| FR-05 | BR-01 | r01 | D-07, D-17 |
| FR-06, RULE-04 | F-06, Q-03, IMP-07 | r01 | D-10, D-18 |
| FR-07, FR-08, RULE-07 | ADD-01, F-04, IMP-02 | r01 | D-03, D-14, D-33, D-35, D-40 |
| FR-09, FR-10 | F-02, F-04 | r01 | D-12, D-19 |
| FR-11 | F-03 | r01 | D-21 |
| FR-12 | ADD-02, F-08, Q-06, IMP-04 | r02, r05 | D-12, D-20 |
| FR-13, FR-14, RULE-05, WF-02 | F-05, F-10, Q-02, Q-10, W-02, W-05 | r01, r02, r05 | D-11, D-16, D-41 |
| FR-15, FR-16, WF-04 | ADD-03, F-11, F-13, Q-08 | r04 | D-02, D-28, D-29 |
| FR-17 | ADD-04, BR-14, Q-12 | r06 | D-23, D-27 |
| FR-18 | BR-11, BR-15 | r04, r06 | D-31 |
| FR-19 | F-11, F-13, IMP-06 | r04 | D-25 |
| FR-20, FR-21, WF-03 | BR-10, G-04, IMP-05 | r02, r03, r04 | D-01, D-13, D-30 |
| RULE-01 | Q-12 | r06 | D-14, D-23 |
| RULE-06 | Q-06, Q-10 | r02, r05 | D-12 |
| §3 permissions | F-02, F-08, Q-06 | r01, r02 | D-12, D-16, D-41 |
| §14 exclusions | F-03, F-12, Q-09 | r01, r04 | D-09, D-14, D-17, D-21, D-24 |
