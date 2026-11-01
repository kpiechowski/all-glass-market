# r04 — cross-repo: technical / operational logging in V1

Question: What technical / operational logging exists in V1 — what gets logged, where it goes, can an operator see any of it from the panel? · V1 commit: 511ce3b

## Summary
- Technical logging is Laravel file logging only: `Log::`/`logger()` calls in ~75 live sites (about 22 more are imports or commented-out), going to `laravel.log` (default `stack` -> `single`) and a dedicated `storage/logs/otomoto.log`. No log viewer, no Sentry/Telescope/Pulse/Horizon, no `failed_jobs` UI, no log route.
- The ONLY operator-visible surface that resembles a log is the admin-only "Monitor kolejki Otomoto" page, which shows the `otomoto_bulk_actions` table (status, counters, no error text). Details of errors point the operator to `otomoto.log`, which the panel cannot show.
- Other operator feedback is ephemeral Filament toast notifications (not persisted) and database notifications for Otomoto bulk actions and Woo bulk sync (persisted in the bell for the acting user only).
- Failure handling is mostly catch-and-return-false; failed jobs are retried only where `$tries=3` is declared; exhausted jobs go to `failed_jobs` (database-uuids driver) which nobody can see in the panel.
- `wordpress_syncs` / `woo_commerce_syncs` are mapping tables (wordpress_id, sync_date, last_update); they hold no status/error/message columns, so they are not operator logs.

## Findings

### Log destinations and config
- Tag: confirmed · Impact: normal
- What happens: default channel `LOG_CHANNEL=stack`; stack members from `LOG_STACK` (default `single` -> `storage/logs/laravel.log`, level `LOG_LEVEL` default debug). Custom channel `otomoto` = `single` driver at `storage/logs/otomoto.log`, level `OTOMOTO_LOG_LEVEL` (default debug), placeholders replaced. Also defined (template, not necessarily used): daily (14 days via `LOG_DAILY_DAYS`), slack (`LOG_SLACK_WEBHOOK_URL`, level default critical but env LOG_LEVEL overrides), papertrail, stderr, syslog, errorlog. `.env.example` sets only LOG_CHANNEL, LOG_STACK=single, LOG_DEPRECATIONS_CHANNEL=null, LOG_LEVEL=debug. Real production .env not visible -> actual channels `unknown`.
- Evidence: config/logging.php:21,53-131; .env.example:17-20.

### No custom exception handling / reporting
- Tag: confirmed · Impact: normal
- What happens: `withExceptions` is empty, so unhandled exceptions are reported by Laravel default to the default channel only. No Sentry/Flare/Telescope/Pulse/Horizon/log-viewer in composer.json (grep matched none).
- Evidence: bootstrap/app.php:19-21; composer.json (grep sentry|telescope|pulse|horizon|log-viewer|nightwatch: no hits).

### Otomoto bulk-action lifecycle is logged to otomoto.log and persisted in a table
- Tag: confirmed · Impact: normal
- What happens: when an admin dispatches a bulk activate/publish/update/regenerate, a row in `otomoto_bulk_actions` is created (user_id, action, status queued|processing|completed|completed_with_errors|failed, total/processed/failed_jobs, offers_succeeded/unconfirmed/errored, offer_ids JSON, started_at, finished_at). Logged on `otomoto` channel: info "Bulk action #N requested: action" (context user_id, offers), info "processing started", and at finish info or warning (warning if failed_jobs>0 or offers_errored>0) with the counters. Each stage also sends a database notification to the acting user ("Zlecono akcję Otomoto", "Akcja Otomoto w trakcie realizacji", "Akcja Otomoto zakończona", "... zakończona z błędami" which says "sprawdź logi Otomoto").
- Evidence: app/Services/OtomotoService/BulkActionTracker.php:30-45,74,126-150,150-195; database/migrations/2026_07_25_000000/000001_*otomoto_bulk_actions*; app/Models/OtomotoBulkAction.php:7-45.
- Note: status `failed` constant exists and is rendered ("Błąd"), but I found no code that sets it (BulkActionTracker only sets completed / completed_with_errors). Tag: inferred (grep of STATUS_FAILED in app showed only the monitor page and model).

