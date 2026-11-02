# Audit — discovery

<!-- Working document for the module-discovery skill. Only the manager writes it.
     Item ids are stable and never reused. Every finding cites a report (rNN) and its V1 evidence.
     Tags: confirmed · observed · inferred · ambiguous · unknown · defect? · decide -->

## A. Metadata

- **Status:** spec-written
- **Next actions:** architecture process for Audit (ticket `.scratch/architecture/issues/06-migrate-audit.md` was waiting on this spec). Spec: `.scratch/audit/functional-spec.md`; data migration: none (D-37), `.scratch/data-migration/audit/spec.md`.
- **Started:** 2026-11-01 · **Last updated:** 2026-11-01
- **V1:** `/home/kp/laravel/projekty/AllGlass/` @ `511ce3b` (dirty: `docker-compose.yml` modified, `SECURITY_INCIDENT_2026-10.md` untracked; neither touches app code)
- **V2 state:** exists in `app/Modules/Audit`, built by the retired 5-step workflow (Phase 1a). Predates this discovery; not evidence of V1 behaviour. Ticket `.scratch/architecture/issues/06-migrate-audit.md` waits on this spec.
- **Prior material:** `docs/archive/v1-port/modules/audit/scan.md`, `plan.md` (leads, `inferred`); `.scratch/users/functional-spec.md` (agreed; owns the Journal); `GLOSSARY.md`; `docs/adr/0001..0004`.

### Scope

- **Apparent responsibility:** the technical record of who changed what and when, across every module: change history of records, an activity/event log, and (per user brief) a log page for operators. Modules register into it through Audit's own interface.
- **User brief (2026-11-01):** the Journal ("Mój dziennik", `UserJournal`, `JournalAction*`) is already adopted by Users (Users spec FR-29..FR-32, D-16, D-28) and generalised there — **out of scope here**. Focus on the technical side of audit, which V1 limited; room for a good log page and for modules registering into the change log through Audit's interface. Further scope settled in grilling.
- **Anchors:**
  - *Custom change log:* `app/Modules/ChangesLog/**` (ServiceProvider, `HasChangesLog`, `LoggableModel`, `ChangesLogService`, `ChangesLogger` facade, `Components/ChangesTableSchema`, `UserChangesTableSchema`, `ChangesDetailsInfolistSchema`); `app/Models/ChangesLog.php`; migration `2025_04_12_181156_create_changes_logs_table.php`; `app/Filament/Resources/Shared/ChangesRelationManager.php`; `app/Filament/Resources/UserResource/RelationManagers/UserChangesRelationManager.php`.
  - *Spatie activity log:* `config/activitylog.php`, `config/filament-activitylog.php`, `app/Policies/ActivityPolicy.php`, `app/Filament/Utils/PanelConfig.php` (`ActivitylogPlugin`), migrations `2024_10_14_19350{7,8,9}_*activity_log*`; models using `LogsActivity` (`Car`, `Setting`, `OfferType`, `OfferProducer`, `OfferColor`, `OfferEquipment`, `User`?).
  - *Versioning:* `config/versionable.php`, migrations `2019_05_31_042934_create_versions_table`, `2020_07_03_163707_add_deleted_at_to_versions`; `Offer` (Versionable), `OfferRevisionsPage`, `EditOffer`.
  - *Technical logging:* `config/logging.php`; ~86 `Log::`/`logger()` calls in `app/`; `jobs`/`failed_jobs` tables; integration sync tables (`wordpress_syncs`, `woo_commerce_syncs`, `otomoto_bulk_actions`) only as possible operator-facing logs.
- **Vocabulary:** `ChangesLog`, `changes_logs`, `HasChangesLog`, `ChangesLogger`, `ChangesLogService`, `LoggableModel`, `loggable`, `$loggable`, `$notLoggable`, `meta_data`, `matchColorToAction`, `changesLogs`, `ChangesRelationManager`, `UserChangesRelationManager`, `ChangesTableSchema`, `ChangesDetailsInfolistSchema`; `activity_log`, `Activity`, `LogsActivity`, `getActivitylogOptions`, `LogOptions`, `activity()`, `causer`, `subject`, `batch_uuid`, `log_name`, `ActivitylogPlugin`, `rmsramos`, `*_activitylog` permissions; `Versionable`, `versions`, `versionable`, `revision`, `OfferRevisionsPage`; `Log::`, `logger(`, `failed_jobs`, `report(`; Polish labels "Dziennik zmian", "Historia zmian", "Zmiany", "Rewizje", "Logi", "Aktywność".
- **Boundary assumptions:** archive boundary table: "ChangesLog → Audit module — cross-cutting concern; any module can use it". Users spec §9: Audit receives Users' events, supplies read-only change history on the user detail and records the acting User for every module.
- **Out of scope:** the Journal, `UserJournal`, `JournalAction*`, `user_notifications` feed (Users, D-16/D-28); panel notification bell (shared channel); offer business history beyond *how* it is recorded (Offers); integration sync state itself (WooCommerce/Otomoto) except as operator-visible logs.
- **Limitations:** no V1 runtime or production data; static reading only.

## B. Module overview

Audit in V1 is not one thing but four unconnected mechanisms, none owned by a module boundary:

1. **Change log** (custom): Offer only; per-offer and per-actor tabs; field old/new; rollback; deletable (F-01..F-06).
2. **Activity log** (package): 8 models with narrow field lists; viewer "Logi" visible to one hard-coded e-mail (F-07, F-08).
3. **Offer versions** (package): snapshots with diff and restore (F-09, F-10).
4. **Technical logging**: files only, no panel view; Otomoto's run monitor is the only operator surface (F-11..F-13).

Common gaps: quiet saves and relation changes escape all histories; system actors are anonymous; nothing is pruned; nobody but one developer can browse history across records; errors cannot be seen without shell access. The Journal is Users' (out of scope).

## C. Functional inventory

