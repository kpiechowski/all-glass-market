# r01 — local: Settings anchors

Question: What exactly can be configured through the Settings anchors, by whom, with what validation, defaults and storage guarantees? · V1 commit: 511ce3b

## Summary
- Settings is a `settings` table (id, key, value, user_id, timestamps) accessed only via `Setting::value($key)` and `Setting::setValue($key,$value)`. Every value is a string.
- 3 pages in the cluster "Ustawienia": "Ogólne" (2 keys), "Ustawienia Api" (8 keys across 2 forms plus a "fetch from WooCommerce" action), "Ustawienia powiadomień" (6 dynamic keys).
- Cluster access is hard-coded to one email (`kpiechowski25@gmail.com`) who must also be admin. Pages additionally use Shield `HasPageShield`.
- Validation is almost absent: only `required` on site_name, site_description and the default-user select. No URL or format validation. Secrets are stored in plaintext.
- No unique index on `key`, no DB-level guarantee; upsert is read-then-write (race).
- `Setting` uses Spatie activitylog on `key` and `value`, so plaintext secrets are also copied into the activity log.

## Findings

### Access: the whole cluster is limited to one hard-coded user
- Tag: confirmed · Impact: high (permissions)
- What happens: `Settings::canAccess()` returns true only if the user `isAdmin()` (role "Admin" or "Super Admin") AND email equals `kpiechowski25@gmail.com`. Nobody else sees the cluster navigation or pages. `HasPageShield` on each page adds a per-page Shield permission (`page_ApiSettings`, `page_GeneralSettings`, `page_MessagesSettings`). Whether it is ANDed with the cluster check is not traced (Filament cluster `canAccess` vs page `canAccess`).
- Evidence: app/Filament/Clusters/Settings/Settings.php:15-18 canAccess; app/Models/User.php:132-140 isAdmin/isSuper; Pages/*.php `use HasPageShield`; database/seeders/ShieldSeeder.php:15 grants `page_ApiSettings`, `page_GeneralSettings`, `page_MessagesSettings` to "Super Admin" only (role "Admin" gets only user permissions; "User" none).
- The cluster navigation sort is 99, icon heroicon-o-cog-8-tooth, title 'Ustawienia'.

### Setting keys table
Storage for all: `settings.value` string, NOT NULL (migration 'value' string, no nullable). Nothing is encrypted by the model or DB (no `encrypted` cast; no casts at all). "Masked" = UI only.

| key | page / label | field type | validation | default | encrypted / masked | who | page actions |
|---|---|---|---|---|---|---|---|
| site_name | Ogólne / 'Site name' (English label) | TextInput, prefix globe icon | `required` | `Setting::value('site_name') ?? ''` | no / no | cluster gate + page_GeneralSettings | no Save button; saved on blur (`live(onBlur)` + afterStateUpdated) via setValue. Empty state is ignored (not saved, old value stays). No notification. |
| site_description | Ogólne / 'Site description' (English label) | Textarea | `required` | `Setting::value(...) ?? ''` | no / no | same | same autosave on blur; empty ignored. Textarea stored in `string` (varchar 255) column. |
| woocommerce_site_url | Ustawienia Api, section 'WooCommerce Api' / 'Adres Url Woocommerce' | TextInput, globe icon | none (no url(), no required) | current DB value or null | no / no | cluster gate + page_ApiSettings | 'Zapisz' (saves all fields of this form), 'Test połączenia' |
| woocommerce_client_key | same / 'Klucz Klienta' | TextInput password | none | DB value | plaintext in DB / masked input; `revealable` only if `isSuper()` | same | same |
| woocommerce_secret_key | same / 'Sekretny Klucz Klienta' | TextInput password | none | DB value | plaintext / masked, revealable only for Super Admin | same | same |
| woocommerce_save_as_draft | same / 'Dodawaj nowe oferty jako draft' | Toggle | none | `Setting::value(...)` (string "1"/"0"/null) | no | same | same. Saved as bool cast to string: true->"1", false->"" or "0" (setValue is typed `string`; see defects). Consumer WooProductDTO.php:70 checks `=== "1"` for draft else 'publish'. |
| wordpress_username | Ustawienia Api, section 'Wordpress Api' / 'Login administratora wordpress' | TextInput, user icon, `live(onBlur)` + `debounce()` (no afterStateUpdated, so no effect) | none | DB value | no / no | same | 'Zapisz', 'Test połączenia' |
| wordpress_password | same / 'Hasło administratora wordpress' | TextInput password | none | DB value | plaintext / masked, revealable for Super Admin only | same | same |
| woo-offers-default-user | same (Wordpress form) / 'Uzytkownik dla zaczytanych ofert' | Select of all users (`User::all()->pluck('name','id')`) | `required` | `Setting::value('woo-offers-default-user') ?? 1` | no | same | stores the user id as string. Used as `user_id` of imported offers (Jobs/FetchWooProducts.php:114,147, fallback 1). Key uses hyphens, unlike all others. |
| journal_created_title | Ustawienia powiadomień, fieldset 'Oferta utworzona' / 'Tytuł' | TextInput | none | DB value or null | no | cluster gate + page_MessagesSettings | 'Zapisz' |
| journal_created_desc | same / 'Treść' | Textarea | none | DB value | no | same | same |
| journal_accepted_title | fieldset 'Oferta zaakceptowana' / 'Tytuł' | TextInput | none | DB value | no | same | same |
| journal_accepted_desc | same / 'Treść' | Textarea | none | DB value | no | same | same |
| journal_rejected_title | fieldset 'Oferta odrzucona' / 'Tytuł' | TextInput | none | DB value | no | same | same |
| journal_rejected_desc | same / 'Treść' | Textarea | none | DB value | no | same | same |

Section description of the messages page: 'Treść powiadomień dla zapisów w zakładce "Mój dziennik"' under section 'Powiadomienia dziennika'. API section descriptions: 'Dane wymagane do poprawnego połączenia i synchronizacji z panelem Wordpress' and 'Dane wymagane do poprawnego połączenia i synchronizacji produktów'.
- Tag: confirmed. Evidence: Pages/ApiSettings.php:128-202; Pages/GeneralSettings.php:39-64; Pages/MessagesSettings.php:62-88; WooProductDTO.php:70; FetchWooProducts.php:114,147.
- The "woo* keys in ApiSettings:150" from the brief is `woo-offers-default-user` (line 142-150). No other woo keys exist in the anchors.
- Keys read by the integration services but with no UI: none beyond the above. WordpressApiService.php:40 reads `woocommerce_site_url` (shares the Woo URL; no separate WP URL field).
- SettingsSchemaBuilder (key-driven TextInput builder with per-key autosave and notification 'Zapisano!') is not used by any of the three pages. The ApiSettings import is unused and the schema array at its bottom is commented out. It would also reference an `otomoto_*` key set (otomoto_client_key, otomoto_secret_key, otomoto_client_email) only in comments. `otomotoSettingsForm()` in ApiSettings exists but is not in `getForms()` or the view, so it is dead (Shout warning "Uwaga! Informacje poniżej są poufne. Nie udostępniaj ich osobom trzecim."). Tag: confirmed (grep for fromGroup consumers was limited to anchors; ApiSettings, GeneralSettings, MessagesSettings do not call it). Wider grep outside cluster not done (r02 scope).

### Save behaviour of the API and messages pages
- Tag: confirmed · Impact: high (external credentials)
- What happens: 'Zapisz' iterates `$form->getState()` and calls `Setting::setValue($field,$value)` for every field, then shows notification 'Zapisano'. There is no per-form validation message beyond `required`. Fields left empty are written (empty string), unlike the General page which ignores empty. A null value would hit `setValue(string $value)` with strict_types absent, so null is coerced to "" for scalars in non-strict mode; a boolean false becomes "" (and true "1"). Ambiguous exact stored value for false is inferred from PHP coercion.
- Evidence: Pages/ApiSettings.php:226-241; Pages/MessagesSettings.php:107-122; Models/Setting.php:22.
- Tag for the false coercion: inferred.

### Test connection and fetch actions (ApiSettings)
- Tag: confirmed · Impact: high (external systems)
- What happens: Both sections have a header button 'Test połączenia'. It calls `testConnection()` of WordpressApiService (WP form) or WoocommerceApiService (Woo form). On success it sets a `woo_api_test` state key, turning the button green with check icon and disabling it. On failure nothing is shown (no notification). The test uses the values currently stored in the DB (services read Setting in constructor), not the unsaved form values; so the user must save first. Both buttons use the same `woo_api_test` name, but each form has its own state path, so they do not affect each other. The state is lost on reload, and `woo_api_test` is not a schema field so is not saved. Section 'Akcje' has a button 'Pobierz Oferty z Woocommerce' that calls `WoocommerceApiService::fetchProducts()`; on truthy result notification 'Udało się!' else 'wystąpił błąd' (danger). Anyone with page access can trigger it.
- Evidence: Pages/ApiSettings.php:82-103,117-125,169-177. What the services do inside is r04 scope. The "reads DB values, not form values" claim: WordpressApiService.php:40-42 and WoocommerceApiService.php:68-70 read in constructor (grep hit only; not examined in full). Tag for that: inferred.
- The service instances are created in `getForms()` via `app()` and stored in static properties.

### Seeding of settings
- Tag: confirmed · Impact: high (secrets)
- What happens: DatabaseSeeder upserts five keys from env on every seed: woocommerce_site_url (WOO_URL), woocommerce_client_key (WOO_KEY), woocommerce_secret_key (WOO_SECRET), wordpress_username (WP_USER), wordpress_password (WP_PASSWORD), each defaulting to ''. Re-seeding overwrites UI-edited values with env values. No seed for site_name, site_description, journal_* messages, woocommerce_save_as_draft or woo-offers-default-user (so these default to null, the Select to user 1, the draft toggle to off/"publish").
- Evidence: database/seeders/DatabaseSeeder.php:53-58.

### Storage model
- Tag: confirmed
- Table `settings`: id, `key` string (varchar 255, NOT unique, not indexed), `value` string NOT NULL (varchar 255), `user_id` nullable foreignId (no constraint, `foreignIdFor` without `constrained()`), timestamps. Evidence: migration lines 14-19.
- `user_id`: never written. `Setting::create(['key','value'])` omits it and `$guarded=[]` makes it fillable, but nothing sets it. No `user()` relation in the model, no Auth in setValue. Effectively unused (probable intent: per-user settings or "last edited by"). Tag: confirmed for "never written by anchors"; "unused anywhere" not established (outside-anchor grep not done).
- Typing: `value()` returns `?string` (null when the key is missing), `setValue()` takes `string`. No casts. All consumers must compare strings (e.g. `=== "1"`). Value length is capped at 255 chars by the column (MySQL strict mode would throw on longer; SQLite does not enforce). Depends on DB driver: unknown.
- Hooks / logging: `Setting` uses `LogsActivity` with `logOnly(['key','value'])`; every create or update is logged to the activity log including the values of password and secret keys (plaintext). No other model events, observers or cache. `value()` does a DB query each call, no caching. Evidence: Models/Setting.php:13,36-40. Whether any observer is registered elsewhere: not checked.
- Upsert: `setValue` is `first()` then create/update, not atomic; with no unique index on `key`, concurrent saves can create duplicate rows, and `value()` then returns the first matched row (no ordering). Tag: defect?

## Contradictions and defects
- defect? Plaintext secrets: woocommerce_client_key, woocommerce_secret_key, wordpress_password stored unencrypted, also copied to the activity log (LogsActivity logs `value`). Password fields are only masked in the UI, and Super Admin can reveal them.
- defect? Pre-filled secret fields: the form `default()` puts the stored secret in the page's HTML/Livewire state, even if masked.
- defect? No unique index on `settings.key`; read-then-write upsert races.
- defect? `value` is varchar(255) NOT NULL: long message descriptions (journal_*_desc) or site_description may be truncated or fail; the model accepts no null.
- defect? Access check hard-codes a personal email in code; changing the admin is a code change.
- defect? General page: required fields, but empty values are silently not saved (no feedback; no Save button; no notification). The API and messages pages do write empty values.
- defect? `woocommerce_save_as_draft` toggle default is the raw string from DB (e.g. "0") assigned as the toggle default; "0" string may be truthy-checked inconsistently in Filament. Not verified.
- defect? Test connection result not shown on failure; uses DB-stored values rather than unsaved input. The 'Test połączenia' button in the Wordpress form tests the WP service, but its success state is stored under the name `woo_api_test` (copy-paste naming).
- defect? Label language inconsistent: 'Site name' and 'Site description' are English in a Polish UI.
- defect? Dead code: SettingsSchemaBuilder, otomotoSettingsForm, empty `create()` methods, commented schema.
- defect? Key naming inconsistent (`woo-offers-default-user` hyphens).
- The Wordpress service uses `woocommerce_site_url` as its base URL (WordpressApiService.php:40), so WP has no own URL setting.

## Leads outside scope
- Resources/Shield under app/Filament/Clusters/Settings/Resources/Shield (RoleResource, out of scope). Shield permissions for settings pages live in ShieldSeeder.php (and ShieldSeeder_old2.php).
- Consumers of journal_*_title/_desc and site_name/site_description (r03 / r02).
- WooProductDTO.php:70 and Jobs/FetchWooProducts.php:114,147 consume save_as_draft and default-user.
- Activity log viewing of Setting changes (secret leakage in the log UI).

## Could not establish
- Whether the cluster `canAccess` and Shield page permission combine (AND) for each page: needs Filament/Shield vendor code or runtime. The vendor code was not read.
- Exact stored value for an unchecked Toggle (""/"0"): runtime would settle it.
- DB driver / strict mode behaviour for >255-character values: config or runtime.
- Whether `user_id` or `SettingsSchemaBuilder` is used outside the anchors: r02.
- Whether the settings table has duplicate keys in production: only data inspection.

## Coverage ledger
- Searched: `Setting::` in app/Services; `woo-offers-default-user|woo_api_test`; `woocommerce_save_as_draft`; `isAdmin|isSuper` in User; `Setting` in database seeders, factories, tests, policies; grep "settings" in migrations.
- Examined: app/Models/Setting.php; the create_settings_table migration; Clusters/Settings/Settings.php; Pages/ApiSettings.php, GeneralSettings.php, MessagesSettings.php; Filament/Utils/SettingsSchemaBuilder.php; DatabaseSeeder.php settings part; resources/views/filament/clusters/settings/pages/api-settings.blade.php; User::isAdmin/isSuper; ShieldSeeder.php permission strings (grep output).
- Matched, not examined: WordpressApiService.php, WoocommerceApiService.php (r04); WooProductDTO.php, FetchWooProducts.php (r04); ShieldSeeder_old2.php; Resources/Shield (out of scope); general-settings and messages-settings blade views (not read); no Setting factory/tests/policy found.
