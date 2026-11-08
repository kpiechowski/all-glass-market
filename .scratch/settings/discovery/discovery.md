# Settings — discovery

<!-- Working document for the module-discovery skill. Only the manager writes it.
     Item ids are stable and never reused. Every finding cites a report (rNN) and its V1 evidence.
     Tags: confirmed · observed · inferred · ambiguous · unknown · defect? · decide -->

## A. Metadata

- **Status:** done (no-module outcome)
- **Next actions:** none. Outcome in `.scratch/settings/decision.md` and ADR 0007; carried items go to WooCommerce and Offers discovery.
- **Started:** 2026-11-08 · **Last updated:** 2026-11-08
- **V1:** `/home/kp/laravel/projekty/AllGlass/` @ `511ce3b` (dirty: `docker-compose.yml` modified, `SECURITY_INCIDENT_2026-10.md` untracked; neither touches settings code)
- **V2 state:** module absent. Related: `app/Modules/Preferences` (per-user preferences, ADR 0006).
- **Prior material:** `docs/archive/v1-port/modules/settings/scan.md` (leads only, `inferred`); roadmap `.scratch/roadmap.md` row 4.

### Framing (from the user, 2026-11-08)

Settings was a failure in V1 and is **not** ported 1:1. The first grilling question is whether V2 needs a Settings module at all, and in what form, or whether it should be postponed until more modules exist. For every setting that is really used, the candidate homes are: config/.env, a code constant, a user preference (Preferences), an integration setting (`app/Integrations`), data of the module it concerns, or an admin-editable panel setting. The outcome is either a small `functional-spec.md` or a "no module" ADR with a map V1 setting → V2 home. The user also asked that the first question be informed by good-practice standards for storing settings (r05).

### Scope