| ID | Functionality | Entry points | Workflow | Verified | Confidence | Open |
|---|---|---|---|---|---|---|
| G-01 | Who can open the user view page (and so F-03 delete/rollback) | not-investigated | Users owns it | Users spec covers access; D-32 makes tab read-only |
| G-02 | Whether the offer tab's read-only mode hides Delete in practice | needs-runtime | Filament runtime behaviour | moot (D-10). Closed. |
| G-03 | Scheduled pruning of change entries | not-investigated | r01 did not read schedule | r03, r04: none scheduled. Closed. |
| G-04 | Whether a host cron runs `activitylog:clean`; production LOG_* channels; worker setup | needs-runtime | not in repo | user 2026-11-02: nothing ever cleaned the activity log; production log is one big file; host cron runs the scheduler every minute. Closed (D-01, D-02). |
| G-05 | Revert semantics of a revision restore, owner access by URL, and whether a restore pushes to Woo/Otomoto | not-investigated | vendor + offer events | r05: previous version; owner yes; Woo + Otomoto yes. Closed. |
| G-06 | Bulk price change: what it dispatches and which actor its audit rows carry | not-investigated | r03 read to ~line 100 | r05: BR-12. Closed. |
| G-07 | Other Offer writers (`OfferUpdateService`, `OfferStatusAggregate`, `OfferPriceService`) and listener registration (`DeactivateAdvertListener`, `UpdateAdvertAfterOfferChange`) | not-investigated | leads from r03, r04 | r06: services write nothing; listeners registered via OfferEventServiceProvider, Otomoto calls + file logs only. Closed. |
| G-08 | Does the non-existent `App\Providers\ChangesLogServiceProvider` in `bootstrap/providers.php` break boot? | needs-runtime | r03 | open; V1 runs in production, so it evidently does not; irrelevant to V2 |
| F-01 | Automatic change log of Offer create / update / soft delete | Offer model hooks (any save path) | W-01 | spot | high | Q-01 |
| F-02 | "Historia zmian oferty" tab on the offer view (admins) | OfferResource relation manager | n/a: display; delete covered by F-06 | — | med | G-02 |
| F-03 | "Rejestr zmian użytkownika" tab: entries made BY a user | UserResource view, relation manager | n/a: display; delete covered by F-06 | — | med | Q-03 |
| F-04 | Change entry detail (old/new per field) | slide-over View action on F-02/F-03 | n/a: display | spot | high | — |
| F-05 | Roll back an Offer to the old or new values of an entry | detail footer actions | W-02 | spot, r05 | high | Q-02 |
| F-06 | Delete change entries singly or in bulk | row/bulk Delete on F-02/F-03 | n/a: plain delete, no side effects | — | med | Q-03 |
| F-07 | Activity log recording on 8 models (fixed field lists) | package model hooks | W-03 | spot (Setting) / r03 | high | Q-05 |
| F-08 | "Logi" activity log viewer | panel plugin resource | n/a: read-only display | spot | high | Q-06 |
| F-09 | Offer versions (snapshots) | package model hooks on Offer | W-04 | r03 | med | Q-07 |
| F-10 | Offer revisions page: diff and restore | EditOffer "Revisions" → `/{record}/revisions` | W-05 | r05 | high | Q-02, Q-10 |
| F-11 | Technical file logging (laravel.log, otomoto.log) | ~75 call sites | n/a: write-only, no reader | r04 | high | Q-08 |
| F-12 | "Monitor kolejki Otomoto" with "Wyczyść log" | OtomotoQueueMonitor page | n/a: display + delete-all, owned by Otomoto (X-04) | spot | high | Q-09 |
| F-13 | Technical failures surfaced to operators (toasts, bell) | Notifier, DB notifications | n/a: transient display | r04 | med | Q-08 |

### F-01 Automatic change log of Offer

- **Purpose:** know who changed an offer, what, and when.
- **Description:** every create, update (with at least one logged field changed) and soft delete of an Offer writes one entry: Polish message, per-field old/new values, action, acting user, time. Offer is the **only** model on this mechanism.
- **Actors and permissions:** system; actor = signed-in user, else none.
- **Entry points:** model hooks; fire on every non-quiet save from any path.
- **Workflows · rules · dependencies:** W-01; BR-01..BR-05; X-01.
- **Evidence:** r01; `app/Modules/ChangesLog/Traits/HasChangesLog.php:15-34`, `Services/ChangesLogService.php:12-75`.
- **Status:** traced · **Confidence:** high (spot-checked, vendor event order checked).

### F-02 Offer change history tab

- **Description:** on the offer view page (not edit) admins see the offer's entries newest first: date, actor initials (tooltip name, link to user), message coloured by action. Search on date and message; no filters. View opens F-04.
- **Actors:** Admin, Super Admin (`isAdmin()`).
- **Evidence:** r01; `Filament/Resources/Shared/ChangesRelationManager.php:29-73`, `Clusters/Offers/Resources/OfferResource.php:79-84`.
- **Status:** mapped · **Confidence:** med (whether read-only mode hides delete: G-02).

### F-03 User change register tab

- **Description:** on the user view page, the entries that user **made** (as actor), across logged models (in practice offers): date, offer SKU + icon, offer title (primary eurocode) linking to the offer, message. Search; no filters; view, delete, bulk delete.
- **Actors:** whoever can open the user view page; no tab-level check, no change-log policy.
- **Evidence:** r01; `UserResource/RelationManagers/UserChangesRelationManager.php:18-54`, `Components/UserChangesTableSchema.php:37-59`.
- **Status:** mapped · **Confidence:** med. Users spec D-32 already decided this tab is read-only in V2.

### F-04 Change entry detail

- **Description:** "Informacje ogólne" (user, date, message) and "Zmiany": per field a label, old value (red), new value (green). Labels from a per-model label map; producer id resolved to a name; type and colour shown as raw ids; arrays as JSON or comma list.
- **Evidence:** r01; `Components/ChangesDetailsInfolistSchema.php:19-75`, `Models/Offer.php:83-125`.

