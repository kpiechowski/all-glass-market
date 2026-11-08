# r04 — flow: how ApiSettings values reach the integrations

Question: How do integration settings edited on ApiSettings reach the external integrations, and how is each integration actually configured in V1? · V1 commit: 511ce3b

## Summary
- ApiSettings saves 8 keys into the `settings` table (key/value strings). Only WooCommerce and WordPress read them. Both read them in the service constructor.
- WordPress has no URL of its own. It reuses `woocommerce_site_url`, plus `wordpress_username` and `wordpress_password`.
- Otomoto is NOT configured from the settings table. It uses `env()` called directly in app code: OTOMOTO_URL, OTOMOTO_CLIENT, OTOMOTO_SECRET, OTOMOTO_EMAIL, OTOMOTO_PASSWORD. Nothing is in config/ or .env.example. The ApiSettings Otomoto form is dead code: it only shows a warning and is not registered or rendered.
- The only settings-to-env link is the seeder, which writes WOO_URL, WOO_KEY, WOO_SECRET, WP_USER and WP_PASSWORD into the settings table once. There is no runtime fallback in either direction.
- WP_DEFAULT_IMAGE_ID is read from env at runtime for the WordPress featured-image fallback.
- Mail uses the Laravel default config/env (MAIL_*). There is no SMS or payment integration in the code I searched.
- Verify: (1) the woo*/wordpress* settings are live. (2) site_name and site_description were added on 2024-10-13, and nothing outside the page reads them. (3) settings.user_id is never written or read.

## Findings

### Saving: each of the two forms has its own "Zapisz" button that upserts every field of that form
- Tag: confirmed · Impact: high (credentials)
- What happens: the button loops over `$form->getState()` and calls `Setting::setValue($field,$value)` for each field. This is an upsert by `key`. It then shows the notification "Zapisano". There is no validation except `required` on the user select. No connection check happens on save. The `Setting` model is activity-logged (key and value, so secrets go into the log in plain text). Values are stored unencrypted, and the migration column is `string('value')` (varchar 255).
- Keys written: WordPress form: `wordpress_username`, `wordpress_password`, `woo-offers-default-user`. WooCommerce form: `woocommerce_site_url`, `woocommerce_client_key`, `woocommerce_secret_key`, `woocommerce_save_as_draft`.
- Evidence: app/Filament/Clusters/Settings/Pages/ApiSettings.php (getSaveFormAction, the last method before the commented-out schema). app/Models/Setting.php::setValue and ::value. database/migrations/2024_10_13_111544_create_settings_table.php:15-18.
- Note: `Setting::setValue(string $key, string $value)` is not strict-typed, so the toggle `false` is coerced to "" and `true` to "1". A null state (never-filled text input) would raise a TypeError (inferred, not run). A TypeError would abort the loop partway, so some keys would be saved and some not, with no transaction. Tag: inferred.
- Note: the "Akcje"/"Test połączenia" buttons call the service singleton built from the already-saved DB values. Unsaved form input is never tested.

### Test connection: reads the saved settings, not the form
- Tag: confirmed · Impact: normal
- What happens: both sections have a "Test połączenia" header action. WooCommerce calls `GET system_status` through the WooCommerce client. WordPress calls `GET users/me`. On success the button turns green and is disabled. Both buttons write the same state key `woo_api_test`, which is not a field in either form's schema. This is probably a defect, and it looks like a copy-paste.
- Woo success shows "Api jest połączone!". Failure (HttpClientException) shows "Błąd połączenia z api" and a log entry. WordPress catches any Exception, logs it and shows "Wystąpił bład".
- If credentials are empty, the client is null and "Błąd połączenia z api" with "Nie udało się utworzyć klienta Api sprawdź dane" is shown, plus an Info log. `testConnection` returns null.
- Evidence: ApiSettings.php (the 'Test połączenia' actions). WoocommerceApiService.php::testConnection and ::ensureClientIsDefined. WordpressApiService.php::testConnection.