- **Apparent responsibility:** a key/value store of global settings with admin pages to edit them (API credentials, general site info, message templates).
- **Anchors:** `app/Models/Setting.php`, `database/migrations/2024_10_13_111544_create_settings_table.php`, `app/Filament/Clusters/Settings/Settings.php`, `app/Filament/Clusters/Settings/Pages/{ApiSettings,GeneralSettings,MessagesSettings}.php`, `app/Filament/Utils/SettingsSchemaBuilder.php`, `database/seeders/DatabaseSeeder.php` (settings part).
- **Vocabulary:** `Setting`, `App\Models\Setting`, `settings` table, `Setting::value`, `Setting::setValue`, `SettingsSchemaBuilder`, cluster `Settings`; keys `site_name`, `site_description`, `woocommerce_site_url`, `woocommerce_client_key`, `woocommerce_secret_key`, `woocommerce_save_as_draft`, `wordpress_username`, `wordpress_password`, `woo*` (dynamic, in `FetchWooProducts`), dynamic message keys in `MessagesSettings`; column `user_id`; Polish labels TBD (r01).
- **Boundary assumptions:** `Shield/RoleResource` in the cluster belongs to Users/authorization and is dropped (Shield removed 2026-11-08, ADR 0004). Activity logging on `Setting` is replaced by Audit, if anything.
- **Out of scope:** roles and permissions UI (Users); the behaviour of WooCommerce/WordPress/Otomoto integrations beyond which settings they consume (integrations, roadmap #8–9).
- **Limitations:** static reading only; production setting values and which keys actually hold data are `needs-runtime`.

## B. Module overview

V1 Settings is a string-only key/value table (`settings`: key, value varchar(255) NOT NULL, unused nullable `user_id`, no unique index on key) edited in a Filament cluster "Ustawienia" with three pages. 15 keys exist: 2 on "Ogólne", 7 on "Ustawienia Api", 6 on "Ustawienia powiadomień" (r01, manager count from page source). **Only the 7 API keys are live**, all of them configuration of the WooCommerce/WordPress integration (r02, r04). The 8 other keys are written by their pages and read by nothing (r02, r03, r04). The cluster is reachable by exactly one hard-coded admin e-mail (r01, spot-checked). Other configuration of the system lives outside the table: Otomoto credentials in `.env` read via `env()`, `WP_DEFAULT_IMAGE_ID` in `.env`, VAT 1.23 hard-coded, `config/otomoto.php`, and a second key/value store `otomoto_data` (r02, r04).

In business terms, V1 Settings is "where an admin pastes the WooCommerce/WordPress connection and picks who owns imported offers", plus two unfinished features (site identity, editable notification texts).

## C. Functional inventory

| ID | Functionality | Entry points | Workflow | Verified | Confidence | Open |
|---|---|---|---|---|---|---|
| F-01 | Configure the WooCommerce/WordPress connection | page Ustawienia Api | W-01 | spot / r04 | high | Q-01, Q-02 |
| F-02 | Choose the default owner of offers imported from WooCommerce | page Ustawienia Api (`woo-offers-default-user`) | W-02 | spot / r02, r04 | high | Q-03 |
| F-03 | Choose whether products pushed to WooCommerce are saved as drafts | page Ustawienia Api (`woocommerce_save_as_draft`) | W-01 | r01, r04 | high | — |
| F-04 | Test the WooCommerce and WordPress connection | 2 "Test połączenia" actions | n/a: display-only check, no state change | r01, r04 | med | Q-04 |
| F-05 | Fetch offers from WooCommerce on demand | action "Pobierz Oferty z Woocommerce" | out of scope: WooCommerce integration behaviour | r01 | med | — |
| F-06 | Edit site name and description | page Ogólne | n/a: dead, nothing reads it | r02, r04 | high | D- pending |
| F-07 | Edit notification texts for offer created/accepted/rejected | page Ustawienia powiadomień | n/a: dead, nothing reads it | r02, r03 | high | D- pending |

### F-01 Configure the WooCommerce/WordPress connection

- **Purpose:** connect the back office to the shop (WooCommerce REST) and its WordPress media/posts API without a deploy.
- **Description:** an admin enters shop URL, Woo client key, Woo secret key, WordPress username and password; values are stored as plaintext strings. WordPress reuses the shop URL; there is no separate WP URL.
- **Actors and permissions:** only a user who is admin AND has one hard-coded e-mail (`Settings.php:17`); Shield page permissions on top (r01).
- **Evidence:** r01, r04; `ApiSettings.php:113–200`, `WoocommerceApiService.php:68–70`, `WordpressApiService.php:40–42`.
- **Status:** traced · **Confidence:** high.

### F-02 Default owner of imported offers

- **Description:** offers created by `FetchWooProducts` get `user_id` = this setting, falling back to user 1 when empty. No validation that the user exists (r04).
- **Evidence:** `FetchWooProducts.php:114,147`; `ApiSettings.php:142–150`.

### F-06 / F-07 Dead pages

- Site name/description added 2024-10-13 (54d7fb7), never read (r04). Journal message keys added in c28e600 (2024-12-05) together with JournalAction, never wired; real journal/notification texts are hard-coded in JournalAction subclasses (r03).

## D. Business rules and domain knowledge

### Confirmed (`confirmed` / `observed`)

- **BR-01** Woo/WP clients are built once per process from settings; if any credential is empty, every Woo/WP call shows "Błąd połączenia z api" and returns false/null — except `FetchWooProducts::handle` and `findBySKU`, which fail hard (r04).
- **BR-02** Imported offers are owned by the configured default user, else user id 1 (r04).
- **BR-03** VAT multiplier 1.23 is a code constant (`OfferPriceService.php:19`), companies and owner-less offers exempt (r02) — not a setting; owned by Offers.

### Inferred or questionable

- **BR-04** Saving an empty text field on the API or messages page may throw a TypeError (`setValue(string $value)`), mid-loop and without a transaction — `inferred` (r02, r04); G-01.

### Domain terms

- "Ustawienia" (cluster), "Ogólne", "Ustawienia Api", "Ustawienia powiadomień"; "Test połączenia"; "Pobierz Oferty z Woocommerce".

## E. Functional workflows

### W-01 Integration configuration reaches WooCommerce/WordPress

- **Trigger:** admin saves a section on Ustawienia Api.
- **Main flow:** 1. each field is upserted read-then-write into `settings`. 2. Next process that resolves the Woo/WP singleton reads the keys in its constructor. 3. Offer hooks, delete actions, OfferType hooks, jobs and a command call the services (r04 verify 1).
- **Side effects:** the activity log stores old/new values, including secrets (r01).
- **Error scenarios:** BR-01; BR-04; long-running workers keep old credentials until restart (`inferred`, r02).
- **Alternative paths:** the seeder copies `WOO_*`/`WP_*` env values once; no runtime fallback (r04).
- **Evidence:** r04.

### W-02 Default owner applied on import

- `FetchWooProducts` reads `woo-offers-default-user` per created offer (r04). Detailed import behaviour belongs to WooCommerce discovery.

## F. Cross-module dependencies

- **X-01** WooCommerce/WordPress integration ← reads all 7 live keys (r02, r04).
- **X-02** Offers ← F-02 sets the owner of imported offers; VAT constant lives in Offers code (r02).
- **X-03** Otomoto integration — configured outside Settings (env + `config/otomoto.php` + `otomoto_data`); no dependency on the settings table (r02, r04).
- **X-04** Users/authorization — cluster also hosts Shield RoleResource (dropped, ADR 0004).

## G. Ambiguities, contradictions and questionable behaviour

### Q-01 Plaintext secrets, duplicated into the activity log — `defect?`

- **Evidence:** r01, r02, r04. **Question:** where do credentials live in V2 (env vs DB encrypted), and may an admin change them at runtime? **Resolution:** open

### Q-02 Otomoto reads `env()` outside config — `defect?`

- **Evidence:** `OtomotoAuthorizationService.php:20,58,64` (spot). Breaks under `config:cache`; keys undocumented in `.env.example`. **Resolution:** open (Otomoto discovery; noted here for the map)

### Q-03 Default owner of imported offers falls back to user 1 unvalidated — `defect?`

- **Evidence:** r04. **Question:** is a configurable default owner needed, or is the importing actor / a fixed system user enough? **Resolution:** open

### Q-04 Test-connection buttons share one state key and test saved, not entered, values — `defect?` / `inferred`

- **Evidence:** r01, r04. **Resolution:** open (WooCommerce integration)

### Q-05 Report counts disagree (r01 "9 API keys", r02 "13 keys, 6 dead", r04 "8 keys") — contradiction, settled

- **Resolution:** manager count from `ApiSettings.php` source: 7 API keys; total 15, 8 dead.

## H. Missing information and gaps

| ID | What is unknown | Kind | Why | Resolution |
|---|---|---|---|---|
| G-01 | Does saving an empty field throw (BR-04)? | needs-runtime | static reading only | open — irrelevant if pages are not ported |
| G-02 | Which keys hold values in production | needs-runtime | no data access | open |
| G-03 | Whether Shield page permissions and the e-mail check combine | not-investigated | moot: both dropped | dismissed |

## I. Potential improvements

- **IMP-01** Credentials out of the DB: integration secrets in env + config, owned by each integration (r05; Q-01). decision: open
- **IMP-02** Otomoto config through `config/` instead of `env()` in services, keys documented in `.env.example` (Q-02). decision: open (Otomoto)
- **IMP-03** No generic key/value store; any future runtime setting is a code-defined, typed definition owned by the consuming module (r05, ADR 0006 pattern). decision: open

## J. Additional functionality to implement

none identified yet

## K. Investigation log

| Report | Mission | Scope / question | Status | Key results | Limits | Follow-ups | Checked |
|---|---|---|---|---|---|---|---|
| r01 | local | anchors: every key, field, validation, default, access, encryption | returned | 15 keys on 3 pages (Ogólne 2, Api 9, powiadomienia 6 dynamic... incl. `woo-offers-default-user`); plaintext secrets also in activity log; no unique key; varchar(255); access hard-coded to one admin email; SettingsSchemaBuilder + otomotoSettingsForm dead | test-connection behaviour inferred | r04 | spot: `Settings.php:17` canAccess email ✓ |
| r02 | cross-repo | all keys + hidden settings (config/env/constants) + census | returned | 7 live keys (all Woo/WP, read by ApiService classes + FetchWooProducts); site_name/site_description + 6 journal keys dead; user_id unused; 11 census rows, no routes/commands/schedules; ~25 hidden settings (Otomoto creds env-only, WP_DEFAULT_IMAGE_ID, VAT 1.23 hard-coded, config/otomoto.php, `otomoto_data` 2nd k/v store, APP_ENV switches) | its headline count "13 keys, 6 dead" is an arithmetic slip; manager count from page source: 15 keys = 2 general + 7 API + 6 journal, 8 dead | otomoto_data → Otomoto discovery | spot: `OfferPriceService.php:19` 1.23 ✓; ApiSettings field list (7 keys; r01's "9" was a miscount) ✓ |
| r03 | flow | MessagesSettings: templates and their consumers | returned | 6 keys `journal_{created,accepted,rejected}_{title,desc}`, no consumer ever (added c28e600, never wired); real texts hard-coded in JournalAction subclasses | other 7 Journal actions only grepped | — | spot: grep `journal_` outside cluster = 0 hits ✓ |
| r04 | flow + verify | integration credentials → Woo/WordPress services/jobs; Otomoto config source; liveness | returned | Woo/WP singletons read creds in constructor; empty → soft error "Błąd połączenia z api", except FetchWooProducts::handle + findBySKU (hard fail); WP reuses woocommerce_site_url; Otomoto reads env() directly (5 keys); seeder copies env → settings once, no fallback; verify: Woo/WP keys live (offer hooks, actions, jobs, command), site_* dead, user_id dead | count "8 keys" off by one (7); tags not tallied; no census | — | spot: `OtomotoAuthorizationService.php:20,58,64` env() ✓ |
| r05 | research (non-V1) | good practice for storing app settings; when a settings module pays off | returned | taxonomy → homes (env/config, code, Preferences, typed DB store, Pennant, owning module); secrets out of DB for a small team (APP_KEY co-located, dumps/audit diffs); generic k/v = V1 failure mode; spatie/laravel-settings clashes with "every write is a command"; ADR-0006-style definitions as the middle path; triggers to build later: ≥3–5 non-secret values non-devs must change, change rate > deploy rate, ≥2 modules wanting one screen | non-V1, input only; its recommendation is not a decision | — | sources cited (12factor, Laravel docs, OWASP, spatie) |

## L. Completeness assessment

- **Exit criteria:** 1 ✓ (r02 census, 11 rows: all map to F-01…F-07 or are seeder/model hook) · 2 ✓ (F-04/F-06/F-07 display or dead, reasons given; F-05 out of scope) · 3 ✓ (SettingsSchemaBuilder, otomotoSettingsForm examined: dead) · 4 ✓ (leads: `otomoto_data`, `config/otomoto.php`, APP_ENV switches → Otomoto/WooCommerce discovery; JournalAction hard-coded texts and `PanelNotification::getRecipients` stub → Offers discovery; listed in the V1→V2 map at grilling) · 5 ✓ (Q-05 settled) · 6 ✓ (spot-checks on access, dead journal keys, VAT, Otomoto env) · 7 ✓ accepted exception: one wave only; the module is 570 lines and four independent missions agree on the key set · 8 ✓
- **Mapped:** 7 F, 4 BR, 2 W, 4 X, 5 Q, 3 G, 3 IMP, 0 ADD.
- **Verified:** live/dead status of every key (r02 + r04 verify + spot).
- **Still uncertain:** Q-01…Q-04 (decide), G-01, G-02 (needs-runtime).
- **Weak coverage:** test-connection behaviour (inferred); not important if pages are not ported.
- **Ready because:** the question for grilling is placement, not behaviour; every key has a known reader set.

## M. Decision log

- **D-01** (2026-11-08) re B, r05 — V2 builds **no Settings module now**. Nothing live in V1 Settings needs an admin-editable store before the WooCommerce integration exists. Class: exclude. Rationale: user — "settings are pointless for now"; only the 7 Woo/WP keys are live and the integration comes after Offers.
- **D-02** (2026-11-08) re X-03, Q-02 — Otomoto is out of the V2 plan; a new integration replaces it at the very end. Its env keys, `config/otomoto.php` and `otomoto_data` are not mapped. Class: exclude. Rationale: user.
- **D-03** (2026-11-08) re F-06, F-07, G-01, G-03 — drop site name/description, the 6 journal message keys, the hard-coded e-mail access and `settings.user_id`. Class: exclude. Rationale: user — dead in V1, "wywalamy".
- **D-04** (2026-11-08) re F-01, Q-01, IMP-01 — integration credentials live in `.env` read only through `config/` (`config/services.php`), owned by the integration; not admin-editable, not in the DB. Class: correct. Rationale: user agrees with env → config for WooCommerce/WordPress (r05).
- **D-05** (2026-11-08) re F-02, F-03, F-04, F-05, Q-03, Q-04 — draft option, default owner of imported offers, connection test and on-demand fetch move to WooCommerce discovery. Class: pending.
- **D-06** (2026-11-08) re r02 hidden settings, G-02 — VAT stays a business constant of Offers; `WP_DEFAULT_IMAGE_ID` and `APP_ENV` switches become WooCommerce config; the V1 `settings` table is not migrated (no data-migration ticket). Class: correct. Rationale: user "tak".
- **D-07** (2026-11-08) re IMP-03, Q3 — record the placement rule as ADR 0007 framed positively: settings belong to their owner; a typed, code-defined admin-editable setting (Preferences shape) or a shared Settings module stays possible when the signals appear; no generic key/value store; secrets never in the DB. Class: new. Rationale: user — "priorytetem są ustawienia per module … nie zamykajmy się".
- **D-08** (2026-11-08) re roadmap — row 4 Settings → decided (ADR 0007); row 9 Otomoto → dropped, a new integration at the very end. Class: correct. Rationale: user "ok".