### F-05 Rollback from an entry

- **Description:** "Przywróć nowsze" writes the entry's new values back to the offer; "Przywróć poprzednie" (hidden for created entries) writes the old values. Normal save, so it logs a new entry. Notification "Pomyślnie przywrócono".
- **Actors:** anyone who sees the detail, on either tab. No confirmation, no extra permission.
- **Workflows:** W-02. **Evidence:** r01; spot `ChangesDetailsInfolistSchema.php:36-42`, `ChangesLogService.php:108-123`.
- **Status:** traced · **Confidence:** high.

### F-07 Activity log recording

- **Description:** a second, package-based history records create/update/delete on 8 models, only for listed fields: Offer (price, eurocode, usage_status, rating, year, type/producer/colour name), Car (name, model), Setting (key, value), OfferType/Producer/Colour/Equipment (name, status), User (name, email). Log name "default"; causer = signed-in user, else none; old/new values kept.
- **Evidence:** r02, r03; `Models/Offer.php:229-247`, `User.php:150-154`, `Setting.php:36-40`; spot `Setting::setValue` saves through Eloquent (`Setting.php:22-34`), so settings-screen changes are logged.
- **Status:** mapped · **Confidence:** high.

### F-08 "Logi" activity log viewer

- **Description:** panel page "Logi": list newest first (log name, event badge, subject class + id, causer, properties, date), filters by date range and event, detail with old/new key-value lists. No revert, no delete (package).
- **Actors:** only the user whose e-mail equals a hard-coded developer address. Seeded `*_activitylog` permissions (Super Admin all, User `view_activitylog`, Admin none) have no effect; the policy class is probably unregistered (`inferred`).
- **Evidence:** r02, r03; spot `Filament/Utils/PanelConfig.php:86-89`.
- **Status:** mapped · **Confidence:** high.

### F-09 Offer versions

- **Description:** a third history: full snapshots of an Offer on create and update (versioned fields price, eurocode, user_description, description, usage_status, rating, without_eurocode, alt_eurocodes, year). Unlimited retention; force delete removes them. Version author = the offer's owner when set, not the editor.
- **Evidence:** r02, r03; `Models/Offer.php:125-146`, vendor `overtrue/laravel-versionable/src/Versionable.php:338-341`, `config/versionable.php:7`.
- **Status:** mapped · **Confidence:** med (snapshot contents and revert semantics from vendor reading).

### F-10 Offer revisions page