### How the settings reach the integrations: credentials are read once at service construction, and the service is a singleton
- Tag: confirmed · Impact: high
- What happens: `ApiService::__construct` calls `setCredentials()` and then `defineClient()`. Those read `Setting::value()` (a DB query per key) and build the client. If any needed credential is empty, `$client=null` and the service is "inactive". The services are bound as singletons, so one instance per PHP process. Web requests and the per-minute `queue:work --stop-when-empty` process are short-lived, so a saved change takes effect on the next request or worker run. A long-lived worker would keep stale credentials (inferred, there is no worker config in the repo).
- Woo client: `Automattic\WooCommerce\Client(baseUrl,key,secret,[version wc/v3, wp_api true, query_string_auth false])`. It needs `woocommerce_site_url`, `woocommerce_client_key` and `woocommerce_secret_key`, and all three must be non-empty.
- WP client: Guzzle with base_uri = `woocommerce_site_url` + `/wp-json/wp/v2/`, basic auth `wordpress_username` and `wordpress_password`, and the header Accept: application/json. All three must be non-empty. It has no URL key of its own. Changing the "Adres Url Woocommerce" also moves WordPress. No trailing-slash normalisation (inferred from the string concatenation).
- Evidence: app/Services/ApiService/ApiService.php:17-21. WoocommerceApiService.php::setCredentials and ::defineClient. WordpressApiService.php::setCredentials and ::defineClient. app/Providers/ApiServiceProvider.php:20-21.

### `woocommerce_site_url` / `_client_key` / `_secret_key` — what each does and what happens when empty or wrong
- Tag: confirmed (empty), inferred (wrong values: library behaviour not traced) · Impact: high
- Empty: any of the three leaves the Woo client null. Every Woo method (`syncProduct`, `updateProduct`, `deleteProductFromWoo`, `fetchProducts`, `syncProductCategory`, `updateProductCategory`, `testConnection`) calls `ensureClientIsDefined()`. It emits a UI notification via Notifier, "Błąd połączenia z api" / "Nie udało się utworzyć klienta Api sprawdź dane", logs at Info, and returns false or null. This is a soft failure, with no exception.
- Because Offer's `updated` hook calls `updateProduct`, a missing config pops an error notification on every offer update that has `wooSync` and `auto_update` (Offer.php:225 area, `afterUpdateSyncAction`).
- Exception: `findBySKU` has no guard and dereferences the null client (fatal Error).
- Exception: `FetchWooProducts::handle` calls `$woocommerceApiService->getClient()` then `$client->get(...)` with no null check. With empty credentials the job fails with an Error on null, and is retried or failed by the queue.
- Wrong values: Woo HttpClientException is caught in each method, which notifies, logs and returns false. `updateProduct` additionally deletes the local `wooSync` if Woo answers 400 `woocommerce_rest_product_invalid_id`. `deleteProductFromWoo` catches only HttpClientException. `fetchProducts` only dispatches a job, so the catch there rarely fires. Real errors surface later inside the jobs.
- Evidence: WoocommerceApiService.php (the methods above). app/Services/ApiService/Jobs/FetchWooProducts.php:41-46. Offer.php::afterUpdateSyncAction.

### `wordpress_username` / `wordpress_password`
- Tag: confirmed · Impact: high
- Used only in WordpressApiService, for model sync (`syncModel`, `updateModel`), image upload, and taxonomy fetch jobs (colors, equipment, producers, cars, offer types). Empty means a null client, and the same soft-failure notification as Woo. Wrong means a Guzzle 401 or 403. `testConnection`, `syncModel` and `updateModel` catch Exception, log and notify "Wystąpił bład" / "Szczegóły dostępne w dzienniku zdarzeń", and return false. `updateModel` also deletes the local `wordpressSync` row on a 404. The "reveal" eye on the password fields is allowed only for `Auth::user()->isSuper()`.
- Evidence: WordpressApiService.php::setCredentials, ::syncModel, ::updateModel, ::handleException. ApiSettings.php (`revealable(fn()=>Auth::user()->isSuper())`).

### `woocommerce_save_as_draft` — new offers go to Woo as "draft"
- Tag: confirmed · Impact: normal
- What happens: when building the product DTO from an offer, `status` = 'draft' if `Setting::value('woocommerce_save_as_draft') === "1"`, otherwise 'publish'. The value is read from the DB on every DTO build (not cached). A missing row or an empty value means 'publish'. Nothing in the settings layer limits it to "new" offers. Per the label ("Dodawaj nowe oferty jako draft") it is intended for creating, but the DTO factory is shared with the update path (UpdateProductToWordpressAction), which also uses WooProductDTO (inferred, the DTO factory was not traced from every caller). Tag: inferred.
- Evidence: app/Services/ApiService/DTO/WooProductDTO.php:70. ApiSettings.php (Toggle). Added in commit e7b034d 2025-01-19 "handled saving products as draft".

