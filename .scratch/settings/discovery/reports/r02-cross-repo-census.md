# r02 — cross-repo: Settings census and hidden settings

Question: Where in V1 are settings read or written, which are dead, which hidden settings exist · V1 commit: 511ce3b

## Summary
- The `settings` table has 12 distinct keys in use. Only 6 have a live reader: the five Woo/WP credential keys and `woocommerce_save_as_draft`. Add `woo-offers-default-user`, making 7 live.
- `site_name`, `site_description` and the 6 journal message keys (`journal_{created,accepted,rejected}_{title,desc}`) are written by admin pages and read by nothing except the edit forms. They are dead (write-only). No consumer exists for the messages, so r03 will find none.
- `settings.user_id` is never written or read anywhere. Dead column.
- Hidden settings are numerous. Credentials and URLs for Otomoto live only in `.env`. VAT 1.23 is a hard-coded constant. The Otomoto category tree lives in `config/otomoto.php`. A second key/value store (`otomoto_data`) holds Otomoto defaults and mappings. See the Part 2 inventory.
- There are no tests, routes, API endpoints or console commands touching settings. All access goes through 3 Filament pages, 2 services, 1 job, 1 DTO and 1 seeder.

## Findings

### Settings are read through a per-call DB query with no cache
- Tag: confirmed · Impact: normal
- What happens: `Setting::value($key)` runs `where key = ? first('value')` and returns string|null. `setValue` is create-or-update by key. There is no cache, so every read is a query. Services are singletons (ApiServiceProvider), so the constructor reads of Woo/WP credentials happen once per process. A queue worker, which is long-lived, keeps the old credentials until restart. That last point is `inferred` (singleton plus constructor read; no worker-restart logic was found).
- Evidence: app/Models/Setting.php:13-30; app/Providers/ApiServiceProvider.php:20-21; app/Services/ApiService/WoocommerceApiService.php:68-70; WordpressApiService.php:40-42.

### Empty form fields likely fail on save
- Tag: defect? · Impact: normal
- What happens: all three pages save with `foreach ($form->getState() as $field=>$value) Setting::setValue($field,$value)`. `setValue(string $key, string $value)` is non-nullable. If a cleared TextInput yields null, PHP throws a TypeError, because there is no `declare(strict_types)` coercion for null. Whether Filament returns null or '' for an empty field was not run. `Toggle` false→"" and true→"1" by scalar coercion. Hence the DTO check `=== "1"`. The `value` column is `string NOT NULL`.
- Evidence: ApiSettings.php:231-236; MessagesSettings.php:112-117; Setting.php:20; migration 2024_10_13_111544:16.

### Draft toggle
- Tag: confirmed · Impact: high (it decides whether new offers are published to the public shop)
- What happens: `woocommerce_save_as_draft === "1"` makes a product go to Woo as `draft`, otherwise `publish`. Missing key (never saved) means publish.
- Evidence: app/Services/ApiService/DTO/WooProductDTO.php:70.

### Default owner for offers imported from Woo
- Tag: confirmed · Impact: normal
- What happens: `woo-offers-default-user` (key with hyphens, the only one) is chosen in ApiSettings ("Uzytkownik dla zaczytanych ofert", required, options = all users). FetchWooProducts uses it as `user_id` of imported offers, falling back to 1. It is not seeded. The form default is 1.
- Evidence: ApiSettings.php:144-150; app/Services/ApiService/Jobs/FetchWooProducts.php:114,147 (:142 is commented out).

### Settings cluster has a second, unrelated Shield role resource
- Tag: confirmed · Impact: normal
- What happens: `app/Filament/Clusters/Settings/Resources/Shield/RoleResource*` lives in the cluster. It matches the vocabulary only via the word "settings" and does not use the settings table. Role permissions are a Module concern for Users/Roles, not Settings.
- Evidence: file list (RoleResource, Pages/{Create,Edit,List,View}Role).