- **Description:** from the offer edit page, admins get a "Revisions" button (badge = versions − 1). The page lists versions newest first, shows a word-level diff, and offers a confirmed restore that reverts the offer to the version before the one shown, then returns to edit.
- **Actors:** button admin-only; page itself allows anyone who may edit the offer (admins and the owner) by direct URL.
- **Workflows:** W-05. **Evidence:** r02, r03; `EditOffer.php:34-35`, `OfferRevisionsPage.php:11-21`, vendor `mansoor/filament-versionable/src/RevisionsPage.php:42-120`.
- **Status:** verified (r05) · **Confidence:** high. Correction (r05 #1): the button's version-count rule is overwritten, so admins see it on single-version offers and get a 404.

### F-11 Technical file logging

- **Description:** errors and progress go only to files: `laravel.log` (default) and `otomoto.log` (Otomoto bulk and API). Areas: Otomoto bulk lifecycle and API, Otomoto auth, WordPress/Woo imports and sync, OEM sync, bulk price job, offer soft delete. No logging of auth events, permission changes or uploads. No exception reporter beyond defaults; no Sentry/Telescope/Pulse/log viewer. Several errors logged at info level.
- **Evidence:** r04 census table; `config/logging.php:21,53-131`, `bootstrap/app.php:19-21`.

### F-12 Otomoto queue monitor

- **Description:** admin page listing Otomoto bulk-action runs (action, requester, status, counts of good/unconfirmed/errored offers, chunk progress, dates), polling every 10 s; per-run offer list; no error text ("Szczegóły w otomoto.log."). "Wyczyść log" hard-deletes all runs after confirmation.
- **Evidence:** r04; spot `OtomotoQueueMonitor.php:51`. Owned by Otomoto (X-04); here as the only V1 operator log surface.

### F-13 Failures surfaced to operators

- **Description:** transient toasts on synchronous failures (WordPress, Woo, Otomoto publish), lost in queued context; bell notifications to the dispatching user for Otomoto bulk runs and Woo bulk sync (counts only). Texts send operators to a "dziennik zdarzeń" / "logi Otomoto" the panel cannot show.
- **Evidence:** r04; `Services/Notifier/Notifier.php:7-50`, `WordpressApiService.php:256`, `BulkActionTracker.php:126-195`.

### F-06 Delete change entries

- **Description:** row Delete and bulk Delete on both tabs; hard delete, no record of the deletion.
- **Evidence:** r01; relation managers above. **Confidence:** med (Offer tab effect at runtime: G-02).

## D. Business rules and domain knowledge

### Confirmed (`confirmed` / `observed`)

- **BR-01** Offer logs every own attribute except `id, sku, user_id, created_at, updated_at, deleted_at`; relations (eurocodes, equipment, media) are never captured. Default for a model without a list: all except id and timestamps. — F-01; r01 `Models/Offer.php:60-61`, `ChangesLogService.php:49-61`
- **BR-02** The actor is the signed-in user at save time; console, queue, webhook and unauthenticated saves record no actor, yet the message still reads "Użytkownik …". Deleting a user empties the actor of their entries. — F-01; r01 `ChangesLogService.php:98-100`, migration `create_changes_logs_table.php:15`
- **BR-03** An update is logged only when a logged field changed; a create is always logged (even with no fields). Messages: "Użytkownik dodał nowy rekord." / "Użytkownik wprowadził zmiany w [N] polu|polach." / "Użytkownik usunął rekord." — F-01; r01 `ChangesLogService.php:12-40`; spot vendor `Model::performUpdate` fires `updated` before `syncOriginal`
- **BR-04** Force delete of an offer deletes its whole history and logs nothing; restore is not logged. — F-01; r01 `HasChangesLog.php:25-33`
- **BR-05** Quiet saves skip the log: `EditOffer.php:167` (after equipment sync), `CreateOffer.php:87`, `BulkPriceUpdateSyncJob.php:36` (price revert), `SynchOemCodeFromWordpressJob.php:57` (oem from WordPress). — F-01; r01
- **BR-06** No retention or pruning; entries go only by manual delete or offer force delete. — F-06; r01 (schedule unchecked: G-03)

- **BR-07** Three overlapping histories exist for Offer (change log, activity log, versions), each with a different field set and a different actor rule. — F-01, F-07, F-09; r02, r03
- **BR-08** Quiet saves bypass all three histories at once (and also Offer's sync events). About 20 writers do it: bulk price change step 1, Woo/WordPress import jobs, OEM sync, offer edited/created by user actions, EditOffer, CreateOffer, Offer's own soft-delete status save. — r03
- **BR-09** Relation and pivot changes (eurocodes, equipment, media) are recorded by none of the histories. — r01, r03 `EurocodeOffer.php:12-20`
- **BR-10** Activity log retention is configured (365 days) but nothing runs the clean; change log and versions have no retention. All grow forever. — r02, r03, r04 `routes/console.php:14-24`
- **BR-12** Bulk price change: prices are first written silently; a queued job then re-applies each change as a normal save, so the history shows every price change with **no actor**, a version authored by the owner, and triggers Woo/Otomoto sync (Otomoto possibly twice). The admin who ran it is not recorded anywhere in history. — r05 #7 (`ChangePriceBulkAction.php:~84-120`, `BulkPriceUpdateSyncJob.php`)
- **BR-13** No history at all for reservations, sales, inquiries, submissions or media. Reservations are hard-deleted; turning a reservation into a sale drops the reserver; `sold_by` columns exist but are never written or read. — r06; spot `create_offer_reservations_table.php:23`, `create_offer_sales_table.php:21`, no app references
- **BR-14** No security events are recorded: sign-in, sign-out, failed sign-in, password reset, 2FA, impersonation, role/permission changes, self account deletion. — r06; spot: no auth event listeners in `app/`, `bootstrap/`
- **BR-15** Scheduled Otomoto full-data update jobs have no failure handling; failures land only in the framework's failed-jobs store, with no UI. — r06
- **BR-11** Failed queue jobs are recorded only by the framework (no `failed()` handlers, no UI); WordPress/Woo import jobs swallow exceptions, so an import chain stops silently with no retry and no failed-job record. — r04

### Inferred or questionable

### Domain terms

- **Logi** — activity log viewer (F-08). **Revisions / Rewizje** — offer versions page (F-10). **Monitor kolejki Otomoto / Wyczyść log** — F-12. **dziennik zdarzeń** — text in V1 errors pointing to `laravel.log`.
- **Historia zmian oferty** — offer's change tab (F-02). **Rejestr zmian użytkownika** — user's "changes I made" tab (F-03). **Przywróć nowsze / poprzednie** — rollback (F-05).

## E. Functional workflows

### W-01 An offer is saved

- **Trigger:** any non-quiet create, update, soft delete or force delete of an Offer.
- **Main flow:** 1. Model event fires. 2. Created: write entry with all logged attributes as old=null/new=value. Updated: diff dirty logged attributes; none → stop; else write entry with old/new per field. Deleted (soft): write entry with no fields. Force delete: delete all the offer's entries, write nothing.
- **State changes:** one new change entry (or history wiped on force delete).
- **Error scenarios:** none caught; a failing log write fails the save (no transaction observed). `inferred`.
- **Alternative paths:** quiet saves (BR-05) write nothing.
- **Evidence:** r01; spot vendor event order.

### W-02 Rollback from an entry

- **Trigger:** "Przywróć nowsze" / "Przywróć poprzednie" in F-04.
- **Preconditions:** can see the tab; entry has field changes; for "poprzednie" the entry is not a create.
- **Main flow:** 1. Collect new (or old) value of each field in the entry. 2. Update the offer with them (normal save, so W-01 logs a new entry). 3. Success notification.
- **Side effects (r05 #6):** the save fires activity log, versions, WooCommerce sync and the Otomoto listener, like any offer edit.
- **Error scenarios:** fields missing an `old` key are dropped silently; an entry without `meta_data.action` errors.
- **Evidence:** r01, r05; spot `ChangesDetailsInfolistSchema.php:36-42`.

### W-03 A model on the activity log is saved

- **Trigger:** non-quiet create/update/delete of the 8 models. **Main flow:** package compares listed fields, writes one entry (event, subject, causer, old/new). **Alternative paths:** quiet saves write nothing (BR-08). **Evidence:** r02, r03.

### W-04 Offer saved → version

- **Trigger:** non-quiet create/update of an Offer. **Main flow:** first version on create (or before first update); new snapshot on each update. **Evidence:** r02 vendor `Versionable.php:29-66`.

### W-05 Restore an offer revision

- **Trigger:** "Restore" on the revisions page. **Preconditions:** can edit the offer; > 1 version. **Main flow:** 1. confirm 2. offer reverted to the version before the one shown 3. redirect to edit. **Side effects (r05 #4, confirmed):** a normal save; if anything changed: change-log and activity entries by the restorer, a version (only if a versioned field changed, author = owner), WooCommerce sync in the request, queued Otomoto advert update when Active. **Evidence:** r02, r05.

## F. Cross-module dependencies

- **X-01** Offers → Audit: Offer is the only model on the custom change log; it supplies field labels, display transforms, title/URL/icon for links. r01
- **X-03** Catalog/dictionaries (Car, Setting, OfferType/Producer/Colour/Equipment) → Audit: recorded on the activity log only. r02
- **X-04** Otomoto → operators: bulk-action run history (F-12) and `otomoto.log`; the only persistent operator-facing technical log. r04
- **X-05** WooCommerce/WordPress → logs: import and sync failures go to `laravel.log` and transient toasts only. r04
- **X-06** Settings → Audit: settings changes are logged via the activity log (spot `Setting.php:22-34`). r03
- **X-07** Offers (reservations, sales, inquiries), Submissions → Audit: no history in V1 (BR-13); candidates to register in V2 (ADD-01). r06
- **X-08** Users (sign-in, impersonation, roles/permissions) → Audit: unrecorded in V1 (BR-14); Users spec already requires impersonation, Extra Permission, forced password change and lifecycle events to be audited. r06
- **X-02** Users ↔ Audit: actor link to the user; the user view hosts F-03. Users spec D-32: history tab read-only in V2. r01

## G. Ambiguities, contradictions and questionable behaviour

### Q-01 Change log is incomplete for system writes — `defect?`

- **Evidence:** BR-02, BR-05; r01.
- **Interpretations:** a) quiet saves deliberately hide noise (e.g. SKU, price revert) b) accidental gaps.
- **Question for the user:** must every change to a logged record reach the log, with a named system actor when no user acted?
- **Resolution:** D-07

### Q-02 Rollback with no confirmation or permission — `defect?`

- **Evidence:** F-05; spot `ChangesDetailsInfolistSchema.php:36-42`.
- **Question for the user:** keep rollback at all? If so, who may use it, with confirmation, and only for the latest entry or any?
- **Resolution:** D-11

### Q-03 Audit entries can be deleted — `defect?`

- **Evidence:** F-06; r01. Users D-32 already made the user tab read-only.
- **Question for the user:** are audit entries immutable in V2 (only retention removes them)?
- **Resolution:** D-10

### Q-04 Offer title can crash the user tab — `defect?`

- **Evidence:** r01 `Models/Offer.php:63-81` `getLoggableTitle(): string` returns null when no primary eurocode and not `without_eurocode`. Also detail view has no null check on a missing subject.
- **Resolution:** D-18 (informs requirement: subject title/link must tolerate a missing subject)

### Q-05 Three histories with different coverage — `decide`

- **Evidence:** BR-07, BR-09; r01–r03.
- **Question for the user:** one change history in V2 for every module that registers, replacing all three? Which records and which fields must be covered?
- **Resolution:** D-06

### Q-06 Activity log readable by one hard-coded e-mail — `defect?`

- **Evidence:** F-08; spot `PanelConfig.php:86-89`; r05 #8 confirms the permission-based policy is never registered.
- **Question for the user:** who may read the audit log in V2 (Super Admin only? Admins? a Permission)?
- **Resolution:** D-12

### Q-07 Version author is the owner, not the editor — `defect?`

- **Evidence:** F-09; r02 vendor `Versionable.php:338-341`.
- **Resolution:** D-06 (moot) (moot if versions are replaced by the change history)

### Q-08 Technical errors are invisible from the panel — `decide`

- **Evidence:** F-11, F-13, BR-11; r04. Messages point to a "dziennik zdarzeń" that does not exist in the panel. V1 incident report (`SECURITY_INCIDENT_2026-10.md`): entry vector "niepotwierdzony logami".
- **Question for the user:** what should the V2 log page show (application errors, integration runs, failed jobs, security events), to whom, and with what retention?
- **Resolution:** D-02, D-25, D-28..D-31

### Q-09 "Wyczyść log" destroys run history — `defect?`

- **Evidence:** F-12; spot `OtomotoQueueMonitor.php:51`.
- **Question for the user:** replace manual wipe with retention? (Otomoto-owned page; decision may be handed to Otomoto.)
- **Resolution:** D-24

### Q-10 Revisions page reachable by offer owners — `defect?`

- **Evidence:** r05 #2: button admin-only, page authorised by "can edit offer" (admins and owner).
- **Question for the user:** may an offer owner see and restore their offer's history?
- **Resolution:** D-11, D-12

### Q-11 Bulk actions lose their actor — `defect?`

- **Evidence:** BR-12; r05 #7.
- **Question for the user:** must work started by a user and finished in the background be recorded under that user (and the bulk action as one event)?
- **Resolution:** D-08, D-09

### Q-12 Users spec asks Audit for events that are not data changes — contradiction (raised by D-14)

- **Evidence:** Users spec §9 and D-22, D-46: impersonation start/end, "forced" password change, and "record the acting User for every module" are to be audited. D-14 limits the Change history to data changes.
- **Interpretations:** a) impersonation start/end go to a security event log (ADD-04) b) they are not recorded anywhere; Users spec is amended.
- **Resolution:** D-23

