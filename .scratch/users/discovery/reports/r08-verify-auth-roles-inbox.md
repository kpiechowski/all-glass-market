# r08 — verify: auth, roles, inbox claims

Question: settle 12 claims from V1 alone · V1 commit: 511ce3b

## Summary
1 partly · 2 confirmed (with a caveat) · 3 partly (the api-tokens half is REFUTED) · 4 confirmed · 5 refuted as stated (no mail transport in the repo, no link from the Filament login) · 6 refuted (three RoleResources are registered, not one) · 7 partly (reachable, but needs update_user, not view_any_user) · 8 confirmed · 9 refuted · 10 refuted · 11 partly (no logging, banner and leave route exist) · 12 confirmed.

## Findings

### 1. Fortify routes respond in V1: PARTLY
- Tag: confirmed · Impact: high (public auth surface)
- Fortify routes are registered. Nothing calls `Fortify::ignoreRoutes()` or `Jetstream::ignoreRoutes()`. A grep of app, bootstrap, routes and config found no call. `FortifyServiceProvider` is listed in `bootstrap/providers.php:8`. The `registration`, `resetPasswords`, `emailVerification`, `updateProfileInformation`, `updatePasswords` and `twoFactorAuthentication` features are enabled in `config/fortify.php:146-155`. Views are enabled (`'views' => true`, `config/fortify.php:133`), and `resources/views/auth/*.blade.php` exist.
- Method corrections:
  - `/user/password` and `/user/profile-information` are PUT only and need auth (`vendor/laravel/fortify/routes/routes.php:101,108`). A GET returns 405.
  - `/reset-password` exists only as POST. The GET form is `/reset-password/{token}` (`routes.php:55,64`).
  - `/forgot-password`, `/register` and `/two-factor-challenge` have GET and POST with the `guest` middleware.
  - `/two-factor-challenge` GET (`routes.php:131`) is a view, but it redirects away unless a `login.id` session value exists. Without a pending 2FA login it is effectively useless.
- Evidence: `bootstrap/providers.php:8`, `config/fortify.php:133,146-155`, `vendor/laravel/fortify/routes/routes.php`.

### 2. POST /register creates a working account with role "User": CONFIRMED (caveat)
- Tag: confirmed (code path) · Impact: high (open self-registration into the admin panel)
- `CreateNewUser::create` (`app/Actions/Fortify/CreateNewUser.php:28`) calls `User::create` with name, email and password. It assigns no role itself.
- The role comes from Filament Shield. The `HasPanelShield` trait is on User (`app/Models/User.php:34`). `bootHasPanelShield` registers `static::created(fn($user) => $user->assignRole(panel_user role name))` (`vendor/bezhansalleh/filament-shield/src/Traits/HasPanelShield.php:14-20`).
  - This only runs when the app is not running in console.
  - `config/filament-shield.php:27-30` has `panel_user` enabled with the name `'User'`.
- `canAccessPanel` returns true for "Super Admin" or "User" (`HasPanelShield.php:24`). Filament's `Authenticate` middleware aborts only if `canAccessPanel` is false (`vendor/filament/filament/src/Http/Middleware/Authenticate.php:32-34`).
- `fortify.home` is `/admin` (`config/fortify.php:76`). A self-registered user is therefore logged in and can open /admin.
- Registration is not gated: `terms` is not required because `Features::termsAndPrivacyPolicy()` is commented out (`config/jetstream.php:61`). The new user gets only the "User" role's permissions. The seeder snapshot gives "User" offers and journal permissions (`database/seeders/ShieldSeeder_old2.php:15`). That seeder is old, so the live DB may differ. See "Could not establish".

### 3. Jetstream routes: PARTLY, and the api-tokens half is refuted
- Tag: confirmed · Impact: normal
- `/user/profile` (route `profile.show`) is registered. The stack is `livewire` (`config/jetstream.php:19`). `vendor/laravel/jetstream/routes/livewire.php` registers it under `auth`. `resources/views/profile/show.blade.php` exists.
- `/user/api-tokens` is registered, because `Features::api()` is on (`config/jetstream.php:63`).
- REFUTED: `/user/api-tokens` is reachable by authenticated users. The `verified` middleware is `EnsureEmailIsVerified`. It blocks only when `$request->user() instanceof MustVerifyEmail && !hasVerifiedEmail()` (`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php`, the `handle` method).
  - `User` does not implement `MustVerifyEmail`; the import is commented out (`app/Models/User.php:5`).
  - So the check passes for every user and the verified-email requirement is never enforced.
