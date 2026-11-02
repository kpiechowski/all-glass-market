# r01 — local: identity and accounts

Question: What can users and admins do with user accounts in V1? · V1 commit: 511ce3b

## Summary
- The only live UI is the Filament panel at `/admin`: login (Filament built-in), own-profile page "Profil", admin Users resource (list, view, create, edit, delete), and a user-menu link "Edytuj profil".
- Fortify/Jetstream are installed with registration, reset, 2FA, API tokens and account deletion configured. No Filament panel feature uses them (no `->registration()`, `->passwordReset()`, `->emailVerification()`, 2FA UI). Their HTTP routes may still exist and views exist for auth, so they are "configured, partly reachable by URL, not wired into the panel". See the dedicated section.
- Account type (company/individual) is a self-service toggle on the profile with a 30-minute lock and side effects. `isCompanyAccount()` needs both `company_account` AND `has_accepted_terms`.
- `Model::unguard()` is global, so the narrow `$fillable` has no effect and every form field is mass-assigned.
- Admin user form has no company/individual or terms control, only role, name, email, password, phone, company data, shipping text.

## Findings

### Panel sign-in
- Tag: confirmed · Impact: high
- What happens: the panel `admin` at path `admin` uses Filament's built-in `->login()`. `/` redirects to `admin`. `/login` redirects (GET) to `/admin/login` (route name `admin-login`). Panel access (`canAccessPanel`, from the Shield trait) is granted only if the user has the role "Super Admin" or the panel-user role "User". A user without either cannot enter. Fortify throttle `login` = 5/min per email+IP is defined but applies only to Fortify's POST /login, not to Filament's login page.
- Evidence: app/Providers/Filament/AdminPanelProvider.php `->login()`; routes/web.php:3-7; vendor/bezhansalleh/filament-shield/src/Traits/HasPanelShield.php:26 canAccessPanel; config/filament-shield.php:20-30 (names `Super Admin`, `User`); User.php:36 `use HasPanelShield`. Note: User implements FilamentUser but defines no own canAccessPanel (trait supplies it).

### Sessions
- Tag: confirmed · Impact: normal
- What happens: sessions stored in DB `sessions` table, 120-minute lifetime, not expired on browser close. Panel uses `AuthenticateSession` middleware, so a password change invalidates the user's other sessions. No UI to list/revoke browser sessions in V1 panel (Jetstream browser-sessions feature is not enabled in config).
- Evidence: config/session.php (driver default `database`, lifetime 120, `expire_on_close` false); AdminPanelProvider ->middleware([... AuthenticateSession::class ...]); config/jetstream.php features list only `api()` and `accountDeletion()`.

### Registration (self-signup)
- Tag: ambiguous · Impact: high
- What happens: `Features::registration()` is enabled in Fortify config, `CreateNewUser` exists (validates name, unique email, `Password::default()`+confirmed, and `terms` only if Jetstream terms feature, which is not enabled). It creates a user with name/email/hashed password only: no role, no `has_accepted_terms`, no company flag. Such a user would have no role and could not pass `canAccessPanel`. The panel has no `->registration()`. resources/views/auth/register.blade.php exists, so `GET /register` is probably served by Fortify, but whether Fortify routes are actually registered at runtime could not be confirmed (no route cache; routes come from vendor). No Polish UI message for it. How real users get accounts is therefore: admin creates them (see below). No seeder/code assigns the "User" role on creation (grep `assignRole|syncRoles` only in DatabaseSeeder for the super admin).
- Evidence: config/fortify.php `features` array; app/Actions/Fortify/CreateNewUser.php:create; AdminPanelProvider (no registration call); database/seeders/DatabaseSeeder.php:45; resources/views/auth/register.blade.php.

### Password reset / email verification
- Tag: ambiguous · Impact: normal
- What happens: Fortify `resetPasswords` and `emailVerification` are enabled in config; `ResetUserPassword` sets a new hashed password (rules: required, Password::default(), confirmed). Panel has no "forgot password" link. The `User` model does NOT implement `MustVerifyEmail` (import commented out, User.php:5), so verification mail is never required, `email_verified_at` is not used for gating. Reset token broker: table `password_reset_tokens`, expires 60 min, throttle 60 s. Filament login page does not offer reset. Whether the Fortify reset routes work depends on runtime route registration (unknown, see below).
- Evidence: config/fortify.php; User.php:5; config/auth.php `passwords.users`; app/Actions/Fortify/ResetUserPassword.php:reset; resources/views/auth/forgot-password.blade.php, reset-password.blade.php exist.

