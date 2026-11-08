# r05 — Research: good practice for storing application settings

- **Mission:** research (not V1). Good practice for storing settings in a Laravel app, and when a dedicated Settings module is worth it.
- **Date:** 2026-11-08 · **Author:** research subagent · **Tags used:** `confirmed` means the cited source says it. `inferred` means my reasoning applied to AllGlass.
- **V2 context checked:** `docs/adr/0006` (Preferences: code-defined `PreferenceDefinition` classes, unknown keys rejected), `docs/adr/0004` (Authorizer + Actor), `composer.json` (laravel/framework ^13.39, filament/filament ^5.11, no spatie/laravel-settings, no Pennant). `app/Integrations` does not exist yet. `config/` has no WooCommerce, WordPress or Otomoto entries yet.

## 1. Kinds of "settings" and where each belongs

| Kind | Example in AllGlass | Good-practice home | Why |
|---|---|---|---|
| **Deploy-time config** | Woo site URL per environment, queue driver, mail host | `.env`, read **only** in `config/*.php`, then `config('x.y')` / `Config::string()` | 12-Factor: config is "everything likely to vary between deploys" and must be strictly separate from code ([12factor.net/config](https://12factor.net/config)). After `config:cache` "the `.env` file will not be loaded… you should ensure you are only calling the `env` function from within your configuration files" ([Laravel 12 Configuration](https://laravel.com/docs/12.x/configuration#configuration-caching)). Typed getters (`Config::string/integer/boolean`) throw when the type is wrong (same page). `confirmed` |
| **Secrets / credentials** | Woo consumer key/secret, WordPress app password, Otomoto API credentials | `.env` (optionally `env:encrypt` into the repo) or a secrets manager. Exposed through `config/services.php` | 12-Factor litmus test: the code could be open-sourced "without exposing any credentials". `.env` must not be committed; an encrypted `.env.encrypted` may be ([Laravel Configuration](https://laravel.com/docs/12.x/configuration#encrypting-environment-files)). OWASP prefers a dedicated secrets store over a general-purpose config store (§4.1) ([OWASP Secrets Management](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html)). `confirmed` |
| **Code constants / business rules** | VAT rate rules, status transitions, max photo count | A domain policy, enum or class constant in the module that owns the rule | 12-Factor: internal config that does not vary between deploys "belongs in the code". A rule that changes behaviour needs tests and review, so it should change through a deploy. `confirmed` / `inferred` |
| **Per-user preferences** | Quick-access tiles, hidden columns, default filters | The existing **Preferences** module (ADR 0006) | Already decided: code-defined definitions with defaults and validation, keyed per user. `confirmed` (repo) |
| **Runtime business settings an admin can edit** (global, or per tenant) | Message templates, "publish to Woo as draft" toggle, company name/footer on documents | A DB-backed **typed** store. Either typed settings classes (spatie/laravel-settings) or fields on the owning module's aggregate | These change without a deploy and are edited by non-developers, so the DB is the right place. They still need types, defaults, validation and an owner (section 3). `inferred` |
| **Feature flags** | Turn on Otomoto export for a pilot | Laravel Pennant (class-based features; `array` or `database` driver; scoped to a user or global) or simply a config boolean | Pennant is "a simple and light-weight feature flag package" ([Laravel 13 Pennant](https://laravel.com/docs/13.x/pennant)). Flags are temporary and belong to code, not to a settings screen. `confirmed` / `inferred` |
| **Reference data owned by a domain** | Glass categories, car makes, shipping methods, WooCommerce category mapping | That module's own tables, with CRUD through commands | These are records with identity and relations, not "settings". Putting them in a key/value table hides them from the domain. `inferred` |

## 2. Secrets: should API credentials be editable by admins in the DB?

**Arguments for DB-stored, admin-editable credentials:** a key can be rotated without a deploy or server access, a non-developer can do it, and it supports per-tenant credentials (not needed here).

**Arguments against, and the costs:**
- **Larger leak surface.** Every DB dump, backup, staging copy and SQL console now holds the secret. OWASP: "Encrypt backups and put them on secure storage with reduced access rights" (§2.9), "Secrets should never be retrievable by everyone and everything" (§6.3), and secrets must "Never be logged" (§8.3). An admin form also means the secret passes through Livewire state, validation errors, audit diffs and logs unless every path masks it. `confirmed` / `inferred`
- **Encryption at rest helps only partly.** Laravel's `encrypted` cast (and spatie's `encrypted()` properties) use AES-256 with a MAC, need a `TEXT` column, and "you will not be able to query or search encrypted attribute values" ([Eloquent casting](https://laravel.com/docs/12.x/eloquent-mutators#encrypted-casting)). The key is `APP_KEY`, which usually sits in `.env` on the same host as the DB. OWASP: "You should not store keys next to the secrets they encrypt" (§7.3). Whoever has the server or `.env` plus a dump has the plaintext. Encryption protects against a stray dump, not against a compromised host. `confirmed` / `inferred`
- **APP_KEY rotation gets harder.** `APP_PREVIOUS_KEYS` lets Laravel decrypt with old keys, but values stay encrypted with the old key until they are re-saved ([Encryption: rotating keys](https://laravel.com/docs/12.x/encryption#gracefully-rotating-encryption-keys)). A leaked `APP_KEY` therefore also means re-encrypting every stored secret **and** rotating the third-party credentials. OWASP: restarting the app does not revoke stolen credentials; revocation happens at the provider (§2.4). `confirmed`
- **Audit.** OWASP asks for tamper-resistant auditing of every read and update (§2.6). A DB setting can be audited through the Audit module, but the log must record *that* the value changed and never the value. `confirmed` / `inferred`
- **V1 evidence.** V1 kept WooCommerce/WordPress credentials as **plaintext** strings in a generic `settings` table with `$guarded = []` (`docs/archive/v1-port/modules/settings/scan.md`). This is the worst combination: in every dump, editable by anyone with panel access, and no type or owner. (An untracked `SECURITY_INCIDENT_2026-10.md` exists in the V1 repo; I did not check whether it is related.)

**Recommendation for a small internal team (`inferred`):** keep integration credentials in `.env` → `config/services.php` (`services.woocommerce.key`, …), read through a typed accessor inside the integration. Rotation is rare (a few times a year) and a developer is always involved, so "rotate without a deploy" saves little. Editing `.env` plus `config:cache` is a minute's work. Use `env:encrypt --readable` if the team wants credentials versioned. Move a credential into the DB (encrypted cast, masked write-only field, audit without the value) only when a real need appears, for example many shops with their own keys or an admin who must rotate without a developer. In the panel, show a read-only health check ("WooCommerce: connected / last error") instead of the secret.

## 3. Runtime settings that admins edit: three approaches

| Approach | Strengths | Weaknesses |
|---|---|---|
| **A. Generic key/value table** (`key`, `value` string) — V1 | Five minutes to build; one screen | Values are untyped strings (bools as `"1"`/`"true"`, JSON in strings). Keys are unguarded: any code or form can write any key, and typos create new keys silently. Keys go dead because nobody knows if a key is still read. No owner: credentials, site name and message templates sit in one bag. No defaults or validation in one place. Hard to test or refactor. Mass assignment and plaintext secrets make it worse. **This is exactly V1's failure.** `inferred` from scan.md |
| **B. Typed settings classes** (spatie/laravel-settings) | A class per group with typed public properties and defaults; casts (enum, DateTime, Data, Collection). **Settings migrations** (`add`, `rename`, `update`, `delete`, `addEncrypted`) version the stored shape. A missing value with no default throws `MissingSettings`. Database or Redis repositories (custom ones via the `SettingsRepository` interface). Optional cache (`SETTINGS_CACHE_ENABLED`, refreshed on save). Encrypted and locked properties. Events `SavingSettings`/`SettingsSaved`. Discovery from `app/Settings` or explicit registration ([GitHub README](https://github.com/spatie/laravel-settings), [Freek: strongly typed settings](https://freek.dev/store-strongly-typed-settings-in-a-laravel-app)). The official Filament plugin binds a `SettingsPage` form to a settings class. Field names must match the property names, and the docs warn that `canEdit()` does not hide values, so sensitive settings need `canAccess()` ([Filament Spatie Settings plugin](https://filamentphp.com/plugins/filament-spatie-settings), supports v5). `confirmed` | A new dependency, which needs approval. The settings object saves itself (`$settings->save()`), the Active Record style, which clashes with golden rule 1 (every write is a command) and the read-only-model guard. Using it would mean calling it only from a handler behind a port, and **not** using the plugin's auto-save page. Locked properties silently ignore writes. Its discovery cache must be cleared after a class is renamed. `confirmed` / `inferred` |
| **C. Store on the owning module's aggregate** | The setting is domain data with full invariants, its own entity, repository, commands and audit. No generic machinery | Only fits when a natural aggregate exists (for example `MessageTemplate` records in a Messaging module, a `WooShopConnection` in the integration). Forcing singletons ("site name") into an aggregate is awkward. `inferred` |

A **middle path** that fits this codebase (`inferred`): reuse the ADR 0006 pattern for *global* settings. One small table (`key`, JSON `value`). Every setting is a class implementing a Core contract (`SettingDefinition`: key, owner module, default, validation/normalisation rule, optional `sensitive` flag → encrypted cast + masked field). Writes go through an `UpdateSetting` command whose handler rejects unknown keys and checks the `Actor`. Reads go through a typed ModuleApi accessor. This gives B's guarantees (types, defaults, no unknown keys, an owner) without a new dependency or Active Record. The cost is what spatie gives for free: settings migrations for renames, and caching.

## 4. When a settings module pays off, and how to postpone safely

**It is not worth it yet (YAGNI) while** (`inferred`):
- the few runtime values in question are credentials (they go to `.env`, section 2) or are owned by a module that does not exist in V2 yet (messages, integrations);
- no non-developer has asked to change a value without a deploy;
- every candidate value has one consumer that can hold it as config with a typed accessor.

**Signals to introduce it** (heuristics, `inferred`):
1. **About 3–5 or more values** that a non-developer must change without a developer, *and* that are not credentials or domain records.
2. A value **changes more often than you deploy** (weekly template wording, a seasonal toggle), or a change must take effect immediately with no deploy window.
3. **Two or more modules** need admin-editable values and the user wants **one** "Ustawienia" screen instead of a page per module.
4. A value needs audit ("who changed the footer text?") that a git history of `.env` cannot give, because `.env` is not in git.

**How to postpone safely:**
- Put every candidate value in `config/<module>.php` (from `.env` where it varies per deploy) and read it **only** through one typed accessor per owner, for example `WooCommerceConfig::siteUrl(): string` in the integration, or a ModuleApi method. Never call `config('…')` from scattered places.
- Later, the accessor's implementation switches from `config()` to the DB store without touching its callers. That is the whole migration cost. Pick names now that match the future definition keys (`woocommerce.save_as_draft`).
- Do not create a `settings` table "for later". Record the decision and its trigger signals in an ADR so the next module does not reinvent V1.

## 5. How settings fit the modular / hexagonal app

- **Ownership:** a setting belongs to the module or integration that **consumes** it, not to a "Settings" module. Settings would at most own the storage and the screen. Module X declares `x.something` with its default and rules. The definition lives in X, never in Settings, which mirrors Preferences (ADR 0006) and the quick-access registry in Core. `inferred`
- **Registration:** a Core contract (`SettingDefinition`) plus a Core registry that modules feed from their service providers, exactly as `PreferenceDefinition` is fed. The handler resolves by key and rejects unknown keys. Dead keys show up because each definition is a class that can be found and deleted. `inferred`
- **Reading:** consumers never read the table. They call a typed accessor (`ModuleApi` of the store, or their own wrapper), which keeps the config → DB switch invisible. `inferred`
- **One screen:** a single Filament page (or one tab per owning module, like the Audit "Historia i logi" tabs) renders the fields from the registered definitions. Each definition supplies its own field component, label (from the owning module's translations, golden rule 6) and permission. The page sends an `UpdateSettings` command; it does not auto-save. Sensitive definitions render as write-only masked inputs. `inferred`
- **Boundaries:** deptrac stays clean if definitions depend only on Core contracts, and the Settings module (if it exists) knows no other module. Integrations register definitions like any module. `inferred`

## Recommendation for AllGlass V2 (input for the user's decision, not a decision)

1. **Do not build a Settings module now.** Nothing in V2 consumes a runtime-editable setting yet, and V1's settings were a generic key/value store, the very thing to avoid.
2. **Credentials (Woo, WordPress, Otomoto) → `.env` + `config/services.php`**, owned by each integration and read through a typed accessor. They do not appear in the panel; at most a connection-status widget. Plaintext credentials in the DB are not carried over. If DB-stored credentials are ever needed: encrypted cast, masked write-only field, audit without the value, and a written APP_KEY rotation procedure.
3. **Site name/description → `config/app.php` (`APP_NAME`) or translations**, unless the user confirms they change at runtime.
4. **Message templates → the module that sends messages** (records owned by that module's aggregate, approach C) when it is ported. They are not settings.
5. **Write an ADR** (a "no module" decision) with: the V1 key → V2 home map (from r01–r04), the rule "a setting is owned by its consumer and read through a typed accessor", and the trigger signals from section 4. When the triggers fire, build the ADR 0006-style `SettingDefinition` registry with one aggregated screen. Consider spatie/laravel-settings only if settings migrations and caching are needed, and only behind a command/port.

## Sources

- The Twelve-Factor App, III. Config — https://12factor.net/config
- Laravel 12 Configuration (env, config:cache, env:encrypt, typed Config getters) — https://laravel.com/docs/12.x/configuration
- Laravel 12 Encryption (APP_KEY, APP_PREVIOUS_KEYS) — https://laravel.com/docs/12.x/encryption
- Laravel 12 Eloquent casting, Encrypted Casting — https://laravel.com/docs/12.x/eloquent-mutators#encrypted-casting
- Laravel 13 Pennant — https://laravel.com/docs/13.x/pennant
- OWASP Secrets Management Cheat Sheet — https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html
- spatie/laravel-settings README — https://github.com/spatie/laravel-settings
- Freek Van der Herten, "Store strongly typed settings in a Laravel app" — https://freek.dev/store-strongly-typed-settings-in-a-laravel-app
- Filament Spatie Settings plugin — https://filamentphp.com/plugins/filament-spatie-settings
- Repo: `docs/adr/0006-preferences-module-with-code-defined-preferences.md`, `docs/adr/0004-own-authorizer-and-actor.md`, `docs/archive/v1-port/modules/settings/scan.md`
