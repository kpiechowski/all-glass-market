# Audit — discovery

<!-- Working document for the module-discovery skill. Only the manager writes it.
     Item ids are stable and never reused. Every finding cites a report (rNN) and its V1 evidence.
     Tags: confirmed · observed · inferred · ambiguous · unknown · defect? · decide -->

## A. Metadata

- **Status:** ready-for-grilling
- **Next actions:** Phase 5 gate passed. Await user's go, then Phase 6 grilling (read grilling.md). Suggested order: Q-05/ADD-01 (one history + registration) → Q-01/Q-11 (completeness, actors) → Q-02/Q-10 (rollback/restore) → Q-03/IMP-07 (immutability) → Q-06 (who reads) → BR-10/IMP-05 (retention) → Q-08/ADD-03 (log page) → ADD-04 → Q-09 (hand to Otomoto).
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
| G-02 | Whether the offer tab's read-only mode hides Delete in practice | needs-runtime | Filament runtime behaviour | open |
| G-03 | Scheduled pruning of change entries | not-investigated | r01 did not read schedule | r03, r04: none scheduled. Closed. |
| G-04 | Whether a host cron runs `activitylog:clean`; production LOG_* channels; worker setup | needs-runtime | not in repo | open |
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
- **Resolution:** open

### Q-02 Rollback with no confirmation or permission — `defect?`

- **Evidence:** F-05; spot `ChangesDetailsInfolistSchema.php:36-42`.
- **Question for the user:** keep rollback at all? If so, who may use it, with confirmation, and only for the latest entry or any?
- **Resolution:** open

### Q-03 Audit entries can be deleted — `defect?`

- **Evidence:** F-06; r01. Users D-32 already made the user tab read-only.
- **Question for the user:** are audit entries immutable in V2 (only retention removes them)?
- **Resolution:** open

### Q-04 Offer title can crash the user tab — `defect?`

- **Evidence:** r01 `Models/Offer.php:63-81` `getLoggableTitle(): string` returns null when no primary eurocode and not `without_eurocode`. Also detail view has no null check on a missing subject.
- **Resolution:** open (informs requirement: subject title/link must tolerate a missing subject)

### Q-05 Three histories with different coverage — `decide`

- **Evidence:** BR-07, BR-09; r01–r03.
- **Question for the user:** one change history in V2 for every module that registers, replacing all three? Which records and which fields must be covered?
- **Resolution:** open

### Q-06 Activity log readable by one hard-coded e-mail — `defect?`

- **Evidence:** F-08; spot `PanelConfig.php:86-89`; r05 #8 confirms the permission-based policy is never registered.
- **Question for the user:** who may read the audit log in V2 (Super Admin only? Admins? a Permission)?
- **Resolution:** open

### Q-07 Version author is the owner, not the editor — `defect?`

- **Evidence:** F-09; r02 vendor `Versionable.php:338-341`.
- **Resolution:** open (moot if versions are replaced by the change history)

### Q-08 Technical errors are invisible from the panel — `decide`

- **Evidence:** F-11, F-13, BR-11; r04. Messages point to a "dziennik zdarzeń" that does not exist in the panel. V1 incident report (`SECURITY_INCIDENT_2026-10.md`): entry vector "niepotwierdzony logami".
- **Question for the user:** what should the V2 log page show (application errors, integration runs, failed jobs, security events), to whom, and with what retention?
- **Resolution:** open

### Q-09 "Wyczyść log" destroys run history — `defect?`

- **Evidence:** F-12; spot `OtomotoQueueMonitor.php:51`.
- **Question for the user:** replace manual wipe with retention? (Otomoto-owned page; decision may be handed to Otomoto.)
- **Resolution:** open

### Q-10 Revisions page reachable by offer owners — `defect?`

- **Evidence:** r05 #2: button admin-only, page authorised by "can edit offer" (admins and owner).
- **Question for the user:** may an offer owner see and restore their offer's history?
- **Resolution:** open

### Q-11 Bulk actions lose their actor — `defect?`