### Two-factor authentication
- Tag: confirmed (data layer), ambiguous (reachability) · Impact: high
- What happens: Fortify `twoFactorAuthentication(['confirm'=>true,'confirmPassword'=>true])` configured; model uses `TwoFactorAuthenticatable`; migration adds `two_factor_secret`, `two_factor_recovery_codes`, and `two_factor_confirmed_at` (conditional on `Fortify::confirmsTwoFactorAuthentication()`). Secrets/codes are hidden on serialization. Filament login does not challenge for 2FA, and no panel page offers enabling it, so in the panel flow 2FA is not enforced. Rate limiter `two-factor` 5/min is defined.
- Evidence: config/fortify.php; User.php:34,55-60; migrations/2024_10_06_123448_add_two_factor_columns_to_users_table.php; FortifyServiceProvider.php:41-43. No Filament MFA configured in AdminPanelProvider.

### API tokens
- Tag: confirmed (the single endpoint) / inferred (token UI unreachable) · Impact: high
- What happens: `personal_access_tokens` table exists; `User` uses `HasApiTokens`. The only user-related API route is `GET /api/user` under `auth:sanctum`, returning the authenticated user (JSON hides password and 2FA fields; `profile_photo_url` is appended). Jetstream `Features::api()` is on with permissions create/read/update/delete (default `read`), so the Jetstream route `GET /user/api-tokens` is registered but sits behind the `verified` middleware, and User does not implement MustVerifyEmail, so it would redirect (inferred from vendor routes/livewire.php:31-34; not run). Sanctum tokens have no expiry (`expiration` null). The edit-profile plugin that had a Sanctum token section is commented out in PanelConfig and `show_custom_fields` is false.
- Evidence: routes/api.php; config/jetstream.php; config/sanctum.php; vendor/laravel/jetstream/routes/livewire.php:27-35; app/Filament/Utils/PanelConfig.php:90-93 (plugin commented); config/filament-edit-profile.php.

### Account deletion (Jetstream)
- Tag: confirmed (code), ambiguous (reachable) · Impact: high
- What happens: `DeleteUser` removes the profile photo, deletes all the user's API tokens, then deletes the user row (hard delete; no SoftDeletes trait on User). Only invoked from Jetstream's profile UI (`/user/profile`), which isn't linked from the panel; whether that route/view works is unknown (no `resources/views/profile` examined).
- Evidence: app/Actions/Jetstream/DeleteUser.php:delete; JetstreamServiceProvider.php:26.

### Own profile editing (the live self-service page)
- Tag: confirmed · Impact: high
- What happens: any authenticated panel user (`canAccess()` returns true; no Shield permission) opens "Profil" (nav label "Mój profil", heading "Edytuj swój profil", also user-menu item "Edytuj profil"). Editable: "Imię i nazwisko" (required), "Adres email" (required; no email format or uniqueness rule in the form), "Numer telefonu" (required, tel), account type toggle, "Nazwa firmy", "NIP", "Adres", "Adres do wysyłki" (hint "Jeżeli inny niż adres firmy"), "Miasto", "Kod pocztowy", terms checkbox, and password change ("Nowe hasło" / "Potwierdź nowe hasło", must match; confirmation required when a password is given; blank password is ignored; a generate-20-char-password button fills both). Save action "Zapisz" validates, saves with `$user->update()` and shows "Zapisano dane profilu". Navigation badge "i" shows when the user is not a company account (i.e. not company OR terms not accepted).
- Conditions: "NIP" and "Adres" are required and visible only when company account is selected; the terms checkbox ("Oświadczenie Sprzedawcy", must be accepted) is visible only for company accounts; for private accounts a placeholder reads "Korzystając z konta prywatnego Twoje oferty będą widoczne tylko dla Ciebie i pracowników allGlass. Zmień typ konta na firmowe, aby wyświetlać oferty w katalogu publicznym". Note: when switching to private, the hidden terms checkbox keeps its stored value.
- Evidence: app/Filament/Pages/ProfilePage.php:46-48 (canAccess), 55-58 (badge), 84-101 mount, 104-122 update, 125-end form; AdminPanelProvider userMenuItems.
- Duplicate email on self-edit: no form uniqueness rule, DB column unique -> would raise a DB error. Tag: defect? Evidence: ProfilePage form 'email' TextInput only `required()`; migrations/0001 `$table->string('email')->unique()`.

