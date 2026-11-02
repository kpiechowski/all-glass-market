# r06 — cross-repo: Saturation check for audit-relevant write paths, events and log surfaces

Question: Saturation check — are there write paths, events or log/notification surfaces relevant to audit that earlier missions missed? · V1 commit: 511ce3b

## Summary
- Nothing new on the Offer write side (a): OfferUpdateService, OfferStatusAggregate and OfferPriceService write nothing. The only unaudited paths are the already-known saveQuietly writers plus one new one: ViewSubmission and the SubmissionResource bulk action use saveQuietly on Submission, which has no history anyway.
- Reservations, sales, inquiries, submissions and media have NO history. Reservations are hard-deleted, so the reserver's identity is lost when a reservation is converted to a sale or removed. Sales carry no user. `sold_by` columns exist but are never written.
- Listeners (b) are registered via OfferEventServiceProvider (listed in bootstrap/providers.php). They only call Otomoto and write file logs. Nothing writes history.
- Auth/security (c): nothing is recorded anywhere (no listeners, no last_login). Impersonation, role/permission sync, 2FA and password reset are all unrecorded.
- Scheduled job failure (d): FullDataAdvertUpdateJob has no failure handling. Failures go to failed_jobs (DB, no UI) and the actions' Filament Notifications are sent to no one in a queue context.
- (e): no created_by/updated_by columns exist. Actor columns: reservations.user_id, submissions.user_id, changes_logs.user_id, activity_log.causer. Details below.
- One defect?: bootstrap/providers.php lists a nonexistent class `App\Providers\ChangesLogServiceProvider`.

## Findings

### (a) Offer write services: the three named services do not write
- Tag: confirmed · Impact: normal
- What happens: OfferUpdateService::handleUsersPublishedOffer only chunks a user's Woo-synced offers into OfferUpdateOffersChunk jobs, which call Woo `updateProduct` (an external push, no local write). OfferStatusAggregate::canOfferBeAccepted is a pure predicate (status in Pending/Rejected), used by AcceptOffersBulkAction. OfferPriceService::getAdjustedOfferPrice only computes gross price (x1.23 unless company account / no owner).
- Evidence: app/Services/Offer/OfferUpdateService.php:11-18; app/Jobs/Offer/OfferUpdateOffersChunk.php:25-28; app/Services/Offer/Aggregates/OfferStatusAggregate.php:11-19; app/Services/Offer/OfferPriceService.php:12-19; caller app/Filament/Pages/ProfilePage.php:123 (handleUsersPublishedOffer); app/Filament/.../AcceptOffersBulkAction.php:33.

### (a) Status-changing journal actions use normal save(), so they ARE audited by Offer hooks
- Tag: confirmed · Impact: high (state)
- What happens: OfferFullyReservedAction (status Reserved), OfferFullySoldAction (Sold + sold_date, then OfferSoldEvent), OfferArchivedAction (Archived, then OfferArchivedEvent) all call `$record->save()`, so the Offer `updated` hook, activity log and versionable fire. Un-reserve (StockSaleTab, UserReservationSection) also does `$offer->status = Active; $offer->save()`. Whether these save() calls create ChangesLog rows is covered by r01-r04 (hooks); not re-verified here.
- Evidence: app/Filament/Actions/Journal/OfferFullyReservedAction.php (processAction), OfferFullySoldAction.php, OfferArchivedAction.php; StockSaleTab.php:112-117; UserReservationSection.php:57-62.

### (a) New quiet writer: Submission is_read via saveQuietly
- Tag: confirmed · Impact: normal
- What happens: Opening a submission (ViewSubmission) and the "mark as read" bulk action set `is_read=true` with saveQuietly. Submission has no activity log or ChangesLog anyway, so "who read it" is not recorded at all (no reader column).
- Evidence: app/Filament/Resources/SubmissionResource/Pages/ViewSubmission.php:79; app/Filament/Resources/SubmissionResource.php:168; database/migrations/2025_09_06_55555_add_read_status_to_submission.php. (Submission deletion at ViewSubmission.php:491 `$record->delete()` is also unlogged.)