### `woo-offers-default-user` — owner of offers imported from WooCommerce
- Tag: confirmed · Impact: normal
- What happens: the select "Uzytkownik dla zaczytanych ofert" is `required`. It is the only key stored on the WordPress form. It is used as `user_id` of each `Offer::create` during the Woo product import, and as `user_id` of `OfferReservation` created for products reported as reserved. Fallback: `?? 1` (user id 1) when the key is absent, in the form default and at all three read sites. Nothing validates that user 1 exists.
- Evidence: app/Services/ApiService/Jobs/FetchWooProducts.php:114, :147 (and :142, a commented-out line). ApiSettings.php (Select, default `?? 1`).
- The `OfferSale` branch tests `$status == 'Sprzedana'`, whereas `$status` was set from `OfferStatus::Sold->value`. Whether those match depends on the enum value, which I did not check (see Leads).

### "Pobierz Oferty z Woocommerce" button
- Tag: observed · Impact: high (bulk create of offers)
- What happens: it calls `WoocommerceApiService::fetchProducts()`. This clears the six cache keys (`cars_fetched`, `producer_fetched`, `equipment_fetched`, `colors_fetched`, `offer_types_fetched`, `products_fetched`) and dispatches `FetchWordpressDataOrchestrator`. The orchestrator runs the taxonomy jobs, then `FetchWooProducts::dispatch(1)` (paged import). It returns true, which shows "Udało się!" even though the import hasn't run yet. With an empty config it returns null, which shows "wystąpił błąd" (plus the notification from ensureClientIsDefined). The import itself I did not trace (out of scope).
- Evidence: ApiSettings.php (fetch_woocommerce action). WoocommerceApiService.php::fetchProducts. app/Services/ApiService/Jobs/FetchWordpressDataOrchestrator.php:56.

### Otomoto: configured only from env, not from the settings table
- Tag: confirmed · Impact: high
- What happens: `OtomotoAuthorizationService` reads `env('OTOMOTO_URL')`, `env('OTOMOTO_CLIENT')` and `env('OTOMOTO_SECRET')` (HTTP Basic client auth), plus `env('OTOMOTO_EMAIL')` and `env('OTOMOTO_PASSWORD')` for an OAuth password grant at `oauth/token`. The token is cached under `otomoto_authorization_token` with expiry less 5 minutes (a refresh occurs when under 5 minutes remain). `OtomotoApiService` uses `env('OTOMOTO_URL')` as the base URI and `env('OTOMOTO_EMAIL')` as the User-Agent header. On a 401 it force-refreshes the token and retries once.
- Failure: a missing token logs "Otomoto API token missing" and returns null from the request. A ClientException during the token request is logged and `report()`ed, and null is returned. Non-Client exceptions (connection errors) are not caught there. A null `OTOMOTO_URL` assigned to a `string $baseUrl` property raises a TypeError at construction (inferred: typed property, no default).
- `env()` is used outside config/, so with `php artisan config:cache` in production all of these return null (a well-known Laravel behaviour). Tag: defect? There is no OTOMOTO_* entry in config/services.php or .env.example (grep found none).
- Dedicated log channel: `otomoto` (storage/logs/otomoto.log, level `env('OTOMOTO_LOG_LEVEL','debug')`), config/logging.php:70-73.
- Evidence: app/Services/OtomotoService/Api/OtomotoAuthorizationService.php:20,58,64-65. OtomotoApiService.php:23,40. config/logging.php:70-73. Introduced 2025-05-02 e23c381 "authorization service to api" (git log -S OTOMOTO_URL).

### The ApiSettings "otomoto" form is dead
- Tag: confirmed · Impact: normal
- `otomotoSettingsForm()` exists, but it is absent from `getForms()` and the Blade view, and it contains only a Shout warning. The commented schema listing `otomoto_client_key/_secret_key/_client_email` was never wired in. No `otomoto_*` key exists in the settings seeder or any reader.
- Evidence: ApiSettings.php (getForms returns 3 forms). resources/views/filament/clusters/settings/pages/api-settings.blade.php. Repo-wide grep for `otomoto_client` finds only the comment.