### Switching between company and individual account
- Tag: confirmed · Impact: high
- What happens: toggle "Rodzaj konta" with "Konto Firmowe" / "Konto prywatne" (default true). When the value actually changes on save: (1) a cache lock `company_account_lock_{userId}` for 30 minutes is set, which disables the toggle with helper text "Zmiana rodzaju konta jest zablokowana do: HH:MM"; (2) a user notification is created with the title "Uzytkownik {name} zmienił rodzaj konta na: Firmowe|Prywatne" (UserNotificationService, detail in r03); (3) every one of the user's offers that has a WooCommerce sync (`wooSync` relation) is re-dispatched in chunks of 10 to the update job `OfferUpdateOffersChunk`. Selecting private opens a modal text: "Jeśli publikujesz oferty z konta prywatnego, podane przez Ciebie ceny zostaną powiększone o wysokość podatku VAT przed dodanim ich do katalogu publicznego AllGlass." (the modal method `getAccountInfoModal` is not obviously wired to the dispatched `account-info-modal` id: unknown whether it opens).
- The lock is cache-only (does not survive a cache flush) and does not bind admins (admin form has no such control).
- Evidence: ProfilePage.php:109-118 update; ProfilePage toggle `->disabled(Cache::has(...))`; app/Services/Offer/OfferUpdateService.php:11-18 handleUsersPublishedOffer.

### What "company account" means (isCompanyAccount)
- Tag: confirmed · Impact: high
- What happens: `isCompanyAccount()` = `company_account` AND `has_accepted_terms`. A user flagged company but without accepted terms counts as individual. It drives: label "Firmowe"/"Prywatne" in the users table and account widget ("Konto firmowe"/"Konto prywatne"), price handling in OfferPriceService (company/no owner skips the private-account path), and visibility of several offer fields/actions (OfferCreateForm:259, OfferEditForm:294, ViewOffer:131,245, reserved tables). Pricing/offer semantics belong to Offers; only the tie is recorded here.
- Evidence: User.php:177-180; UserResource.php:90-94; resources/views/filament/widgets/accout-widget.blade.php; app/Services/Offer/OfferPriceService.php:15; usages listed by grep in Offers cluster.

### Terms acceptance
- Tag: confirmed · Impact: high
- What happens: `has_accepted_terms` is a boolean column default 0, cast boolean. Users set it on their own profile via the checkbox "Oświadczenie Sprzedawcy" with text "Oświadczam, że jestem zarejestrowanym czynnym podatnikiem podatku VAT. Jednocześnie oświadczam, że dokonuje sprzedaży oferowanych towarów w ramach prowadzonej przeze mnie działalności gospodarczej." Must be accepted (company accounts). Description in the other (resource) profile page: "Proszę potwierdzić oświadczenie, aby umożliwić publikację Państwa ofert." Together with the company flag it determines company status; no timestamp or version of acceptance is stored. An onboarding modal forcing acceptance is commented out in ProfilePage. Fortify's `terms` registration check is for Jetstream's terms feature, not enabled.
- Evidence: migrations/0001 (`has_accepted_terms` default 0); User.php:81; ProfilePage.php checkbox; ProfilePage.php onboarding commented; EditProfile.php toggle.

### Resource profile page (second, likely dead, profile editor)
- Tag: ambiguous · Impact: normal
- What happens: `UserResource` registers a page route `profile/{record}/` (`EditProfile`) with similar fields (name, email, terms toggle required, company fields, password change, including company data for any record id) and with Shield page check commented out. Nav item pointing at it is commented out in PanelConfig. It is reachable by URL for any record the viewer can pass the resource policy for (inferred; resource `canAccess` uses `view_any_user`). It does not carry the account-type lock.
- Evidence: UserResource.php:140; Pages/EditProfile.php; PanelConfig.php:43-47 (commented).

