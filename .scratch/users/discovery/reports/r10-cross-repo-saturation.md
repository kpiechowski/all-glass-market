# r10 — cross-repo: Users saturation check

Question: Is there ANY entry point or behaviour in V1 that reads or changes user/account/auth/role data, or is user-specific panel behaviour, NOT already in F-01..F-22? · V1 commit: 511ce3b

## Summary
Found 3 small NEW behaviours, all about hard-coded developer identity or seeding. No new routes, commands, jobs, listeners, mails, guards or social login. Everything else hit is covered by an F-item, owned by another module, or dead.
New: (N-1) the Activity log screen is visible only to one hard-coded email; (N-2) the Settings cluster is gated to Admin AND the same hard-coded email; (N-3) the DB seeder creates the initial Super Admin from env ADMIN_EMAIL/ADMIN_PASSWORD. Also: User model changes (name, email) are written to the activity log (feeds N-1).

## Findings
### Only one hard-coded person (kpiechowski25@gmail.com) can open the Activity log
- Tag: confirmed · Impact: high (permissions)
- What happens: the Activitylog plugin's `authorize` callback returns true only if the signed-in user's email equals `kpiechowski25@gmail.com`. Roles and Shield permissions (`view_any_activitylog` in ActivityPolicy) are not the deciding factor for the plugin gate. Nobody else, including Super Admin, sees it. In the rewrite this is a decide item: role/permission, or drop.
- Evidence: app/Filament/Utils/PanelConfig.php:86-89 ActivitylogPlugin::make()->authorize(fn() => Auth::user()->email == "kpiechowski25@gmail.com"); app/Policies/ActivityPolicy.php viewAny uses `view_any_activitylog` (a second, additional layer; how the two combine at runtime is unknown).

### Settings area is limited to Admin who also has that same hard-coded email
- Tag: confirmed · Impact: high (permissions)
- What happens: the Settings cluster (navigation "Ustawienia", sort 99) is accessible only if `isAdmin()` AND email == `kpiechowski25@gmail.com`. Ordinary admins and Super Admins with other emails are locked out. (Pages inside the cluster, e.g. ApiSettings/MessagesSettings, were not traced for their own canAccess.)
- Evidence: app/Filament/Clusters/Settings/Settings.php:15-18 canAccess(). grep of `canAccess|shouldRegister` in Settings/Pages returned nothing, so no per-page override found.

### User name/email changes are recorded in the activity log
- Tag: confirmed · Impact: normal
- What happens: the User model logs changes of `name` and `email` only (not password, roles, account type) via spatie LogsActivity. Readable only through N-1 screen. Separate from the app's own change log (F-11 tab).
- Evidence: app/Models/User.php:36 `use LogsActivity`; app/Models/User.php:150-154 getActivitylogOptions()->logOnly(['name','email']).

### Initial Super Admin is created by the seeder from environment
- Tag: confirmed · Impact: high (accounts)
- What happens: `DatabaseSeeder` runs ShieldSeeder (roles/permissions), then creates "Super Admin" with `ADMIN_EMAIL` and `ADMIN_PASSWORD` env values (defaults empty string), assigns role id 2 via syncRoles. Role ids are hard-wired (User=1, Super=2); the seeder also seeds WooCommerce/WordPress settings. The `Admin`/`User` demo users are commented out. `.env.example` has no ADMIN_EMAIL/ADMIN_PASSWORD keys, so they are undocumented.
- Evidence: database/seeders/DatabaseSeeder.php:19-47. grep ADMIN_EMAIL across repo: only that file. database/seeders/OfferResourcesSeeder.php:70 uses User::find(2) as offer owner (seed data only).