## H. Missing information and gaps

| ID | What is unknown | Kind | Why | Resolution |
|---|---|---|---|---|

## I. Potential improvements

- **IMP-01** Log relation changes (eurocodes, equipment, media) — BR-01 → capture them; decision: D-07, D-14
- **IMP-02** Show referenced records by name (type, colour as raw ids today) — F-04 → resolve all references; decision: D-14
- **IMP-04** Read access by Permission instead of a hard-coded e-mail — Q-06; decision: D-12
- **IMP-05** Real retention (scheduled) for every log kind instead of growth or manual wipes — BR-10, Q-09; decision: D-01, D-13 (change history); technical log: D-30
- **IMP-06** Errors logged at their real level; integration failures recorded with reason, not only counts — F-11, F-12; decision: D-25
- **IMP-07** Audit entries immutable from the UI — Q-03; decision: D-10
- **IMP-03** Distinguish system actors (job, command, integration) — BR-02 → record actor kind and name; decision: D-08

## J. Additional functionality to implement

### ADD-01 Modules register into the change log through Audit's interface

- **Description:** each module declares which of its records and fields are audited, their labels and how values (references, lists, money) are shown, and how to name and link the subject. Audit records every change of those records, including relation changes, with a user or system actor.
- **Value:** one history instead of three (Q-05); new modules get history without new code in Audit.
- **Candidates (r06):** reservations, sales, inquiries, submissions, settings, dictionaries, users.
- **Kind:** extends F-01, F-07, F-09 · **Source:** user (brief) · **Discussed:** yes (2026-11-02) · **Decision:** D-03, D-33