### Admin: list users
- Tag: confirmed · Impact: normal
- What happens: users with `view_any_user` see a table with columns name (searchable), email (searchable), "Rodzaj konta" (Firmowe in success colour / Prywatne in warning), role badge (first role: Super Admin red, Admin green, User yellow; a user with no role would hit the `match` with null/no arm and error), "Data dołączenia" (sortable). No filters, no bulk actions. Row click goes to the view page. Row action: Impersonate (r02) redirecting to dashboard, and Edit. Empty state offers Create. Header "Create". Page view also embeds Livewire `users.list-users-notifications` (r03). Soft-delete scope removed from the query (User has no soft deletes; no `deleted_at` in migrations).
- Evidence: UserResource.php:75-132, 149-155; ListUsers.php; resources/views/filament/users/list-users.blade.php.
- Role badge defect: `match` without default and `$state` typed string -> null state for roleless user throws. Tag: defect? Evidence: UserResource.php:99-106.

### Admin: view user (detail)
- Tag: confirmed · Impact: normal
- What happens: title is user name; header actions "Powrót" (browser back) and "Edytuj". Infolist: "Imię", email, "Nr telefonu", "Data dołączenia"; company block "Nazwa firmy", "Nip", "Adres", "Adres do wysyłki", "Miasto", "Kod pocztowy"; summary "Wszystkie oferty" (count), "Aktywne oferty" (count with status Active), "Łączna wartość ofert" (sum of `price` over ALL offers incl. non-active, in zł). Shipping info text and the account type are not shown.
- Evidence: ViewUser.php:infolist.

### Admin: relation managers on user detail
- Tag: confirmed · Impact: high (delete)
- What happens: two tabs. (1) "Powiązane oferty" (shared OffersRelationManager): read-only list of the user's offers with image, eurocode, owner link, status badge, price (PLN), usage state, type, WP sync flag; modal filter "Filtruj" (trashed, status, usage status, WP sync ternary, rating, users, price...); row action "Zobacz" opens the offer; bulk action delete is present (DeleteBulkAction) so a user with permission can bulk-delete offers there. Offer semantics: Offers module. (2) "Rejestr zmian użytkownika" (UserChangesRelationManager): changes-log entries where `changes_logs.user_id` = this user; sorted newest first; view in slide-over; per-row Delete and bulk delete are offered (relation manager forced editable via `isReadOnly()` false), so audit entries can be deleted from here subject to ChangesLog policy. Header actions empty. A dead per-user `UserResource/RelationManagers/OffersRelationManager` (CRUD on "name") exists but is not registered in ViewUser (ViewUser uses Shared one).
- Evidence: ViewUser.php:getRelationManagers; Shared/OffersRelationManager.php; UserChangesRelationManager.php:table; database migration changes_logs `user_id` nullOnDelete.

### Admin: create user
- Tag: confirmed · Impact: high
- What happens: via the form `User::getForm()`: section "Rola" (hidden when editing an Admin/Super Admin; otherwise required single role select, min 1 max 1), "Dane użytkownika": "Imię i nazwisko" (required, unique, max 255, must not contain `_`), email (email, required, unique), password (required on create, hashed on save, random 20-char generator button, copy-to-clipboard button), "Numer telefonu"; "Dane firmy": "Nazwa Firmy", "Numer NIP Firmy", "Adres Firmy", "Miasto", "Kod pocztowy" (all optional, 255); "Wysyłka szyb" rich text "Wysyłka". No fields to set company/individual or terms, so admin-created users start as individual (`company_account` 0, `has_accepted_terms` 0) until the user changes it. No email is sent. Needs `create_user`.
- Evidence: User.php:182-290 getForm; CreateUser.php (default); UserPolicy.php:create.
- Note: role select is hidden for Admin/Super Admin records, so an admin's role cannot be changed here, and when hidden the `roles` relationship is not saved. (r02 for roles.)

### Admin: edit user
- Tag: confirmed · Impact: high
- What happens: same form as create; blank password leaves the old password; name uniqueness ignores the current record. Header actions: Delete, Force delete, Restore. Delete is a hard delete (no soft deletes in User/migrations), so Force delete and Restore are visible only for trashed records (Filament behaviour, inferred) and are therefore inert. Deletion effects on other tables are by FK: `changes_logs.user_id` set to null; other tables' cascade rules are out of this scope (cross-repo). Delete needs `delete_user`. The list page has the Delete row action commented out (only editing offers deletion).
- Evidence: EditUser.php:getHeaderActions; UserResource.php:122; UserPolicy.php:delete; migrations/0001 (no deleted_at).