### Operator-visible surface 1: "Monitor kolejki Otomoto"
- Tag: confirmed · Impact: normal
- What happens: Filament page at `.../otomoto-offers/queue-monitor`, title "Monitor kolejki Otomoto". Access: `canAccess` = `Auth::user()->isAdmin()` (admin only). Table of all `otomoto_bulk_actions` newest first, polls every 10 s. Columns: "Akcja", "Zlecający", "Status" (W kolejce / Przetwarzanie / Zakończono / Zakończono z błędami / Błąd), "Oferty", "Postęp (paczki)" (with "N paczek przerwano (błąd krytyczny)"), "Poprawne", "Niepotwierdzone", "Błędy" (tooltip: "Szczegóły w otomoto.log."), "Zlecono", "Zakończono". Row action "Oferty" (slide-over listing offer titles). Header: "Odśwież", "Wyczyść log" (confirm "Usuń wszystko": hard-deletes ALL rows of otomoto_bulk_actions, notification "Monitor wyczyszczony" / "Usunięto N wpisów."; does not touch Otomoto or the queue), "Powrót". Linked from the Otomoto offers list via button "Monitor kolejki" (that button's own visibility is not restricted separately; the page itself blocks non-admins).
- Not shown: error messages, exceptions, per-offer failure reasons; only counts. No filters, no search, no export.
- Evidence: app/Filament/Resources/OtomotoOfferResource/Pages/OtomotoQueueMonitor.php:29-34,37-70,72-195; OtomotoOfferResource.php:265 (route 'queue-monitor'); ListOtomotoOffers.php:69-73.
- decide: "Wyczyść log" destroys the only persisted run history; V2 should decide retention instead of hard delete.

### Operator-visible surface 2: database notifications (bell)
- Tag: confirmed · Impact: normal
- What happens: panel has `->databaseNotifications()`; Otomoto bulk lifecycle (above) and `BulkSynchProductToWooAction` ("Synchronizacja zakończona", "✅ Zsynchronizowano: x/y", sent to the dispatching user via sendToDatabase) land in the `notifications` table for that user only. Failures per offer are not listed; Woo bulk sync counts only successes (the `$failed` collection is never filled).
- Evidence: app/Providers/Filament/AdminPanelProvider.php:99; app/Services/ApiService/Actions/BulkSynchProductToWooAction.php:34-56; migration 2024_10_24_161428_create_notifications_table.

### Operator-visible surface 3: ephemeral toast notifications for technical errors
- Tag: confirmed · Impact: normal
- What happens: Filament `Notification::make()->...->send()` (not persisted, shown only to the user in the request; in queued jobs a `send()` has no browser session so it is lost) via `App\Services\Notifier\Notifier` (success/error/warning). Technical errors surfaced this way: Otomoto API error (warning "Wyjątek dla akcji: {action}" with exception message); WordPress: "Błąd połączenia z api" / "Nie udało się utworzyć klienta Api sprawdź dane", "Wystąpił bład" + "Szczegóły dostępne w dzienniku zdarzeń" (the "dziennik zdarzeń" it refers to does not exist in the panel — it can only mean laravel.log); Woo: "Wystąpił błąd podczas synchronizacji", "Nie udało się zaktualizowac oferty" (+ exception message), "Błąd usuwania z Wordpressa", "Nie udało się zsynchronizować", "Błąd aktualizacji, prawdopodobnie kategoria nie istnieje"; Otomoto publish: "Wystąpił błąd przy publikowaniu. "; image delete: title "Wystąpił błąd" (styled success!).
- Evidence: app/Services/Notifier/Notifier.php:7-50; WordpressApiService.php:236-258; WoocommerceApiService.php:110-185,215-262; OtomotoApiService.php:360-380; PublishAdvertAction.php:29-37; Actions/Images/DeleteAllImagesFromCollection.php:62-70.
- defect?: DeleteAllImagesFromCollection shows the error notification with `->success()` and then falls through to a success notification as well.

### No log viewer, no failed-jobs UI
- Tag: confirmed · Impact: normal
- What happens: web routes only redirect `/` and `/login`; api route only `/user`. Grep for `storage/logs`, `laravel.log`, `failed_jobs`, `queue:retry|failed`, `log-viewer` finds nothing that reads logs or failed jobs in app/ routes/ resources/. `failed_jobs` is configured (`QUEUE_FAILED_DRIVER=database-uuids`) but only Laravel writes to it. Operator access to logs = server shell.
- Evidence: routes/web.php:1-10; routes/api.php; config/queue.php:107; grep ledger below.

### Queue running and retry
- Tag: confirmed · Impact: high (silent data sync failure)
- What happens: `QUEUE_CONNECTION=database`; the scheduler runs `queue:work --queue=otomoto,default --stop-when-empty --timeout=500` every minute (withoutOverlapping, onOneServer) - so there is no persistent worker; the cron-driven worker is the worker. Named queues makes, donor_models, generations, oem_codes are "manual" (need a manual worker). Also `otomoto:update-full-active-adverts` scheduled twiceDaily(6,16): dispatches `FullDataAdvertUpdateJob` chunks of 50, each delayed 10 min * chunkIndex; its only output is `$this->info('Jobs dispatched in chunks.')` (scheduler output discarded). `retry_after` default 90 s for database queue vs job timeouts 300 s / worker 500 s: potential duplicate execution `unknown` (needs prod env value DB_QUEUE_RETRY_AFTER).
- Retries: `$tries=3` + `$timeout=300` on PublishAdvertsJob, ActivateAdvertsJob, UpdateAdvertsDataJob, RegenerateAdvertsDescriptionJob; `$tries=3, $timeout=120` on BulkSynchProductToWooAction. No `backoff`, no `failed()` method anywhere (grep `function failed` no hits), no `retryUntil`. The Otomoto chunk jobs catch Throwable per offer, so a thrown error does not fail the job; only errors outside the loop would retry/fail.
- Otomoto API layer: on HTTP 401, one token refresh and retry (`maxRetries`), logged as warning.
- Evidence: routes/console.php:15-27; config/queue.php:16,42; Jobs/*Job.php (`$tries`, `$timeout`); UpdateFullDataOfActiveAdvertsCommand.php:15-27; OtomotoApiService.php:55-70.

### Bulk batch failure handling (Otomoto)
- Tag: confirmed · Impact: normal
- What happens: `Bus::batch(...)->allowFailures()` on queue `otomoto`; `catch` callback logs error "Bulk action #N: chunk job failed" to otomoto.log with batch_id and exception; `finally` calls `BulkActionTracker::finish` which marks completed_with_errors if failed chunk jobs or errored offers, and notifies the actor. Per-offer exceptions in each chunk job are caught and logged error ("Bulk #N: publikacja oferty ID nie powiodła się" and equivalents) and counted into offers_errored. Surfaced to: the actor (bell notification with counts), admins (monitor counts). Error text only in otomoto.log. Bulk row stuck in queued/processing if the worker dies is possible: `unknown`, no timeout/reaper found.
- Evidence: DispatchesOtomotoBulkAction.php:46-72; Jobs/PublishAdvertsJob.php:40-52; ActivateAdvertsJob.php:42-50; UpdateAdvertsDataJob.php:47-55; RegenerateAdvertsDescriptionJob.php:58-65.

### WordPress/Woo fetch (import) jobs: log-and-continue, swallowed
- Tag: confirmed · Impact: high (silent partial import)
- What happens: `FetchWordpress{Cars,Colors,Equipment,Producers}`, `FetchWooOfferTypes`, `FetchWooProducts` page through the remote API (job re-dispatches itself with page+1); progress logged at info ("started ... chunk N", "no more models", "Created and synch X with id..."). Exceptions are caught: Cars/Colors/Equipment/Producers pass them to `WordpressApiService::handleException` (error log + a Filament toast that is lost in queue context); FetchWooOfferTypes does `Log::info($e)` only (info level for an error); FetchWooProducts image errors logged `error`, missing image source / missing relation as `warning`. Because the exception is swallowed, the job ends "successfully" and the chain stops silently (the next page is not dispatched), with no retry and no failed_jobs row.
- Evidence: Jobs/FetchWordpressCars.php:36-92; FetchWordpressEquipment.php:89; FetchWordpressProducers.php:90; FetchWordpressColors.php:91; FetchWooOfferTypes.php:58-88; FetchWooProducts.php:43-55,193,228,245.

### Other swallowed errors
- Tag: confirmed · Impact: normal
- What happens: `SynchOemCodeFromWordpressJob` logs every sku outcome at info including errors ("Error on sku ...", message); `BulkCreateAdvertsAction` and `OtomotoSynchronizationTab` (create Otomoto offers) log error via `logger()->error` with exception and put the offer in a "skipped" list; `UploadImageToWordPressAction` logs error and returns null; OtomotoAuthorizationService logs error and `report($e)` (the only explicit `report()`), returns null.
- Evidence: Jobs/Offer/SynchOemCodeFromWordpressJob.php:36-68; BulkCreateAdvertsAction.php:78-84; OtomotoSynchronizationTab.php:231-235; UploadImageToWordPressAction.php:31-37; OtomotoAuthorizationService.php:72,87-97.

### Log call-site census by area (live calls; commented-out excluded)
| Area | Sites (file:line) | Level | Channel | Context attached | Trigger |
|---|---|---|---|---|---|
| Otomoto bulk lifecycle | BulkActionTracker.php:35,74,146 | info / warning (dynamic) | otomoto | user_id, offers count; counters | admin bulk action, queue |
| Otomoto bulk jobs | PublishAdvertsJob:44, ActivateAdvertsJob:44, UpdateAdvertsDataJob:49, RegenerateAdvertsDescriptionJob:60; DispatchesOtomotoBulkAction:60 | error | otomoto | exception (and batch_id,error) | per-offer exception in queue |
| Otomoto API | OtomotoApiService.php:30,50,61,135,160,367 | error x4, warning, info x2 | otomoto | attempt; message/status/body for :367 | any API call; 401 retry; activate/deactivate success |
| Otomoto auth | OtomotoAuthorizationService.php:35,45,72,81,91,106 (+report():97) | info x4, error x2 | default (not otomoto) | expires_in/at; message,status,body | token refresh |
| Otomoto listener | DeactivateAdvertListener.php:27 | info | default | event class | offer sold/archived/deleted |
| Otomoto offer create | BulkCreateAdvertsAction.php:79; OtomotoSynchronizationTab.php:232 | error (logger()) | default | exception | create adverts from offers |
| Otomoto images | DeleteAllImagesFromCollection.php:69 | info (an error!) | default | message only | image collection delete |
| Otomoto donor data | FetchDonorMakes:34,48; FetchDonorModels:41,47,62; FetchDonorGenerations:53,65 | info | default | makes/models/codes (FetchDonorModels:41 uses single quotes so `{$this->makeCode}` is logged literally: defect?) | manual artisan `otomoto:donor-sync`, queues makes/donor_models/generations |
| WordPress/Woo import jobs | FetchWordpressCars:36,57,80,124,127; Colors:40,61,83,124,127; Equipment:38,59,81,122,125; Producers:39,60,82,123,126; FetchWooOfferTypes:58,88,117,120; FetchWooProducts:43,55,96,127,133,193,228,245 | info (mostly), warning x2, error x1, info-for-error x1 | default | page, ids, timestamps | WordPress data orchestrator / import button |
| WordPress API | WordpressApiService.php:240 (info), :250 (error) | info/error | default | action, message, code | any WP sync call failing |
| Woo API | WoocommerceApiService.php:118 (error: message, request, response), 152, 181 (error, string), 239, 283, 293 (info) | error/info | default | as noted | offer sync create/update/delete, category sync, test connection |
| WP image upload | UploadImageToWordPressAction.php:33 | error | default | message | product sync |
| Offers | Offer.php:173 | info | default | offer id | offer soft-"deleted" model hook |
| Offers | BulkPriceUpdateSyncJob.php:33 | info | default | offer id, previous/final price | bulk price update job |
| Offers | SynchOemCodeFromWordpressJob.php:36,48,59,63,67,68 | info | default | sku, oem | `wordpress:sync-oems` |
| Auth/users | none found (no Log:: in login/2FA/user code) | | | | |
| Imported but unused | UpdateAdvertAction, ReadAdvertDataFromApi, UpdateCoreAdvertData, OtomotoMetadataService, DeactivateAdvertAction, GetAdvertStatsAction, UpdateAdvertAfterOfferChange, OfferUpdatedEvent, WooProductDTO, UserJournal, SelectEurocodesPartial, IsOfferTaxonomyModel, OfferStockChangeAction, OfferFullySoldAction, OfferArchivedAction (commented), ListOtomotoOffers, BulkPublishAdvertsAction | n/a | | | |
- Tag: confirmed. Evidence: grep of `Log::|logger(|report(|->log(` over app routes config bootstrap database (92 raw lines incl. imports, comments and ChangesLogService->log which is business history, out of scope).
- No logging of auth events (login, 2FA, password) found; no logging of images/media errors outside WP import and Otomoto image deletion; no logging of permission changes.

### Sync tables are not operator logs
- Tag: confirmed · Impact: normal
- What happens: `wordpress_syncs` (morph syncable, wordpress_id, auto_update, sync_date, last_update) and `woo_commerce_syncs` (offer_id, wordpress_id, auto_update, sync_date, last_update) record only the remote id and last sync time; no status/error/message. A 404 on update, or `woocommerce_rest_product_invalid_id`, deletes the sync row (so the offer silently becomes unsynced). A failed sync leaves no record besides laravel.log and a transient toast.
- Evidence: migrations 2024_11_02_173527, 2024_11_02_192226; WordpressApiService.php:180-186; WoocommerceApiService.php:142-153.

### Scheduled commands output
- Tag: confirmed · Impact: normal
- What happens: scheduled: `inspire` hourly (demo), `otomoto:update-full-active-adverts` at 6 and 16, and `queue:work` each minute. Two commented-out `Schedule::call` Log::info heartbeat entries. Artisan commands in app/Console/Commands (`otomoto:donor-sync`, `otomoto:donor-generation-sync`, `wordpress:sync-oems`, `eurocodes:sync-models`, `oem:migrate-to-json`) are manual; no scheduler output is captured to file/DB (no `sendOutputTo`, `emailOutputOnFailure`, `onFailure`).
- Evidence: routes/console.php:9-31; app/Console/Commands/*.php signatures.

## Entry points
| Kind | Path | Does | Reachable |
|---|---|---|---|
| Filament page | app/Filament/Resources/OtomotoOfferResource/Pages/OtomotoQueueMonitor.php | displays otomoto_bulk_actions; "Wyczyść log" deletes all rows | reachable, admin only (isAdmin), link on ListOtomotoOffers "Monitor kolejki" button |
| Page header action | OtomotoQueueMonitor "Wyczyść log" | hard delete of all bulk-action rows | reachable, admin only |
| Service (writer) | BulkActionTracker::requested/markProcessing/chunkCompleted/finish | writes row + otomoto.log + DB notifications | reachable via DispatchesOtomotoBulkAction (BulkPublish/Activate/Update/RegenerateDescriptions actions) |
| Filament bulk actions (writers) | OtomotoOfferResource/Components/BulkPublishAdvertsAction, BulkRegenerateDescriptionsAction, others using DispatchesOtomotoBulkAction | trigger tracked batches | reachable (Otomoto list) |
| Queued jobs (log writers) | PublishAdvertsJob, ActivateAdvertsJob, UpdateAdvertsDataJob, RegenerateAdvertsDescriptionJob | error logs per offer | reachable via bulk actions |
| Queued jobs (log writers) | FetchWordpress{Cars,Colors,Equipment,Producers}, FetchWooOfferTypes, FetchWooProducts, FetchWordpressDataOrchestrator | info/error import logs | reachable via WP import action (dispatch in WoocommerceApiService::fetchProducts and orchestrator chain) |
| Queued job | SynchOemCodeFromWordpressJob | info logs per sku | reachable via `wordpress:sync-oems` (manual) |
| Queued job | BulkPriceUpdateSyncJob | info per offer | reachable via price bulk action |
| Queued job | BulkSynchProductToWooAction | DB notification summary | reachable via Woo bulk sync action |
| Jobs FetchDonorMakes/Models/Generations | donor data | info logs | reachable only via manual artisan `otomoto:donor-sync`, `otomoto:donor-generation-sync` and manual named-queue workers |
| Artisan command | otomoto:update-full-active-adverts | dispatches updates, prints one line | scheduled 06:00/16:00 |
| Scheduled task | queue:work every minute | the only worker | scheduled |
| Event listener | DeactivateAdvertListener | info log | reachable if registered; registration not verified in this mission (`unknown`) |
| Model hook | Offer::booted deleted path (Offer.php:173) | info log | reachable on offer delete |
| Panel UI | databaseNotifications bell | shows persisted notifications per user | reachable, all panel users, own only |
| Service | Notifier::error/warning/success | transient toast | reachable (many callers) |
| Controller | ApiController.php:20 `dd(...testConnection())` | debug dump | no route found in routes/web.php or api.php: unreachable |
| Filament component | OfferResource AdminTab.php:122 `->after(fn($livewire) => dd($livewire))` | debug dump | reachable if that action is rendered; `unknown` whether enabled; defect? |
| Config | config/logging.php channels (single, otomoto, daily, slack ...) | log sinks | active: stack/single + otomoto; slack not wired by default |

## Contradictions and defects
- "Szczegóły dostępne w dzienniku zdarzeń" (WordpressApiService.php:256) and "sprawdź logi Otomoto" / "Szczegóły w otomoto.log." point operators at logs that the panel cannot show.
- Otomoto monitor status "Błąd" (STATUS_FAILED) is displayed but never set by the tracker (inferred).
- FetchDonorModels.php:41 single-quoted string logs literal `{$this->makeCode}` (defect?).
- Errors logged at info: FetchWooOfferTypes:88, DeleteAllImagesFromCollection:69, SynchOemCodeFromWordpressJob:67-68, Woo category sync :239, makes a level-based alert (slack at critical) useless.
- Otomoto auth logs go to default channel, not `otomoto`, although the channel comment says it keeps Otomoto noise out of laravel.log.
- "Wyczyść log" erases the history that the page presents as a log.
- Debug `dd()` calls left in AdminTab.php:122 and ApiController.php:20.
- Queue `retry_after` 90 s default vs 300 s job timeout (depends on prod env).

## Leads outside scope
- `UserNotification` model/table shown by Livewire `ListUsersNotifications` ("Aktywność użytkowników") is a user-activity feed (Users/Audit module candidate); not examined.
- `ChangesLog`, `activity_log`, `UserJournal` (r01-r03, Users).
- Event listener registration for DeactivateAdvertListener / UpdateAdvertAfterOfferChange not verified.
- `FullDataAdvertUpdateJob` (scheduled twice daily) failure handling not examined.
- SECURITY_INCIDENT_2026-10.md at V1 root: not read.

## Could not establish
- Actual production LOG_CHANNEL/LOG_STACK/LOG_LEVEL, log rotation, and whether slack/other sinks are active: .env not available (runtime/ops answer).
- Whether any worker other than the scheduled queue:work runs in production (supervisor config absent from repo; docker-compose.yml not read).
- Whether bulk-action rows can stay stuck in processing after a worker crash: no reaper found; runtime would settle it.
- Whether `failed_jobs` contains rows in production: requires DB.

## Coverage ledger
- Searched (grep over app, routes, config, bootstrap, database, resources, lang, tests, composer.json): `Log::`, `logger(`, `report(`, `->log(`, `Log;`, `error_log`, `dd(`, `dump(`, `Log::channel`, `LOG_`, `QUEUE_`, `SENTRY|TELESCOPE|NIGHTWATCH`, sentry/telescope/pulse/horizon/log-viewer/nightwatch in composer.json, `function failed`, `$tries`, `$backoff`, `backoff(`, `$timeout`, `retryUntil`, `WithoutOverlapping`, `ThrottlesExceptions`, `failed_jobs`, `failed-jobs`, `queue:(retry|failed|work|flush)`, `storage/logs`, `laravel.log`, `otomoto.log`, `\.log`, `catch (`, `danger()`/`->warning()` (counted, 39), `OtomotoBulkAction`, `WordpressSync`, `WooCommerceSync`, `wordpress_syncs`, `woo_commerce_syncs`, `otomoto_bulk`, `databaseNotifications`, `dzienni`, `OtomotoQueueMonitor`, `signature`, `Schedule::`; migrations list for sync|bulk|failed|jobs|log|journal|batch|notif; tests for Log:: (none).
- Examined: config/logging.php (relevant parts), config/queue.php (grep), bootstrap/app.php, routes/console.php, routes/web.php, routes/api.php, BulkActionTracker, OtomotoBulkAction model + 2 migrations, OtomotoQueueMonitor, DispatchesOtomotoBulkAction, PublishAdvertsJob, BulkSynchProductToWooAction, OtomotoApiService (excerpts), OtomotoAuthorizationService (excerpt), WordpressApiService (excerpts), WoocommerceApiService (excerpts), UploadImageToWordPressAction, BulkCreateAdvertsAction, OtomotoSynchronizationTab (excerpt), DeleteAllImagesFromCollection (excerpt), PublishAdvertAction (excerpt), FetchWordpressCars/FetchWooProducts (excerpts), UpdateFullDataOfActiveAdvertsCommand, DeactivateAdvertListener, BulkPriceUpdateSyncJob (excerpt), Notifier, ListOtomotoOffers (excerpt), Offer.php:168-176, sync-table migrations, ListUsersNotifications (head).
- Matched, not examined: other Activate/Update/Regenerate jobs (same pattern, only grep lines), FetchWordpressColors/Equipment/Producers/FetchWooOfferTypes/Donor* jobs (grep lines only), console commands other than the scheduled one (signatures only), unused `use Log` imports files (no calls), tests (no logging tests; none exist beyond Jetstream auth tests), docker-compose.yml, SECURITY_INCIDENT_2026-10.md.