### ADD-02 Central audit log page

- **Description:** one panel page across all modules: filter by module/record type, record, actor (user or system), action, date range, field; detail with old/new; links to the subject; export. Per-record history tabs reuse it. *(Superseded in part by D-20: no field filter, no export.)*
- **Kind:** extends F-02, F-03, F-08 · **Source:** user (brief) · **Decision:** D-20

### ADD-03 Technical log page

- **Description:** operators see application errors and warnings, integration runs and their failures with reason, and failed queue jobs (with retry?), filtered by area, level and date.
- **Value:** V1 error messages point to a log the panel cannot show (Q-08).
- **Kind:** entirely new · **Source:** user (brief), r04 · **Decision:** D-02, D-28..D-31

### ADD-04 Security event log

- *(Narrowed by D-27 to sign-in, failed sign-in, sign-out, password reset, impersonation; 2FA and uploads not selected; permission grants are Change entries.)*
- **Description:** (BR-14) record sign-ins, failed sign-ins, password resets, 2FA changes, impersonation (Users D-22), permission grants (Users D-21), file uploads.
- **Value:** the October 2026 incident's entry vector could not be confirmed from logs (`SECURITY_INCIDENT_2026-10.md`).
- **Kind:** entirely new · **Source:** manager · **Decision:** D-27

## K. Investigation log

| Report | Mission | Scope / question | Status | Key results | Limits | Follow-ups | Checked |
|---|---|---|---|---|---|---|---|
| r01 | local | Custom ChangesLog module: what is recorded, when, how shown | returned | Offer only; rollback; deletable; quiet-save gaps | static; schedule unread | G-02, G-03 | spot: F-05, BR-03 (vendor) |
| r02 | local | Spatie activity log + versionable: what is recorded, who sees it | returned | 8 models; e-mail gate; versions + restore | vendor semantics read, not run | G-05 | spot: F-08 gate |
| r03 | cross-repo | Audit/history vocabulary across V1 + entry-point census | returned | census; ~20 quiet writers; User not on change log | some write services unread | G-06, G-07 | spot: X-06 setValue |
| r04 | cross-repo | Technical logging: Log:: calls, channels, failed jobs, operator-visible logs | returned | file-only logs; no viewer; swallowed import errors; queue monitor | prod env unknown | G-04, G-07 | spot: F-12 wipe |
| r05 | verify | Claims on F-10 restore, F-05 sync side effects, bulk price actor, ActivityPolicy | returned (inline; saved by manager) | 3 confirmed, 3 partly→corrections, policy unused | vendor read, not run | — | second source for F-05, F-08, F-10, BR-12 |
| r07 | spec-audit | functional-spec.md faithful, traceable, implementation-free? | returned | counts correct; 0 unapproved ADD/IMP; ~8 undecided behaviours; editorial fixes | spec vs discovery only | round 4 + addendum | — |
| r06 | cross-repo | Saturation: other writers, listeners, auth/security events, scheduled jobs, actor columns | returned | no new audit F-; BR-13..15 (absences) | journal-action actor not re-traced | — | spot: BR-13 sold_by, BR-14 |

## L. Completeness assessment

- **Exit criteria:**
  1 ✓ every census row (r03, r04, r06) maps to F-01..F-13, or is a writer of another module recorded as BR-08/BR-13/BR-14, or is unreachable (`ApiController` dd, nonexistent provider G-08).
  2 ✓ every F- has entry points and tags; workflows W-01..W-05, display-only items say why.
  3 ✓ matched-not-examined: OfferColor/Producer field lists (covered by r02), other Fetch*/Otomoto jobs (same pattern as examined ones, dismissed), FullListOffer/MyOffers (bulk host pages, dismissed), Woo/Otomoto internals (other modules), ShieldSeeder_old2 (dead).
  4 ✓ leads: ListUsersNotifications → Users; Woo/Otomoto on restore → r05; listeners → r06; write services → r06; SECURITY_INCIDENT read by manager (feeds ADD-04); duplicated Shield RoleResource → Users/Settings, dismissed.
  5 ✓ archive "User uses HasChangesLog" refuted by r01+r03; no report-vs-report conflict.
  6 ✓ second sources: F-05 (spot+r05), F-08 (spot+r03+r05), F-10 (r02+r05), BR-05/08 (r01+r03), BR-10 (r02+r03+r04), BR-12 (r05+r03), F-12 (spot+r04), BR-13/14 (spot+r06).
  7 ✓ wave 2 found no new F- items or audit entry points, only absences (BR-13..15).
  8 ✓ I and J filled.
- **Mapped:** 13 F-, 15 BR-, 5 W-, 8 X-, 11 Q-, 8 G- (6 closed), 7 IMP-, 4 ADD-.
- **Verified:** see criterion 6.
- **Still uncertain:** Q-01..Q-11 (all user decisions); G-04 (prod cron/log env), G-08 (irrelevant to V2).
- **Weak coverage:** production behaviour (log channels, worker, whether `activitylog:clean` runs on the host) — static reading only; Polish labels of package pages.
- **Ready because:** V1 behaviour is fully mapped and the remaining questions are about the V2 target, which only the user can decide.

## M. Decision log