### Policy
- Tag: confirmed · Impact: high
- What happens: UserPolicy maps each ability to a Shield permission named `{ability}_user` (view_any, view, create, update, delete, delete_any, force_delete, force_delete_any, restore, restore_any, replicate, reorder). No ownership rule: an admin with `update_user` may edit anyone including Super Admin; a normal user without `view_any_user` can still reach their own "Profil" page.
- Evidence: app/Policies/UserPolicy.php; UserResource.php:40-57.

### Custom fields
- Tag: confirmed (column), unknown (use) · Impact: normal
- What happens: `users.custom_fields` json nullable added by the edit-profile plugin. Plugin is commented out; `show_custom_fields` false; no code in the app reads/writes it besides a vendored blade view. Effectively dead.
- Evidence: migrations/2024_10_23_174738; config/filament-edit-profile.php; PanelConfig.php:90-93; grep `custom_fields` in app/ shows no model use.

### Shipping information
- Tag: confirmed · Impact: normal
- What happens: `users.shipping_information` (text, nullable) is edited by admins in the user form ("Wysyłka"). Helper text: "Informacja wyświetli się w sekcji WYSYŁKA opisu otomoto dla ofert dotyczących tego użytkownika. Pozostaw puste aby użyć wysyłki z oferty/kategorii" (user value overrides offer/category shipping text in the Otomoto description). The consumer of the field (Otomoto description) is outside this mission. The owner cannot edit it on their own profile (not in ProfilePage). Separate `shipment_address` ("Adres odbioru"/"Adres do wysyłki") is a different plain address field editable by the user.
- Evidence: migrations/2025_09_02_55555; User.php:274-281; git log: commit b4ee999 "otomoto description fix, added shipping_information on user edit". Consumption not traced.

### Profile photo
- Tag: ambiguous · Impact: normal
- What happens: `HasProfilePhoto` adds `profile_photo_url` (appended) and `profile_photo_path` column; disk `public`. No panel UI for uploading it; the Fortify `UpdateUserProfileInformation` accepts `photo` (jpg/jpeg/png, 1 MB) but is only invoked from Fortify's PUT /user/profile-information. Filament avatar uses whatever default provider.
- Evidence: User.php:32,67; config/jetstream.php; app/Actions/Fortify/UpdateUserProfileInformation.php.