- **Evidence:** BR-12; r05 #7.
- **Question for the user:** must work started by a user and finished in the background be recorded under that user (and the bulk action as one event)?
- **Resolution:** open

## H. Missing information and gaps

| ID | What is unknown | Kind | Why | Resolution |
|---|---|---|---|---|

## I. Potential improvements

- **IMP-01** Log relation changes (eurocodes, equipment, media) — BR-01 → capture them; decision: open
- **IMP-02** Show referenced records by name (type, colour as raw ids today) — F-04 → resolve all references; decision: open
- **IMP-04** Read access by Permission instead of a hard-coded e-mail — Q-06; decision: open
- **IMP-05** Real retention (scheduled) for every log kind instead of growth or manual wipes — BR-10, Q-09; decision: open
- **IMP-06** Errors logged at their real level; integration failures recorded with reason, not only counts — F-11, F-12; decision: open
- **IMP-07** Audit entries immutable from the UI — Q-03; decision: open
- **IMP-03** Distinguish system actors (job, command, integration) — BR-02 → record actor kind and name; decision: open

## J. Additional functionality to implement

### ADD-01 Modules register into the change log through Audit's interface

- **Description:** each module declares which of its records and fields are audited, their labels and how values (references, lists, money) are shown, and how to name and link the subject. Audit records every change of those records, including relation changes, with a user or system actor.
- **Value:** one history instead of three (Q-05); new modules get history without new code in Audit.
- **Candidates (r06):** reservations, sales, inquiries, submissions, settings, dictionaries, users.
- **Kind:** extends F-01, F-07, F-09 · **Source:** user (brief) · **Discussed:** no · **Decision:** open

### ADD-02 Central audit log page

- **Description:** one panel page across all modules: filter by module/record type, record, actor (user or system), action, date range, field; detail with old/new; links to the subject; export. Per-record history tabs reuse it.
- **Kind:** extends F-02, F-03, F-08 · **Source:** user (brief) · **Decision:** open

### ADD-03 Technical log page

- **Description:** operators see application errors and warnings, integration runs and their failures with reason, and failed queue jobs (with retry?), filtered by area, level and date.
- **Value:** V1 error messages point to a log the panel cannot show (Q-08).
- **Kind:** entirely new · **Source:** user (brief), r04 · **Decision:** open

### ADD-04 Security event log

- **Description:** (BR-14) record sign-ins, failed sign-ins, password resets, 2FA changes, impersonation (Users D-22), permission grants (Users D-21), file uploads.
- **Value:** the October 2026 incident's entry vector could not be confirmed from logs (`SECURITY_INCIDENT_2026-10.md`).
- **Kind:** entirely new · **Source:** manager · **Decision:** open

## K. Investigation log

| Report | Mission | Scope / question | Status | Key results | Limits | Follow-ups | Checked |
|---|---|---|---|---|---|---|---|
| r01 | local | Custom ChangesLog module: what is recorded, when, how shown | returned | Offer only; rollback; deletable; quiet-save gaps | static; schedule unread | G-02, G-03 | spot: F-05, BR-03 (vendor) |
| r02 | local | Spatie activity log + versionable: what is recorded, who sees it | returned | 8 models; e-mail gate; versions + restore | vendor semantics read, not run | G-05 | spot: F-08 gate |
| r03 | cross-repo | Audit/history vocabulary across V1 + entry-point census | returned | census; ~20 quiet writers; User not on change log | some write services unread | G-06, G-07 | spot: X-06 setValue |
| r04 | cross-repo | Technical logging: Log:: calls, channels, failed jobs, operator-visible logs | returned | file-only logs; no viewer; swallowed import errors; queue monitor | prod env unknown | G-04, G-07 | spot: F-12 wipe |
| r05 | verify | Claims on F-10 restore, F-05 sync side effects, bulk price actor, ActivityPolicy | returned (inline; saved by manager) | 3 confirmed, 3 partly→corrections, policy unused | vendor read, not run | — | second source for F-05, F-08, F-10, BR-12 |
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