- Reaching it also requires an authenticated user. The page then renders `resources/views/api/*` (exists). I did not trace whether the page's own Livewire component adds a restriction.

### 4. Filament login never asks for a 2FA code: CONFIRMED
- Tag: confirmed · Impact: high (2FA, where enabled, does not protect panel access)
- The panel uses plain `->login()` (`app/Providers/Filament/AdminPanelProvider.php`, the `->login()` call). That is Filament's stock `Login` page, which has no 2FA step.
- `routes/web.php` redirects `/login` to `/admin/login`.
- The only 2FA code in the app is the `TwoFactorAuthenticatable` trait on User, `resources/views/profile/two-factor-authentication-form.blade.php`, and the migration `database/migrations/2024_10_06_123448_add_two_factor_columns_to_users_table.php`. Grep for two_factor/TwoFactor in app, resources, routes and config matched only these.
- A user with `two_factor_confirmed_at` set still logs in through /admin/login without a code.
- The Fortify POST /login (`routes.php:38`) would challenge for 2FA. It is registered, but the login view (`auth/login.blade.php`) is not linked from the panel. The `/login` route is overridden by the redirect in `routes/web.php` for GET only. POST /login to Fortify still exists. Whether Fortify's login logs a user into the panel is untested. I mark this as unknown.

### 5. Password reset by e-mail works end to end: REFUTED as stated
- Tag: confirmed for the pieces, unknown for production mail · Impact: high
- Pieces that exist:
  - The Fortify routes (claim 1).
  - `ResetUserPassword` (`app/Actions/Fortify/ResetUserPassword.php`).
  - `auth/forgot-password.blade.php` and `auth/reset-password.blade.php`, with the guest layout components present.
  - The broker config: `config/auth.php:93-100`, 60 minutes expiry.
- Missing or doubtful:
  - The Filament panel does not enable `->passwordReset()`. The panel login has no "forgot password" link. The only route to Fortify's `/forgot-password` is typing the URL.
  - `.env.example` sets `MAIL_MAILER=log` (line 49). `config/mail.php:17` defaults to `log`. No SMTP configuration is committed. A real `.env` exists in the V1 tree and I did not read it. Production mail is not established.
  - With `log`, the reset e-mail is only written to the log file.
  - No custom reset notification (`ResetPassword::toMailUsing` / `createUrlUsing` grep: none), so the default Laravel mail is used.
  - The reset form is the Jetstream-styled Blade view, not a Filament page.
  - After a successful reset, Fortify redirects to the `login` route, which redirects to /admin/login. I did not trace this.
- Verdict: the mechanism exists but only works if the deployed `.env` configures a real mailer. The panel UI does not expose it.

### 6. RoleResource: REFUTED (three are registered, not one)
- Tag: inferred (from reading Filament and Shield source; not run) · Impact: normal
- Both app classes are discovered:
  - `discoverResources(in: app/Filament/Resources)` finds `app/Filament/Resources/Shield/RoleResource.php`.
  - `discoverClusters(in: app/Filament/Clusters)` also discovers Resource subclasses in that directory (`vendor/filament/filament/src/Panel/Concerns/HasComponents.php:237-262`). It finds `app/Filament/Clusters/Settings/Resources/Shield/RoleResource.php`.
- The vendor Shield RoleResource is registered too:
  - `->plugins($config::getPlugins())` is called BEFORE `discoverResources` in the panel chain.
  - `FilamentShieldPlugin::register` skips the vendor resource only if `isResourcePublished` finds a "RoleResource" in `$panel->getResources()` (`vendor/bezhansalleh/filament-shield/src/Support/Utils.php:20-28`, `FilamentShieldPlugin.php:32-36`).
  - At that moment the list is empty, so the vendor one registers.
  - `bootstrap/cache/filament/panels` is empty, so there is no component cache that changes this.