## Per-key table
| Key | Writers | Readers | Live? |
|---|---|---|---|
| woocommerce_site_url | ApiSettings:184 form; DatabaseSeeder:53 (env WOO_URL) | WoocommerceApiService:70; WordpressApiService:40 (WP reuses the Woo URL) | live |
| woocommerce_client_key | ApiSettings:191; Seeder:54 (WOO_KEY) | WoocommerceApiService:68 | live |
| woocommerce_secret_key | ApiSettings:198; Seeder:55 (WOO_SECRET) | WoocommerceApiService:69 | live |
| woocommerce_save_as_draft | ApiSettings:202 | WooProductDTO:70 | live |
| wordpress_username | ApiSettings:133; Seeder:57 (WP_USER) | WordpressApiService:41 | live |
| wordpress_password | ApiSettings:140; Seeder:58 (WP_PASSWORD) | WordpressApiService:42 | live |
| woo-offers-default-user | ApiSettings:150 | FetchWooProducts:114,147 | live |
| site_name | GeneralSettings:49 | none (only the form default, :42) | dead |
| site_description | GeneralSettings:62 | none (only the form default, :55) | dead |
| journal_created_title / _desc | MessagesSettings:115 | none (form default only, :82,86) | dead |
| journal_accepted_title / _desc | MessagesSettings | none | dead |
| journal_rejected_title / _desc | MessagesSettings | none | dead |
| settings.user_id (column) | none | none | dead |

Liveness of the live readers (reachable): the services are used by the ViewOffer delete action, UserReservedOffersPage bulk delete, OfferType model hooks, ViewOfferType, OfferUpdateOffersChunk, SynchOemCodeFromWordpressJob, WooCommerceSync, FetchWordpressDataOrchestrator and the ApiSettings "Test połączenia" actions. `FetchWooProducts` is dispatched from the orchestrator. The orchestrator is dispatched by WoocommerceApiService:217 and by the Fetch* jobs. Who starts the first run was not traced (r04).
Note: the "dynamic message keys" `{$type}_title` / `{$type}_desc` are built at MessagesSettings:82-86. No grep hit for `journal_created`, `journal_accepted`, `journal_rejected` outside that page.

## Entry-point census
| Kind | Path | Does | Reachable |
|---|---|---|---|
| Filament page (cluster Settings) | app/Filament/Clusters/Settings/Pages/GeneralSettings.php | edits site_name, site_description (writes on state update, :49,:62) | reachable (anchor, HasPageShield not checked) |
| Filament page | .../Pages/ApiSettings.php | edits WP/Woo credentials, draft toggle, default user; "Test połączenia" buttons | reachable |
| Filament page | .../Pages/MessagesSettings.php ("Ustawienia powiadomień") | edits 6 journal message keys | reachable page, output unused |
| Util | app/Filament/Utils/SettingsSchemaBuilder.php | imported as SBuilder in ApiSettings:22 and MessagesSettings:21; no call found | imported only, `unreachable` (not examined; r01) |
| Service ctor | app/Services/ApiService/WoocommerceApiService.php:68-70 | reads 3 Woo keys | reachable (singleton) |
| Service ctor | app/Services/ApiService/WordpressApiService.php:40-42 | reads Woo URL and WP credentials | reachable |
| Queue job | app/Services/ApiService/Jobs/FetchWooProducts.php:114,147 | reads default user for imported offers | reachable via orchestrator |
| DTO | app/Services/ApiService/DTO/WooProductDTO.php:70 | reads draft flag when building product | reachable via SyncProductToWordpressAction/UpdateProductToWordpressAction, WooSyncBulkAction |
| Action | app/Services/ApiService/Actions/SyncProductToWordpressAction.php | `use Setting` imported; no call found | unused import |
| Seeder | database/seeders/DatabaseSeeder.php:53-58 | seeds 5 credential keys from env WOO_*/WP_* | manual (`db:seed`) |
| Model hook | app/Models/Setting.php | LogsActivity on key and value; changes are written to activity_log, including secrets in plain text | active on every write |
| Migration | database/migrations/2024_10_13_111544_create_settings_table.php | creates table (key, value string NOT NULL, nullable user_id, timestamps; no unique index on key) | n/a |
No settings routes (routes/api.php has only /user; routes/web.php is a redirect), no Livewire component, no console command, no schedule, no listener, no policy, no test, no factory. Exports and global search: none found. `ListOtomotoOffers` has an Action named 'settings' ("Ustawienia Otomoto") that links to OtomotoOptions; it is not the settings table (see leads).