## Contradictions and defects
- Hard-coded personal email as an authorization rule (PanelConfig.php:88, Settings.php:17) is a defect-or-decide for the rewrite. Tag: defect? 
- Starter-kit tests (tests/Feature/*: AuthenticationTest posts `/login` and expects `route('dashboard')`; RegistrationTest, etc.) describe Jetstream defaults, not app behaviour; the app routes `/login` to `/admin/login`, so they likely fail. Tag: inferred (not run).

## Leads outside scope
- Pages MyOffers, UserInquiriesPage, UserReservedOffersPage, UsersOtomotoOffers (app/Filament/Pages) are per-user lists (filter by Auth::id(), shown only to non-admins) — owned by Offers/Inquiries/Reservations/Otomoto.
- AdminReservedOffersPage, FullListOffer: admin-only (isAdmin gates) — Offers.

## Classification of other hits
- covered by F-xx: JournalAction/ActivateFromDraft User::find + UserNotificationService::createUserNotification + User::getAdmins senders (F-01/F-02/F-20); EditProfile/ProfilePage (F-08/F-17); accout-widget (F-19); Fortify/Jetstream actions CreateNewUser, ResetUserPassword, UpdateUserPassword, UpdateUserProfileInformation, DeleteUser (F-15); routes/api.php /user (F-16); routes/web.php `/` and `/login` (F-07); CustomAccountWidget (F-19); User::isAdmin/isSuper (F-03).
- dead/unreachable: `joaopaulolndev/filament-edit-profile` plugin (composer.json:17, vendor views resources/views/vendor/filament-edit-profile/*, import in AdminPanelProvider.php:40) — registration is commented out in PanelConfig.php:90-93, so not reachable; app/Livewire/CustomProfileComponent.php (empty render, referenced nowhere else); UserFactory withPersonalTeam / `App\Models\Team` (no Team model in app/Models per file listing; Jetstream teams unused); resources/views/emails/team-invitation.blade.php (teams unused); ShieldSeeder_old*.php; resources/views/test.blade.php.
- nothing exists (searched, empty): app/Listeners, app/Mail, app/Notifications, app/Observers, app/Exports; Event/Gate registrations in providers; custom middleware (bootstrap/app.php only trustProxies, empty exceptions handler); social login (config/services.php has only postmark/ses/resend/slack); extra guards (config/auth.php only `web` / `users` eloquent); scheduled/console commands touching users (routes/console.php: OtomotoAdvert update, queue:work; app/Console/Commands: five data-import commands, none touch users/sessions/tokens); Jobs (app/Jobs/Offer: offers only); app/Http/Controllers (ApiController::test does dd of Woo connection — not user related, owned by API/Woo; no route found pointing to it in routes/web.php or api.php).
- Last-login / login-event tracking: none (grep last_login|logged_in empty).
- git history: user-related history is feature growth (extended profile, shipping_information b4ee999, user view, reservations, inquiries) — no removed user feature spotted in the commit subjects; diffs not read. Tag: unknown for deleted code.

## Could not establish
- How the plugin `authorize` gate and ActivityPolicy combine at runtime (needs runtime or plugin source in vendor, not read).
- Whether the Settings pages each carry their own access checks (grep empty; would need runtime).

## Coverage ledger
- Searched: routes/*.php; bootstrap/app.php; ls of app/{Console,Http,Jobs,Listeners,Mail,Notifications,Observers,Exports,Livewire,Policies,Providers,Actions,Services,View,Modules}; grep `auth()|Auth::|->user()|Notification|actingAs|User::|Hash::` over app/routes/config/database/resources/tests; `User::(create|first|where|find|factory|updateOrCreate)`; `getAdmins`; `createUserNotification`; ADMIN_EMAIL/ADMIN_PASSWORD; `kpiechowski|@allglass|@gmail`; `last_login|logged_in`; `edit-profile|EditProfile|custom-profile`; `team`; composer.json auth-related packages; config/auth.php, config/services.php, .env.example; git log on User.php/ProfilePage/UserResource*.
- Examined: routes/web.php, api.php, console.php; bootstrap/app.php; app/Models/User.php (partial); app/Filament/Utils/PanelConfig.php; app/Filament/Clusters/Settings/Settings.php; app/Policies/ActivityPolicy.php; database/seeders/DatabaseSeeder.php; database/factories/UserFactory.php; app/Actions/Jetstream/DeleteUser.php; JetstreamServiceProvider; app/Services/User/UserNotificationService.php; app/Livewire/CustomProfileComponent.php; EditProfile.php (head); tests/Feature/AuthenticationTest.php.
- Matched, not examined: the ~60 Offer/Otomoto/Inquiry files hitting Auth::/user() (Offers-owned isAdmin gates); other tests/Feature starter tests (same Jetstream template); resources/views/auth,profile (Jetstream starter, F-15); ShieldSeeder.php content (role/permission list, F-04).