- All three use slug `shield/roles` (`Utils::getResourceSlug`, `config/filament-shield.php:6`; both app classes override `getSlug`) and the cluster from `config/filament-shield.php:13` (`App\Filament\Clusters\Settings\Settings`). The `$cluster` property in the app copy is commented out (`app/Filament/Clusters/Settings/Resources/Shield/RoleResource.php:26`), but `getCluster()` returns the config value.
- Navigation: all appear in the "Ustawienia" cluster (`app/Filament/Clusters/Settings/Settings.php`, title 'Ustawienia', sort 99). The cluster is visible only to an admin whose e-mail equals `kpiechowski25@gmail.com` (`Settings.php:canAccess`). Resource nav sort is 99 and navigation_group is false.
- Same URL and same route name for the three: the one registered first (vendor, since plugins register first) most likely serves `/admin/.../shield/roles`. The app copies' route names collide. Which one actually renders needs runtime confirmation.
- Differences in the cluster copy: it comments out the `updated_at` column and the `DeleteAction` (diff of the two files).

### 7. UserResource "profile/{record}": PARTLY
- Tag: confirmed · Impact: high (editing other users' data, passwords)
- Route registered: `'profile' => Pages\EditProfile::route('profile/{record}/')` (`app/Filament/Resources/UserResource.php:140`). The URL is `/admin/users/profile/{id}`. The slug "users" is the default. I did not verify it from a route list.
- Nothing links to it: the only references are commented out (`PanelConfig.php:46`). The user menu "Edytuj profil" points to `ProfilePage::getUrl()` instead (`AdminPanelProvider.php`).
- Authorization is NOT `view_any_user`. `EditRecord::mount` calls `abort_unless(Resource::canEdit($record), 403)` (`vendor/filament/filament/src/Resources/Pages/EditRecord.php:82-85`). `canEdit` goes through `UserPolicy::update`, which requires `update_user` (`app/Policies/UserPolicy.php`, the `update` method). The `HasPageShield` trait on EditProfile is commented out.
- The policy ignores the record, so a user with `update_user` can edit any user. `view_any_user` only opens the list.
- Defect: the form uses `Hash::make` with no `use Illuminate\Support\Facades\Hash` import in `EditProfile.php`. In that namespace it resolves to a non-existent class. Setting a new password through this page would therefore error out. The same page has no `canAccess` or ownership check. The form also edits `has_accepted_terms` and the company fields.

### 8. Inbox titles and contents are fixed strings: CONFIRMED
- Tag: confirmed · Impact: normal
- Each `app/Filament/Actions/Journal/*Action` sets fixed `$entryTitle` / `$entryContent`. Examples: `OfferAcceptedAction.php:16-17` "Oferta została zaakceptowana!", `OfferRejectedAction.php:19-20` "Oferta została odrzucona".
- `JournalAction::__invoke` (`app/Filament/Actions/Abstracts/JournalAction.php:21-35`) passes `$this->entryContent . $info` to `User::createJournalEntry`. The only dynamic part is the appended `$info` argument, for example the rejection reason from the admin modal (`AdminTab.php:121`).
- Settings is never read in the Journal code path. The `Setting::value` grep found no use of journal keys outside `MessagesSettings.php`.

### 9. MessagesSettings `{type}_desc` used when creating inbox entries: REFUTED
- Tag: confirmed · Impact: normal
- `MessagesSettings` saves `journal_created_*`, `journal_accepted_*` and `journal_rejected_*` `_title` / `_desc` keys into the Settings table (`app/Filament/Clusters/Settings/Pages/MessagesSettings.php:83-86` and the save action).
- No code reads those keys. A grep across app, resources and database for `journal_created`, `journal_accepted` and `journal_rejected` matched only `MessagesSettings.php`. The saved texts are dead configuration.

### 10. App\Filament\Actions\Journal\OfferReservedAction exists: REFUTED
- Tag: confirmed · Impact: normal
- `app/Filament/Actions/Journal/` contains no such file. It has `OfferAmountReservedAction` and `OfferFullyReservedAction`.
- `AdminTab.php:18` has `use App\Filament\Actions\Journal\OfferReservedAction;`. It is a dangling import, and the class is never instantiated (grep for `OfferReservedAction` in app returns only this line). `git log -S` shows the name was introduced in c28e600 ("Journal action"), ea2f749 and a8b8cbd. The class was later renamed or split.

### 11. Impersonation logging and leaving: PARTLY
- Tag: confirmed · Impact: high
- Only the Impersonate table action on the users list (`UserResource.php:120`, `Impersonate::make()->redirectTo(route('filament.admin.pages.dashboard'))`). The package does not scope it by role. It is hidden when you are impersonating, or target is yourself, or `canImpersonate` / `canBeImpersonated` return false (`vendor/stechstudio/filament-impersonate/src/Concerns/Impersonates.php`, `canBeImpersonated`). The User model defines neither method (`grep canImpersonate|canBeImpersonated` in app: none). So everyone who can see the users table can impersonate anyone listed.
- Not logged. The lab404 `ImpersonateManager` only dispatches `TakeImpersonation` / `LeaveImpersonation` (`vendor/lab404/laravel-impersonate/src/Services/ImpersonateManager.php:128,151`) and has no Log/activity calls (grep: none). The filament package listens only to clear password-hash session keys (`FilamentImpersonateServiceProvider.php:32-33`). The app has no listener for those events (`grep TakeImpersonation|LeaveImpersonation` in app: none). The User model's activity log covers only name and email changes (`User.php:getActivitylogOptions`). So impersonation leaves no audit record.
- Leaving works: the banner (`resources/views/components/banner.blade.php:122`) is rendered at `panels::body.start` while impersonating and links to route `filament-impersonate.leave` (`GET filament-impersonate/leave`, `routes/web.php` of the package, `web` middleware only). It calls `leave()` and redirects to the stored `impersonate.back_to`. The route is also reachable by URL.

### 12. Impersonating a user without panel access is rejected: CONFIRMED
- Tag: confirmed (code path) · Impact: normal
- After `take()` the app redirects to `filament.admin.pages.dashboard`. Filament's `Authenticate` middleware aborts with 403 when `canAccessPanel` is false (`Authenticate.php:32-34`). `canAccessPanel` needs "Super Admin" or "User" (`HasPanelShield.php:24`).
- Consequence: the 403 page has no banner. The admin is stuck in the impersonated session until visiting `/filament-impersonate/leave` by URL, because the leave route does not need panel access.

## Contradictions and defects
- Claim 3: `MustVerifyEmail` is commented out (`User.php:5`) while the `verified` middleware is applied and `emailVerification` is enabled. In effect no verification is enforced, so the api-tokens page is open to any logged-in user.
- `EditProfile.php` uses `Hash::make` without importing `Hash`. Defect when a password is set.
- `AdminTab.php:122` has `->after(fn($livewire) => dd($livewire))` on a reject action in a case branch. It is a leftover debug call and is out of scope here, but it is noted as a lead.
- Self-registration is open with no verification. The "User" role is assigned automatically and grants panel access (claim 2).

## Leads outside scope
- `AdminTab.php:122` contains `dd($livewire)` in a reject action.
- `PanelConfig.php:86-89` restricts the activity log to the hard-coded e-mail `kpiechowski25@gmail.com`; so does `Settings::canAccess`. The cluster is therefore effectively single-user.

## Could not establish
- Production mailer: the real `.env` was not read, and the committed default is `log`. A deployment config check would settle claim 5.
- Which of the three RoleResource classes serves the URL and route name: needs a runtime route list (`route:list`), which V1 rules forbid.
- Live permissions of role "User": the seeders in the repo are named `_old` / `_old2`, so the live set is unknown (database check).
- Whether Fortify's POST /login can be used to start a panel session. Not traced.

## Coverage ledger
- Searched: Fortify/Jetstream route registration and ignoreRoutes, MustVerifyEmail, assignRole/panel_user, canAccessPanel, two_factor, MAIL config, ResetPassword customisation, RoleResource, Shield plugin register, discoverClusters, EditProfile references, UserPolicy, `_desc` / journal setting keys, Journal action strings, OfferReservedAction (grep and git log -S), impersonate (app, vendor stechstudio, vendor lab404), TakeImpersonation listeners.
- Examined: config/fortify.php, config/jetstream.php, config/filament-shield.php, bootstrap/providers.php, routes/web.php, AdminPanelProvider, PanelConfig, User model, CreateNewUser, ResetUserPassword, UserResource, EditProfile, UserPolicy, both RoleResource files, Settings cluster, MessagesSettings, JournalAction and Journal/*, AdminTab (grep), Setting model, vendor Fortify/Jetstream routes, EnsureEmailIsVerified, HasPanelShield, FilamentShieldPlugin, Shield Utils, Filament Authenticate/HasComponents/EditRecord, filament-impersonate and lab404 ImpersonateManager.
- Matched, not examined: `database/seeders/ShieldSeeder_old*.php` (only a grep line seen), `resources/views/api/*` and `profile/*` views (existence only), `.env` (not read).