## Part 2 — hidden settings inventory
| Item | Defined | Read by | What it does |
|---|---|---|---|
| OTOMOTO_URL | .env (not in .env.example) | OtomotoAuthorizationService:20; OtomotoApiService:23 (env() outside config/) | Otomoto API base URL |
| OTOMOTO_CLIENT, OTOMOTO_SECRET, OTOMOTO_EMAIL, OTOMOTO_PASSWORD | .env | OtomotoAuthorizationService:58,64-65; OTOMOTO_EMAIL also as User-Agent, OtomotoApiService:40 | OAuth credentials, not editable in UI. The ApiSettings `otomotoSettingsForm` is only a warning Shout. |
| WP_DEFAULT_IMAGE_ID | .env | SyncProductToWordpressAction:82; UpdateProductToWordpressAction:60 | Fallback featured image for products |
| WOO_URL, WOO_KEY, WOO_SECRET, WP_USER, WP_PASSWORD, ADMIN_EMAIL, ADMIN_PASSWORD | .env | DatabaseSeeder:29-30,53-58 | One-time seed of settings and the super admin. Afterwards the DB wins. |
| OTOMOTO_LOG_LEVEL | .env, config/logging.php:70-75 | channel `otomoto` (storage/logs/otomoto.log) | Otomoto log |
| APP_ENV checks | .env | UpdateAdvertImages:42, ReUploadAdvertImageCollection:42, UploadAdvertImageCollection:34 (non-local uploads real images, local uses picsum.photos placeholders), FullDataAdvertUpdateJob:23, UpdateAdvertsDataJob:30 (commented), FetchWooProducts:200 (rewrites 'http://all-glass.test:8082' to 'http://wordpress' for local), Car.php:185,189 (post type 'vehicle' locally, 'vehicles' in production) | Environment-dependent behaviour |
| config/otomoto.php (custom file) | config | OtomotoOptions, CreateOtomotoOffer, OtomotoOfferForm, OtomotoSynchronizationTab, BulkCreateAdvertsAction, OtomotoParameterService, OtomotoCreationService | Otomoto taxonomy: main category 161 "Części", sub-category 163 "Części samochodowe", parts-type "karoseria", 7 parts-category options, 11 advert parameters with Polish labels and defaults (e.g. 'delivery' default true, 'disable_extract_donor_vehicle' default true) |
| `otomoto_data` table (OtomotoData, OtomotoMetadataService getMeta/setMeta) | DB, written by OtomotoOptions page and jobs FetchCategoryParamsJob / FetchSelectedCityData | OtomotoCreationService:172,203 and others | A second key/value store with keys: main_categories, regions, default_region_id, default_city_key, city_default_details, main_category_default, subcategory_default, otomoto_offer_type_mapping, otomoto_position_param_mapping, otomoto_shipping_mapping, phone, name. This is effectively Otomoto settings and V2 must place it. |
| VAT 1.23 | hard-coded, app/Services/Offer/OfferPriceService.php:19 | about 12 callers (Woo regular_price, Otomoto advert price, offer tables, forms) | Gross price = net x 1.23 rounded HALF_UP, unless the owner is null or a company account (`isCompanyAccount()`) |
| Currency 'PLN' | hard-coded, OtomotoService/DTO/AdvertDTO.php:67 (plus `gross_net` 'gross'); Number::currency(...,'PLN','pl') in tables | Otomoto advert price and table display | Currency |
| Scheduler | routes/console.php | `UpdateFullDataOfActiveAdvertsCommand` twiceDaily(6,16); `queue:work --queue=otomoto,default --stop-when-empty --timeout=500` every minute, withoutOverlapping, onOneServer | Timing as code |
| Job limits | code | UpdateFullData command chunk 50 with `delayMinutes` per chunk (:20-25); OfferUpdateService chunk 10; GlobalDescriptionGenerate chunk 50; UpdateAdvertsDataJob/RegenerateAdvertsDescriptionJob tries 3 timeout 300; BulkSynchProductToWooAction tries 3 timeout 120; OtomotoApiService maxRetries 1 (re-auth on 401); Woo/WP `per_page` from properties | Operational limits |
| WP REST path | WordpressApiService:45 | `/wp-json/wp/v2/` | URL constant |
| config/app.php | locale default 'pl' (env APP_LOCALE; .env.example says 'en'); timezone from APP_TIMEZONE | framework | Default differs from .env.example. Locale is set to pl in the code default only. |
| config/filament.php | 'locales' => ['pl'] | Filament | Panel locale |
| config/activitylog.php | `delete_records_older_than_days` 365; ACTIVITY_LOGGER_ENABLED | Spatie package | Audit retention (the retention only applies if `activitylog:clean` is scheduled; no schedule found) |
| config/versionable.php | keep_versions 0 | package | Versions kept forever |
| config/media-library.php | max_file_size 10 MB, MEDIA_DISK, MEDIA_QUEUE, QUEUE_CONVERSIONS_* | package | Upload limit |
| config/filament-activitylog.php | label "Logi", date format 'd/m/Y H:i:s' | package | UI settings (customised) |
| config/filament-edit-profile.php | show_custom_fields false | package | Profile |
| other config files | filament-shield.php, permission.php, auth.php, fortify.php, jetstream.php, sanctum.php, mail.php, session.php, cache, database, queue, filesystems, services | framework/packages | They match "settings" only by wording. Not examined for custom keys beyond a grep. |
| .env.example | app-specific keys: none. It is the stock Laravel 11 file (APP_*, DB_*, MAIL_*, AWS_*). | n/a | OTOMOTO_*, WOO_*, WP_*, ADMIN_*, WP_DEFAULT_IMAGE_ID are used by code but undocumented there. |
| PanelNotification::getRecipients | app/Services/NotificationService/PanelNotification.php:15-19 | returns [] with comment "logic from options" | A stub that hints the journal messages or recipients were meant to read from settings. Never implemented. |
Company data, e-mail addresses (other than env-driven) and margins: no hard-coded hits in app/ or config/ (grep for VAT/margin/PLN/zł/netto/brutto/e-mail domains). Absence is by grep only.