- **D-01** (2026-11-02) re F-07, BR-10, G-04, IMP-05 — Nothing ever cleaned the activity log in production. V2 builds its own audit history (no third-party activity package) and it **must** have scheduled cleanup. Class: correct. Rationale: user — the history grew unbounded; the scheduler already runs every minute on production.
- **D-02** (2026-11-02) re F-11, Q-08, ADD-03 — Technical logs go to daily files instead of one ever-growing file, and the panel gets an interface to read them. Class: new (ADD-03 accepted in principle; details grilled in Phase 7). Rationale: user — production has one huge log file and errors are invisible from the panel.
- **D-03** (2026-11-02) re Q-05, ADD-01, X-01..X-08 — Audit becomes a core-like module that other modules register into through its ports: a module (e.g. a future "warsztaty" module) declares what is auditable — records, fields, relations — and Audit records and shows it. Class: new (ADD-01 accepted in principle; the registration contract is grilled). Rationale: user — V1 audit is scattered and broken; V2 needs one universal, solid mechanism.
- **D-04** (2026-11-02) re process — Grilling and the spec are written on the current branch for now; the work moves to its own branch once the Users worker finishes. Class: n/a (process).
- **D-05** (2026-11-02) re F-12, X-04, scope — Audit owns the Change history (all modules) and the Technical log page (D-02); a security event log would also live here if approved (ADD-04, Phase 7). The Journal stays with Users; the Otomoto queue monitor stays with Otomoto and only writes to the Technical log; the notification bell is not Audit's. Class: correct.
- **D-06** (2026-11-02) re Q-05, F-01, F-07, F-09, BR-07 — One Change history replaces the custom change log, the activity log and offer versions. An entry keeps old and new values of the changed fields; no full-record snapshots. Class: correct.
- **D-07** (2026-11-02) re Q-01, BR-05, BR-08, BR-09 — Everything a module registered as auditable and did not exclude is recorded, whatever the path (UI, background, import, integration, bulk) — including relation changes. Modules exclude fields, never paths. Class: correct. Rationale: user — in V1 it simply worked badly.
- **D-08** (2026-11-02) re BR-02, Q-11, IMP-03 — Every entry has an Actor: the User when a User did it (also when their work finished in the background), otherwise the System with its named reason (scheduled or integration work). No extra annotations. Matches ADR 0004 and `App\Core\Domain\Actor` (verified). Class: correct. Rationale: user — keep it simple; it must work well, not shine.
- **D-09** (2026-11-02) re Q-11, ADD (grouping) — No grouping of entries into operations; a bulk action produces ordinary per-record entries. Class: exclude. Rationale: user — history is read per record, where a price change is just a price change; grouping complicates registration.
- **D-10** (2026-11-02) re Q-03, IMP-07, F-06 — Change entries cannot be edited or deleted from the panel; only expired entries are removed. Class: correct.
- **D-11** (2026-11-02) re Q-02, Q-10, F-05, F-10 — Restore stays, but only for admins on the record types where a module enables it, guarded by a Permission. How data is stored must make restore straightforward; side effects (events, syncs) to be settled. V1's unguarded rollback and owner-reachable revisions page are dropped. Class: correct. Details in round 2.
- **D-12** (2026-11-02) re Q-06, Q-10, F-02, F-03, F-08 — Per-record history is a separate section on the record's page, visible to Admin and above (by Permission). The central audit page and the Technical log page are Super Admin by default (by Permission). Offer owners do not see history. V1's e-mail-gated plugin page is dropped. Class: correct. Rationale: user — V1's page was bad, mixed with a plugin.
- **D-13** (2026-11-02) re BR-10, IMP-05, D-01 — One retention period for the whole Change history, set in Settings, default 365 days; expired entries are removed by a scheduled cleanup. Class: correct. Rationale: user — fastest and simplest.
- **D-14** (2026-11-02) re Q-05, IMP-01, IMP-02, F-04 — A Change entry is only a data change: created, changed, deleted, brought back. No named business actions ("offer accepted" is simply `status: X → Y`). Relation changes are tracked. A module can register a resolver for a field so a reference (e.g. `producer_id`) is shown by its readable name, as V1 did for producers; the same for every relation. Impersonation is not a data change and does not belong in the Change history (→ Q-12). Class: correct. Rationale: user — business rules are not the log's job; the log cares that the status changed.
- **D-15** (2026-11-02) re D-08 — During impersonation the Actor is the real person (the impersonator). Class: new.
- **D-16** (2026-11-02) re D-11 (supersedes its "admins, by Permission" part), Q-02, F-05 — Restore is literal: it writes back the old values of exactly the fields in the chosen entry (only `status` if the entry changed only `status`). No snapshots, no conflict checks against later changes, no other fields. Only a Super Admin may restore for now; restore is offered only on record types where the module enables it. Granting it to Admins as an Extra Permission is a later Users topic (each module would need to offer extra Permissions). The restore runs as an ordinary change through the owning module and is itself recorded. Class: correct. Rationale: user — restore exactly what the user picked; do not overengineer.
- **D-17** (2026-11-02) re proposal for sensitive fields — No special "changed but value hidden" handling. Sensitive fields (passwords, secrets) are simply not registered as auditable. Class: exclude. Rationale: user — tricks to tell a password change from other actions are overengineering.
- **D-18** (2026-11-02) re BR-04, Q-04 — A record's history outlives the record; it goes only by retention. The "deleted" entry keeps the record's last title so it stays recognisable without a link. Bringing a record back from the bin is recorded. Class: correct.
- **D-19** (2026-11-02) re F-02, F-04 — The per-record history is its own tab/section on the record page, a plain table (date, Actor, what happened) with filters by Actor and kind; the changed fields (old → new) are shown in a separate detail view, not in the table. A custom layout of changes may come later. Class: correct. Rationale: user — changed fields do not fit in a table.
- **D-20** (2026-11-02) re ADD-02, F-08 — Central audit page: one table of all entries; filters by module / record type, record, Actor (User or System), kind and date range; entry detail and link to the record; no export for now. Class: new.
- **D-21** (2026-11-02) re F-03 — The "Rejestr zmian użytkownika" (changes made BY a user) is dropped; it was unused. The user page shows the user's own history like any record. Class: exclude.
- **D-22** (2026-11-02) re X-01..X-07 — The spec does not list what each module audits; it gives one example (Offers) only. Each module decides in its own discovery. Class: correct. Rationale: user — reservations and sales will probably not be audited.
- **D-23** (2026-11-02) re Q-12, ADD-04, X-08 — Impersonation start and end go to the Technical log, area "bezpieczeństwo". The "password change required" marker is an ordinary audited field of the User, so setting/clearing it is in the Change history. Users spec §9 is amended to ask Audit only for data changes. Class: correct.
- **D-24** (2026-11-02) re Q-09, F-12, X-04 — Otomoto will not exist in V2; its queue monitor, `otomoto.log` and run history are dropped with it. Class: exclude.
- **D-25** (2026-11-02) re IMP-06, BR-11, F-13 — Rules for every module writing to the Technical log: an error is logged as an error with context (what, which record, the external response); no error is swallowed without a trace; a user-facing "details in the log" message is allowed only where the log is readable in the panel. Class: correct.
- **D-26** (2026-11-02) re Phase 6 — User confirmed the shared understanding of the existing functionality (summary in round 3 Q4). Class: n/a.
- **D-27** (2026-11-02) re ADD-04, BR-14 — Security events are written to the Technical log in their own daily file "bezpieczeństwo": sign-in, failed sign-in (with IP), sign-out, password reset, impersonation start and end. No separate security module or store. Class: new. Rationale: user — simple and neat.
- **D-28** (2026-11-02) re ADD-03 areas — Technical log areas are separate daily log channels, each added in configuration by whoever needs it; Audit does not register them. The log page reads whatever daily log files exist and lets the reader pick the channel. Expected channels: application (default), WooCommerce/WordPress, security, queue. Class: new. Rationale: user — a module sets up its own channel in config; Audit only reads.
- **D-29** (2026-11-02) re ADD-03 page — Technical log page: pick channel and day; table kept lean (time, level, short message); entry detail with full message, context and stack trace; filter by level; text search; download the day's file; no deleting from the panel. Super Admin by default (D-12). Class: new.
- **D-30** (2026-11-02) re ADD-03 retention — Daily log files are kept 14 days, then removed automatically; fixed in configuration, no panel setting. Class: new.
- **D-31** (2026-11-02) re BR-11, BR-15 — "Nieudane zadania" tab on the log page: job name, time, error; retry and remove actions; Super Admin only. Class: new.
- **D-32** (2026-11-02) re alerts — No bell alerts on errors or failed jobs for now. Class: pending (not now).
- **D-33** (2026-11-02) re ADD-01 contract (round 2 Q4) — Per auditable record type a module declares: type name; record title and link; audited fields = all except those it excludes; field labels; how values are shown (references and relations resolved to readable names, enums by label, amounts, dates, lists); which relations are tracked as added/removed; whether Restore is offered and for which fields. Named actions (D-14) and sensitive-field masking (D-17) are not part of it. Class: new. Rationale: user accepted the contract except those two parts.
- **D-34** (2026-11-02) re Phase 7 — Additions selected: ADD-01 (D-03, D-33), ADD-02 (D-20), ADD-03 (D-02, D-28..D-31), ADD-04 (D-27). No other ideas raised. Class: n/a.
- **D-35** (2026-11-02) re r07 1.5, D-14 — References are resolved to readable names **when displayed**, as V1 did; entries keep the raw values. No readable copy is stored at recording time. Class: correct. Rationale: user rejected storing readable values ("na chuj").
- **D-36** (2026-11-02) re r07 1.2 — Restore asks for confirmation in a simple dialog listing the fields and values to be written ("Przywrócić: status → Aktywna, cena → 1200 zł?"), with no comparison to the current state. Class: correct.
- **D-37** (2026-11-02) re Phase 9 — V1 history is not migrated: `changes_logs`, `activity_log` and `versions` are all dropped; the V2 Change history starts empty on migration day. V1 data stays in the V1 database backup. Class: exclude. Rationale: user — start clean.
- **D-38** (2026-11-02) re r07 1.3, 1.4, 1.6, 1.7, 1.8, 1.9, 5.1, 5.2, 5.3, 5.6, 5.4 — Accepted defaults: (1) no Restore on utworzono/usunięto entries or on deleted records; usunięto holds no fields, only the last title; (2) a Restore entry is marked "przywrócono z wpisu z {data}"; (3) only fields are restorable, not relations; an entry with only relation changes has no Restore; (4) Permissions "historia rekordu", "dziennik audytu", "logi techniczne"; Restore is a hard Super Admin rule, no own Permission until extra Permissions per module return; labels "Historia zmian", "Dziennik audytu", "Logi techniczne", "Przywróć"; (5) cleanup of history and log files nightly; a failed run is logged and repeated next night; 365 days count from the entry's time; (6) log page opens on channel aplikacja, today, newest first, paged; PSR levels debug..emergency; message shortened to one line; search covers message and context; empty day shows "brak wpisów"; (7) every security entry has who, time, IP; password reset logged on request and on completion; (8) removing a failed job asks for confirmation, retry does not; (9) old entries after registration changes — the "as recorded" part is superseded by D-35 and re-asked in round 5; the record-type filter lists currently registered types. Class: correct/new (details of D-11, D-12, D-13, D-16, D-27, D-29, D-30, D-31).
- **D-39** (2026-11-02) re r07 1.1, D-07 — A change and its Change entry are saved together or not at all: if the entry cannot be stored, the operation fails. The V2 command bus already wraps every command in one transaction (`app/Core/Infrastructure/Bus/WrapCommandInTransaction.php`), so recording in that transaction gives this for free. Class: correct.
- **D-40** (2026-11-02) re D-35, D-38(9) — Only the id is stored; the name is shown when the entry's details are displayed. Records are soft-deleted, so a deleted referenced record still shows its name, marked as removed; only a record gone entirely shows the bare id, marked as removed. Field labels follow the module's current registration; a field no longer registered is still shown under its technical name (manager's consequence of D-35, user answered on values). Class: correct.