### Other external-integration config
- Tag: confirmed · Impact: normal
- `WP_DEFAULT_IMAGE_ID` (env, direct) is the featured image for Woo products when an offer has none: SyncProductToWordpressAction.php:82 and UpdateProductToWordpressAction.php:60 (added 2025-05-30, 8b55e9f/7e4d60f). Unset means `addFeaturedImage(null)` (behaviour not traced).
- `APP_ENV` is read through `env()` to change behaviour: in `local` Car's WP endpoint is 'vehicle' (vs 'vehicles'). Several Otomoto jobs skip or alter image handling when local. FetchWooProducts.php:200 has a local branch.
- Mail: standard config/mail.php, default `MAIL_MAILER=log`, no app-specific wiring. Filesystems: stock config/filesystems.php with an s3 entry. config/services.php is stock. No SMS and no payment gateway code found.
- Evidence: the listed files and grep `env\(` over app/, routes/, database/.

### Seeder: the only link between env and the settings table
- Tag: confirmed · Impact: normal
- `DatabaseSeeder` calls `Setting::setValue` for `woocommerce_site_url` (env WOO_URL), `woocommerce_client_key` (WOO_KEY), `woocommerce_secret_key` (WOO_SECRET), `wordpress_username` (WP_USER) and `wordpress_password` (WP_PASSWORD), with default ''. This runs only on `db:seed`. It does NOT seed `woo-offers-default-user` or `woocommerce_save_as_draft`. After seeding there is no fallback to env when the DB value is empty. The services read only the DB.
- Evidence: database/seeders/DatabaseSeeder.php:53-58.

### Other keys in the table (not integration)
- Tag: confirmed · Impact: normal
- `site_name`, `site_description` (GeneralSettings, autosave on blur, nothing saved if emptied), and the per-message `{type}_title` / `{type}_desc` keys (MessagesSettings, r03).
- Evidence: GeneralSettings.php:42-62. MessagesSettings.php:82-86,115.

## Verify

### (1) Are the woo*/wordpress* settings still live code paths? — confirmed live, with one dead entry point
- Reachable from pages: the ApiSettings page (save, test, fetch) is a Filament cluster page.
- Reachable from Offer model events: Offer `updated` leads to `afterUpdateSyncAction` and then `WoocommerceApiService::updateProduct` (Offer.php:225).
- Reachable from admin actions: delete-from-Woo bulk and row actions (UserReservedOffersPage:113, FullListOffer:163, AdminReservedOffersTable:85, ViewOffer:456, BulkRemoveFromWpAction:73).
- Reachable from OfferType model hooks and the OfferType view pages (OfferType.php:76, ViewOfferType.php:31,52), and from taxonomy models (IsOfferTaxonomyModel.php:60).
- Reachable from jobs: OfferUpdateOffersChunk.php:28, SynchOemCodeFromWordpressJob.php:31, and the FetchWordpress* jobs.
- Reachable from an artisan command: GetOemsFromWordpressOffers.
- Not on the scheduler: routes/console.php schedules only `UpdateFullDataOfActiveAdvertsCommand` (Otomoto) and the queue worker. No Woo or WP job is scheduled.
- Dead: `ApiController::test` (dd of testConnection) has no route (routes/web.php and api.php do not reference it; grep of routes/app/bootstrap finds the class only in its own file). `SettingsSchemaBuilder` is imported in ApiSettings and MessagesSettings, but the ApiSettings use is unreferenced (the import only), so I treat it as unused there. Inferred for the MessagesSettings use (r03).
- History: the settings were introduced 2024-11-03 (fc09a5b "wordpress api service, rebuild settings page", 35918ac) and 2024-11-09 (68bdde1). The latest ApiService commits are 2025-09-27 (34b40e8, "change statuses on integrations after offer is archived") and 2025-10-16 (feb2ecc, "adjusted bulk synch job"). The key `woocommerce_site_url` has no removal in the `-S` log. The repo HEAD is 2026-10-31.
- Verdict: confirmed live.

### (2) GeneralSettings site_name / site_description
- Added: 2024-10-13 in 54d7fb7 "Settings Page and Util, Refactored Resources to Clusters" (git log -S site_name returns only this commit, the first settings commit, same day as the settings migration).
- Readers outside the page: none. A grep over app/, resources/, routes/, config/, database/ and lang for `site_name`, `site_description` and `siteName` finds only GeneralSettings.php. They are not used in Blade titles, mail or the panel brand. Not in the seeder either.
- Page oddity: the fields are labelled in English ("Site name", "Site description"), both `required`, and autosave on blur (`afterStateUpdated`), with no "Zapisz" button. Emptying a field does not clear the stored value.
- Verdict: added 2024-10-13; write-only (no consumer).