### (a) Other writers of Offer/Eurocode found beyond the known list: none new
- Tag: confirmed · Impact: normal
- What happens: grep for saveQuietly / updateQuietly / DB::table / insert / upsert / withoutEvents / withoutLogging / disableLogging finds only the known writers (BulkPriceUpdateSyncJob:36, FetchWooProducts:166, SynchOemCodeFromWordpressJob:57, MigrateOemCodesToJsonCommand:48 on Eurocode, ChangePriceBulkAction:84, CreateOffer:87, EditOffer:167, OfferCreated/EditedByUserAction, Offer.php:172) plus taxonomy/Wordpress fetch jobs (FetchWordpressColors:116, Producers:115, Equipment:114, Cars:116) and PublishAdvertAction:69 (OtomotoOffer). DB::table appears only in read queries (Car.php:39,49; CarResource.php:54). No withoutEvents/disableLogging anywhere. The Woo import creates Offers with normal `create`, then saveQuietly for sku/created_at.
- Evidence: grep results listed in ledger; app/Models/Car.php:39-49 (select only).

### (a) Reservations, sales, inquiries, submissions, media: no history of their own
- Tag: confirmed · Impact: high (state)
- What happens:
  - OfferReservation: plain Model, no SoftDeletes/LogsActivity. Created by OfferReservationChangeAction (user_id = Auth user, label), AcceptInquiryAction (label "Rezerwacja z zapytania: ..."), and Woo import (user_id = Setting `woo-offers-default-user` or 1, label "Status rezerwacji z wordpress"). Hard-deleted in "Usuń rezerwacje" / "Sprzedaj zarezerowane" / "Oznacz jako sprzedane" (record->delete()) and force-deleted via bulk actions on offers. The reserver is shown in the offer view (label "Dodał") only while the row exists.
  - OfferSale: plain Model, no user column used. Created by OfferStockChangeAction (offer_id, quantity, sold_at) and Woo import (label "Status sprzedaży z wordpress"). Converting a reservation to a sale deletes the reservation and creates a sale without the reserver, so "who bought/reserved" is lost.
  - Migrations declare `sold_by` string columns on offer_sales and offer_reservations; no code reads or writes them.
  - OfferInquiry: SoftDeletes, status enum (Delivered/Accepted/Rejected); status changed by save() with no log; user_id = the offer's owner (recipient), not the admin who sent it. Accept/reject notify admins via DB notification (notifyNow), which is a transient notice, not a history.
  - Submission: acts as a user-edit/creation snapshot (title, user_id nullOnDelete, offer_id cascade, original_data JSON, message). Created by OfferCreatedByUserAction:172, OfferEditedByUserAction:126, ActivateFromDraftAction:46. It is the only business table with an actor + snapshot, visible in SubmissionResource, but it is a review inbox, not an audit trail (no reviewer recorded; deleting it removes the record). It cascades on Offer hard delete.
  - Media (spatie media library): no history; forceDeleted Offer clears media collection (Offer.php:178-180).
- Evidence: app/Models/OfferReservation.php, OfferSale.php, OfferInquiry.php; app/Filament/Actions/Stock/OfferReservationChangeAction.php:28-33; OfferStockChangeAction.php:32-36; app/Services/ApiService/Jobs/FetchWooProducts.php:138-153; StockSaleTab.php:101-118; UserReservationSection.php:50-62; AcceptInquiryAction.php (status set + save, DB notification to admins); database/migrations/2025_01_26_5555235_create_offer_sales_table.php:21, ..._reservations_table.php:23 (`sold_by`); grep `sold_by` in app/resources returns nothing; database/migrations/2024_11_10_145443_create_submissions_table.php.

### (b) Listeners are registered and write no history
- Tag: confirmed · Impact: normal
- What happens: OfferEventServiceProvider (an Illuminate EventServiceProvider subclass, listed in bootstrap/providers.php) maps OfferDeletedEvent/OfferSoldEvent/OfferArchivedEvent to DeactivateAdvertListener and OfferUpdatedEvent to UpdateAdvertAfterOfferChange. Both are queued (ShouldQueue). DeactivateAdvertListener logs `Log::info('DeactivateAdvertListener triggered')` (file only) and deactivates every Otomoto advert of the offer. UpdateAdvertAfterOfferChange pushes core data to Otomoto only if an otomotoOffer exists and offer status is Active; no logging. No app/Listeners dir, no other `$listen`, no Event::listen, no subscribers, so there is no auto-discovery surface. Who dispatches: OfferUpdatedEvent from Offer `updated` hook (Offer.php:159; also via EurocodeOffer per its comment), OfferDeletedEvent from Offer deleted hook (Offer.php:174), OfferSoldEvent from OfferFullySoldAction (and listed in OfferStockChangeAction imports only), OfferArchivedEvent from OfferArchivedAction:33. Quiet writes do not fire OfferUpdatedEvent, so Otomoto adverts are not refreshed after bulk price change etc.; that also means no side effects (inferred from Eloquent semantics).
- Evidence: app/Services/Offer/OfferEventServiceProvider.php:19-35; bootstrap/providers.php:11; app/Services/OtomotoService/Listeners/DeactivateAdvertListener.php:23-37; UpdateAdvertAfterOfferChange.php:25-42; app/Models/Offer.php:158-160,173-175.