### Model rules, hooks and helpers
- Tag: confirmed · Impact: normal
- What happens: no `booted()`/observers on User. `getName()` returns name or "Brak nazwy". `isAdmin()` = role Admin or Super Admin; `isSuper()`; `getAdmins()` returns all Admin/Super Admin users. Activity log (spatie) records only changes to `name` and `email` on the user. Password cast `hashed` plus form-level Hash::make (double-hash risk: the form hashes in dehydrateStateUsing, then the cast would see an already-hashed value; Laravel's cast skips re-hashing if the value is already a bcrypt hash of the current algorithm, so OK, inferred). `getTotalSalesAmount()` = sum over the user's offers of sale quantity x the offer's CURRENT price (not a price at sale time); `getTotalQuantitySold()` = sum of sales quantities. Neither is called in the anchors (grep shows no callers outside the model in the examined set; see ledger) and sales semantics belong to Offers. Relations: offers, offerInquiries, userChangesLogs, userNotifications.
- Evidence: User.php:85-175; getActivitylogOptions line 150.

### Impersonation
- Tag: confirmed (existence only) · Impact: high
- What happens: row action `Impersonate` on the users table, redirect to dashboard. Rules in r02.
- Evidence: UserResource.php:120.

### Mass assignment is wide open
- Tag: confirmed · Impact: high
- What happens: `User::$fillable` lists only name/email/password, but `Model::unguard()` in AppServiceProvider::boot makes every attribute fillable. All form writes of company/terms/phone/etc. therefore work; also any attribute posted to Fortify profile update is irrelevant because it uses forceFill. Redundant fillable is misleading.
- Evidence: app/Providers/AppServiceProvider.php:40; User.php:44-48.

## Fortify/Jetstream: enabled vs dead
| Feature | Configured | Reachable in Filament panel | Verdict |
|---|---|---|---|
| Login | Filament `->login()` | yes | live (Filament, not Fortify) |
| Fortify login throttle 5/min | FortifyServiceProvider | no (Filament has its own) | dead for panel |
| Registration | `Features::registration()` + CreateNewUser + auth/register view | no panel link | ambiguous, produces roleless unusable users |
| Password reset | enabled + ResetUserPassword + views | no link | ambiguous |
| Email verification | enabled, model lacks MustVerifyEmail | no | dead |
| updateProfileInformation / updatePasswords | enabled + actions | no, panel uses its own ProfilePage | dead (stock actions) |
| 2FA | enabled (confirm, confirmPassword) | no UI, no challenge | dead |
| Jetstream API tokens | `Features::api()` | /user/api-tokens behind `verified`, no MustVerifyEmail | effectively dead; only `GET /api/user` live |
| Jetstream account deletion | enabled, DeleteUser | no link | dead |
| Teams, profile photos upload, terms feature, browser sessions | not enabled | no | n/a |
| Edit-profile plugin / custom_fields | commented out | no | dead |

## Contradictions and defects
- ProfilePage email lacks unique/format validation (defect?).
- Users table role badge `match` throws for users with no role (defect?). Fortify registration creates exactly such users.
- Admin user form cannot set terms/account type; seeded/created users are individual until they edit their profile.
- Dead duplicate: `UserResource/RelationManagers/OffersRelationManager` and resource `EditProfile` page.
- `generate-random-password` copy action injects the password straight into a JS string (no escaping), harmless for the generator but note.
- Features declared in config (2FA, registration) but not offered in UI.

## Leads outside scope
- Consumer of `shipping_information` in Otomoto description; `OfferUpdateOffersChunk` job; `UserNotificationService` (r03); `OfferPriceService` private VAT logic; ChangesLog policy for deleting entries; Offer FK cascade on user deletion; `users.list-users-notifications` Livewire; impersonation (r02).
- ActivitylogPlugin is authorised for a hard-coded email `kpiechowski25@gmail.com` (PanelConfig.php:87-89).

## Could not establish
- Whether Fortify/Jetstream HTTP routes (register, forgot-password, /user/profile, /user/api-tokens) respond at runtime: no route cache present, requires running V1 (`route:list`) which is forbidden. Runtime would settle it.
- Whether the "account-info-modal" opens (modal id dispatched but `getAccountInfoModal` wiring and the view profile-page.blade.php not read).
- Whether ForceDelete/Restore header actions are really hidden (Filament vendor behaviour inferred).
- Callers of `getTotalSalesAmount`/`getTotalQuantitySold` (only searched app/, routes, resources in a combined grep; not enumerated per file).
- Business: how real users get their first accounts and the role "User" (admin creates manually? imported?) and is self-registration intended.

## Coverage ledger
- Searched: unguard; canAccessPanel; company_account_lock; handleUsersPublishedOffer; getTotalSalesAmount; getTotalQuantitySold; isCompanyAccount; shipping_information; has_accepted_terms; custom_fields; Fortify::/Jetstream::/ignoreRoutes in app; assignRole/syncRoles; Impersonate; deleted_at in migrations for users; registration/passwordReset/emailVerification in providers.
- Examined (in full unless noted): app/Models/User.php; migrations 0001_01_01_000000, 2024_10_06_123448, 2024_10_06_123456, 2024_10_23_174738, 2025_09_02_55555; UserResource.php; Pages CreateUser, EditProfile, EditUser, ListUsers, ViewUser; RelationManagers OffersRelationManager, UserChangesRelationManager; ProfilePage.php; UserPolicy.php; Actions/Fortify/CreateNewUser, UpdateUserProfileInformation, ResetUserPassword (tail), PasswordValidationRules; Actions/Jetstream/DeleteUser; FortifyServiceProvider; JetstreamServiceProvider; AdminPanelProvider; config auth, fortify, jetstream, sanctum, session, filament-edit-profile (comment-stripped view); routes web.php, api.php; one hop: Shared/OffersRelationManager (first ~150 lines), UserChangesTableSchema (head), OfferUpdateService, AppServiceProvider, PanelConfig (plugins/nav), HasPanelShield, config/filament-shield (partial), list-users.blade.php, accout-widget.blade.php.
- Matched, not examined: UpdateUserPassword.php (stock, not opened); resources/views/profile/*, auth/login/forgot/reset views (existence only); profile-page.blade.php; OfferPriceService and Offer-cluster consumers of isCompanyAccount (Offers); tests/Feature/* Jetstream stock tests (listed, not read; they would not prove panel behaviour); ChangesDetailsInfolistSchema; rest of Shared/OffersRelationManager filters.