## Contradictions and defects
- Admin pages "Ustawienia powiadomień" and general settings persist values that no code consumes (confirmed by grep over app/, resources/, database/, config/). Journal messages are presumably meant for the "Mój dziennik" feature, but journal actions (app/Filament/Actions/Journal/*) were not opened here (r03).
- Woo key naming is inconsistent: `woo-offers-default-user` uses hyphens, the others use underscores.
- WordPress and Woo share the single `woocommerce_site_url`. There is no separate WP URL key.
- Secrets (`wordpress_password`, `woocommerce_secret_key`) are stored in plain text and logged in activity_log through LogsActivity (`logOnly(['key','value'])`). `revealable()` is restricted to super users in the UI only. `decide` for V2: encryption and log masking.
- `settings.key` has no unique index. `setValue` has a race window (read then create).

## Leads outside scope
- app/Filament/Resources/OtomotoOfferResource/Pages/OtomotoOptions.php: the "Ustawienia Otomoto" page (writes `otomoto_data` mappings). It should be in a discovery for Otomoto (r04).
- app/Filament/Actions/Journal/* and the "Mój dziennik" feature: possible intended consumer of the journal messages (r03).
- SettingsSchemaBuilder import without calls (r01 territory).
- Shield RoleResource inside the Settings cluster (Users/Roles module).
- Local-versus-production behaviour switches via `env('APP_ENV')` (picsum placeholder images, 'vehicle' vs 'vehicles' post type). Note for the Offers/Otomoto modules.

## Could not establish
- Whether empty fields throw a TypeError on save (needs runtime, or Filament getState semantics).
- Who dispatches the first FetchWordpressDataOrchestrator (not asked; r04).
- Whether `activitylog:clean` is scheduled: not in routes/console.php. A server cron is not visible from the repo.
- Whether HasPageShield permissions gate the three settings pages per role (ShieldSeeder not examined).

## Coverage ledger
- Searched (case-insensitive, short names and FQCNs): `Setting`, `Setting::`, `App\Models\Setting`, `'settings'`, `"settings"`, `settings`, `DB::table`, `Setting::value|setValue|where|first|all|find|create|updateOrCreate`, `getValue|setValue`, `SettingsSchemaBuilder|SBuilder`, `Settings` cluster, `site_name`, `site_description`, `woocommerce_`, `woo_`, `woo-`, `wordpress_`, `otomoto_`, `save_as_draft`, `woo-offers-default-user`, `journal_(created|accepted|rejected)`, `_title|_desc`, `{$type}`, `user_id` in settings, `env(`, `config('otomoto`, `config('services`, `https?://`, `vat|1.23|margin|PLN|currency|zł|netto|brutto`, `chunk|tries|timeout|per_page|maxRetries`, `getMeta|setMeta`, `OtomotoData`. Over app/, routes/, config/, database/, resources/, tests/, bootstrap/, composer.json.
- Examined: app/Models/Setting.php, OtomotoData.php, MessagesSettings.php, ApiSettings.php (read 120-245 plus greps), DatabaseSeeder.php, the settings migration, config/otomoto.php, routes/*.php, .env.example, OfferPriceService, WooProductDTO (greps), ApiServiceProvider, OtomotoMetadataService (head), PanelNotification, AdvertDTO:55-90, selected config values by grep.
- Matched, not examined: the ~90 files containing woo/wordpress/otomoto as business vocabulary (Offer, Car and Otomoto resources, jobs, actions, migrations for sync tables). These are consumers of the Woo and Otomoto services and belong to r03/r04. Files containing "settings" only as wording (config/session.php, fortify, jetstream, auth, welcome.blade.php, navigation-menu.blade.php, verify-email.blade.php, TwoFactor test names).