### (b) Stale provider entry in bootstrap/providers.php
- Tag: defect? · Impact: normal
- What happens: bootstrap/providers.php lists `App\Providers\ChangesLogServiceProvider`, which does not exist (the class is `App\Modules\ChangesLog\ChangesLogServiceProvider`, registered from AppServiceProvider.php:26). No composer classmap entry for it either. If the file is really loaded, boot would fail; V1 apparently runs, so I could not reconcile this.
- Evidence: bootstrap/providers.php:6; find shows only app/Modules/ChangesLog/ChangesLogServiceProvider.php; app/Providers/AppServiceProvider.php:10,26; grep in vendor/composer for the class finds nothing.

### (c) Authentication and security events: nothing recorded
- Tag: confirmed (absence by search) · Impact: high (permissions)
- What happens: No listener or code for Login, Failed, Logout, PasswordReset, Lockout, Verified, Registered, Impersonation events; no last_login/last_seen columns; the `sessions` table (SESSION_DRIVER=database) is only the framework store and is not read by app code. Fortify features enabled: registration, resetPasswords, emailVerification, updateProfileInformation, updatePasswords, twoFactorAuthentication. Fortify has only rate limiters (login 5/min, two-factor 5/min) and no logging. 2FA enable/disable only changes users.two_factor_* columns; User activity log is `logOnly(['name','email'])`, so those columns, password and roles are not logged. Impersonation is the STS filament-impersonate `Impersonate::make()` action on UserResource: no start/end record; once impersonating, actions are attributed to the impersonated user (activity causer / ChangesLog user_id use Auth user; not traced here). Role and permission changes (Shield RoleResource Create/EditRole `syncPermissions`; user role display only) have no logging, since Role/Permission models have no LogsActivity. Account self-deletion (Jetstream DeleteUser) deletes tokens and user, unlogged; FKs: changes_logs.user_id and submissions.user_id are set null, so the actor is lost.
- Evidence: app/Providers/FortifyServiceProvider.php:34-42; config/fortify.php:146-152; config/jetstream.php:60-65; app/Models/User.php:150-154; app/Filament/Resources/UserResource.php:120; app/Filament/Clusters/Settings/Resources/Shield/RoleResource/Pages/CreateRole.php:41, EditRole.php:48; app/Filament/Resources/Shield/RoleResource/Pages/*; app/Actions/Jetstream/DeleteUser.php:16-17; migration 2025_04_12_181156_create_changes_logs_table.php:15; grep for impersonat|last_login|Login::class|Failed::class|Logout|Lockout found nothing else in app/config/routes.

### (c) Where the activity-log UI is gated
- Tag: confirmed · Impact: high (permissions)
- What happens: The rmsramos ActivitylogPlugin is authorized only for one hard-coded email (`kpiechowski25@gmail.com`), not by role/Shield permission. config/activitylog.php sets delete_records_older_than_days = 365, but no schedule or code runs `activitylog:clean` (routes/console.php has only the Otomoto command and queue:work), so retention is not enforced by the app.
- Evidence: app/Filament/Utils/PanelConfig.php:84-90; config/activitylog.php:14; routes/console.php (whole file); grep "activitylog:clean" in app/routes returns nothing. (Possibly runs from an external cron; unknown.)

### (d) FullDataAdvertUpdateJob failure handling: none, nothing surfaced
- Tag: confirmed · Impact: normal
- What happens: Schedule runs `otomoto:update-full-active-adverts` twice daily (6:00, 16:00), which dispatches a FullDataAdvertUpdateJob per 50 published adverts, delayed 10 min x chunk index. The job returns immediately if APP_ENV is local, otherwise loops adverts calling GetAdvertStatsAction and ReadAdvertDataFromApi. Neither action has try/catch or Log; both emit Filament `Notification::make()->send()` (e.g. "Wystąpił błąd przy aktualizacji ogłoszenia. ", "Statystyki zaktualizowane"), which in a queue worker has no session or recipient, so nothing is surfaced. The job defines no `failed()`, tries or backoff. An exception lands in `failed_jobs` (database-uuids driver) with no UI, command or notification reading it. Contrast: bulk Otomoto jobs through Bus::batch have `->catch` plus OtomotoBulkAction counters and the Queue Monitor page (known). Authorization failure in OtomotoAuthorizationService does `report($e)` (laravel.log). The scheduled queue worker runs `queue:work --queue=otomoto,default --stop-when-empty` every minute, so the failure of any job on any queue is equally unsurfaced.
- Evidence: routes/console.php:14,19-21; app/Services/OtomotoService/Commands/UpdateFullDataOfActiveAdvertsCommand.php:17-27; app/Services/OtomotoService/Jobs/FullDataAdvertUpdateJob.php:20-31; Actions/GetAdvertStatsAction.php:20-48; Actions/ReadAdvertDataFromApi.php:19-45; config/queue.php:107-109; OtomotoAuthorizationService.php:97; grep `function failed|$tries|$backoff|failed_jobs` in app finds only BulkActionTracker/OtomotoQueueMonitor/DispatchesOtomotoBulkAction.
- Note: whether `Notification::send()` for an unauthenticated worker writes a DB notification: unknown, no auth user in a worker so assumed lost (inferred).

### (e) Actor columns: inventory and visibility
- Tag: confirmed · Impact: normal
- What happens: There is no created_by / updated_by / *_by_id anywhere (grep across app/Models and migrations). Actor-ish data: changes_logs.user_id (null on user delete; shown in ChangesLog UI, known), activity_log.causer morph (known), offer_reservations.user_id (reserver; shown as "Dodał" and in reserved-offer tables), offer_inquiries.user_id (recipient/offer owner, not initiator), submissions.user_id (user who edited/created; shown in SubmissionResource), offers.user_id (owner, not editor), user_journals.user_id (out of scope). Dead columns: `sold_by` (sales, reservations). No `deleted_by`; Offer soft delete sets status Deleted + deleted_at with no actor (the ChangesLog entry for it is known).
- Evidence: grep results; migrations cited above; StockSaleTab.php:~97 (`->label('Dodał')`).

## Entry points
New or not-yet-censused entry points relevant to audit (previously known writers excluded).
| Kind | Path | Does | Reachable |
|---|---|---|---|
| Event listener | app/Services/OtomotoService/Listeners/DeactivateAdvertListener.php | On offer deleted/sold/archived: deactivate Otomoto adverts; file log | Reachable (registered; events dispatched at Offer.php:174, OfferFullySoldAction, OfferArchivedAction) |
| Event listener | app/Services/OtomotoService/Listeners/UpdateAdvertAfterOfferChange.php | On any non-quiet Offer update: push core data to Otomoto if Active | Reachable (Offer.php:159) |
| Event | OfferSoldEvent / OfferArchivedEvent | dispatched by journal actions | Reachable; `OfferStockChangeAction` imports OfferSoldEvent but does not dispatch it |
| Scheduled command | otomoto:update-full-active-adverts (routes/console.php:14) | Dispatch FullDataAdvertUpdateJob per 50 adverts, twice daily | Reachable (scheduler needs external cron, unknown) |
| Scheduled command | queue:work --stop-when-empty every minute (routes/console.php:19) | Drives all jobs, incl. failures to failed_jobs | Reachable |
| Queued job | app/Jobs/Offer/OfferUpdateOffersChunk.php | Woo updateProduct for a user's synced offers (dispatched from ProfilePage.php:123 via OfferUpdateService) | Reachable |
| Filament action | UserResource Impersonate (UserResource.php:120) | Log in as another user, no record | Reachable |
| Filament action | Shield RoleResource Create/EditRole syncPermissions | Change role permissions, no record | Reachable (two RoleResource copies, Resources/Shield and Clusters/Settings/Resources/Shield; duplication unreconciled) |
| Filament actions | StockSaleTab / UserReservationSection: "Usuń rezerwacje", "Sprzedaj zarezerowane", "Oznacz jako sprzedane" | Hard-delete reservation, set offer Active / create sale | Reachable |
| Filament action | OfferInquiry Create/Accept/Reject actions | Create inquiry, status change, DB notifications | Reachable |
| Filament bulk | SubmissionResource mark-read (line 168), ViewSubmission:79,491 | saveQuietly is_read / delete Submission | Reachable |
| Jetstream action | app/Actions/Jetstream/DeleteUser.php | Self account deletion | Reachable if accountDeletion UI exposed; unknown on the Filament panel |
| Auto-discovery | app/Listeners | does not exist | none |
| Provider entry | bootstrap/providers.php `App\Providers\ChangesLogServiceProvider` | nonexistent class | unreachable/broken |

## Contradictions and defects
- bootstrap/providers.php:6 references a nonexistent provider class (see b).
- `Offer` deleted hook logs "Dispatching OfferDeletedEvent" and `saveQuietly`s deleted_at/status; the listener then deactivates adverts (known mechanics, no audit effect).
- Two copies of RoleResource (app/Filament/Resources/Shield and app/Filament/Clusters/Settings/Resources/Shield).

## Leads outside scope
- Users module: impersonation/2FA/role changes unrecorded; user hard delete loses actors (FK set null); `Notification::send()` inside jobs.
- Offers module: reservation/sale model (hard delete, lost reserver, unused `sold_by`), Submission as review inbox (no reviewer), `OfferStockChangeAction` unused OfferSoldEvent import.
- Otomoto module: scheduled full-data update silently fails; `env('APP_ENV')` used in job (config-cache issue).

## Could not establish
- Whether the scheduler and `activitylog:clean` run via external cron/host config: nothing in repo; a deployment answer would settle it.
- Whether the app boots with the stale provider entry: needs runtime.
- Whether Offer saves from reservation/sale actions generate ChangesLog rows with the right user (belongs to hook analysis in r01-r04; not re-traced).
- Actor attribution during impersonation: vendor behaviour, not traced.

## Coverage ledger
- Searched: `Listener|Event::|\$listen|Subscriber`; OfferSoldEvent|ArchivedEvent|DeletedEvent|UpdatedEvent; `saveQuietly|updateQuietly|withoutEvents|withoutLogging|disableLogging|DB::table|DB::statement|->insert(|upsert|insertGetId|->delete()|forceDelete|destroy|truncate`; `OfferReservation|OfferSale|OfferInquiry::|Submission::|reservations()|sales()`; `handleUsersPublishedOffer|OfferUpdateService|OfferStatusAggregate|OfferPriceService`; `impersonat|last_login|last_seen|Login::class|Failed::class|Logout|Lockout|Verified|Registered|TwoFactor`; `syncRoles|assignRole|givePermissionTo|syncPermissions`; `function failed|$tries|$backoff|failed_jobs|->catch(|report(`; `_by|created_by|updated_by|causer|actor|sold_by`; `ActivitylogPlugin|activitylog:clean|delete_records_older`; `sessions`; `OtomotoServiceProvider|ChangesLogServiceProvider`; migrations list filtered by reserv|sale|inquir|submission|activity|session|log|notif|failed|job.
- Examined: app/Services/Offer/* (all 4 files + events dir listing), OfferEventServiceProvider, bootstrap/providers.php, bootstrap/app.php, routes/console.php, routes/api.php, OtomotoServiceProvider, both Otomoto listeners, FullDataAdvertUpdateJob, UpdateFullDataOfActiveAdvertsCommand, GetAdvertStatsAction, ReadAdvertDataFromApi, OfferUpdateOffersChunk, OfferReservation/OfferInquiry/OfferSale models, OfferReservationChangeAction, OfferStockChangeAction, OfferFullyReserved/FullySold/Archived actions, FetchWooProducts:120-170, StockSaleTab:95-125, UserReservationSection:40-70, FortifyServiceProvider, User.php:150-155, PanelConfig:80-95, submissions migration, Offer.php:140-195, config fortify/jetstream/queue (grep), AcceptInquiryAction (via grep).
- Matched, not examined: other saveQuietly writers (known from r01-r04); Fetch* taxonomy jobs (fetch-from-WP imports, covered as ~20 known writers, not read); UpdateCoreAdvertData / DeactivateAdvertAction internals (Otomoto API calls, OtomotoOffer not logged per r-earlier; not read); OfferAcceptedAction / OfferRejectedAction / ActivateFromDraftAction (JournalAction subclasses, not read in full); tests/Feature (Jetstream stock tests, no audit tests).