### (3) settings.user_id
- Defined in the 2024-10-13 migration as `foreignIdFor(User::class)->nullable()` (migration:18). `Setting` uses `$guarded = []`. No code sets or reads `user_id` on a Setting: `Setting::create(['key','value'])` is the only create. No `table('settings')` raw access in app/ or database/. The activity log tracks only key and value. `git log -S user_id` on the migration file returned no separate commit that I could attribute, so the column dates from the creating migration (2024-10-13 by file name).
- Verdict: never written or read (confirmed by grep; dead column). The column is nullable (foreignIdFor without constrained(), so no FK constraint).

## Contradictions and defects
- defect? Both "Test połączenia" buttons share the state key `woo_api_test`, and neither form defines it, so green/disabled state is per-form but the key is the same (the Woo state is not set by WordPress).
- defect? `Setting::value()` is a DB query per key per call. The DTO and import jobs call it per product.
- defect? Secrets are stored in plain text and, via the activity log on `Setting` (`logOnly key, value`), written again in the log table.
- defect? `findBySKU` and `FetchWooProducts::handle` dereference a possibly-null client.
- defect? Otomoto and WP_DEFAULT_IMAGE_ID use `env()` outside config (broken under config:cache).
- ambiguous: the ApiSettings Otomoto form and the commented schema suggest a plan to move Otomoto credentials into settings. It was never done (decide for V2).

## Leads outside scope
- app/Filament/Resources/OtomotoOfferResource/Pages/OtomotoOptions.php, the "Ustawienia Otomoto" page. It has its own options (default category mappings, position mappings, ShippingMethod, OtomotoData) and does not use `Setting`. It needs its own discovery.
- FetchWooProducts.php:130-150, where `$status == 'Sprzedana'` is compared against the `OfferStatus::Sold->value` assignment. Check the enum value.
- ApiService's `Notifier` (app/Services/Notifier) is how failures are shown to the user.
- MessagesSettings and SettingsSchemaBuilder (r03).

## Could not establish
- Whether a long-lived queue worker runs in production (stale singleton credentials). Nothing in the repo besides the scheduled `queue:work --stop-when-empty`. Deployment config would settle it.
- Whether a null text-input state reaches `setValue` and throws a TypeError. It needs a runtime run (not allowed).
- Whether production runs `config:cache`, which would break the Otomoto `env()` calls. Deployment knowledge.
- Library-level behaviour for wrong URLs (WooCommerce SDK).
- For (3): the exact commit that added the column (no git blame was run). The migration file name dates it 2024-10-13.

## Coverage ledger
- Searched: `Setting::`, `woocommerce_`, `wordpress_`, `woo-offers`, `site_name`, `site_description`, `user_id` in settings, `env(`, `OTOMOTO`, `WOO_`, `WP_`, `ApiController`, `WoocommerceApiService`, `WordpressApiService`, `fetchProducts(`; `git log -S` for woocommerce_site_url, site_name, woo-offers-default-user, OTOMOTO_URL, woocommerce_save_as_draft, WP_DEFAULT_IMAGE_ID.
- Examined: ApiSettings.php, api-settings.blade.php, Setting.php, settings migration, DatabaseSeeder (lines 40-60), ApiService.php, WoocommerceApiService.php, WordpressApiService.php, ApiServiceProvider.php, FetchWooProducts.php (36-155), WooProductDTO.php (55-80), GeneralSettings.php, SettingsSchemaBuilder.php (first 50 lines), OtomotoAuthorizationService.php, OtomotoApiService.php (1-80), ApiController.php, routes/console.php, config/logging.php (grep), Offer.php (200-230), WooCommerceSync.php (25-70).
- Matched, not examined: the other Otomoto services, actions and jobs (credential flow goes through the two Api services); the FetchWordpress* jobs and Actions (they only consume the services); OtomotoOptions.php (beyond the head); MessagesSettings (r03); config/mail.php and config/filesystems.php (stock, by grep only). routes/web.php and routes/api.php were checked only by grep for ApiController and settings.
