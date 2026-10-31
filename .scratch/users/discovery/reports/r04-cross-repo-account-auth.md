# r04 — cross-repo: where user accounts and auth live outside the anchors

Question: Where else in V1, outside the anchors, are users and their account data read, changed or relied on, and what is the complete set of entry points touching user accounts? · V1 commit: 511ce3b

## Summary
- Account data is consumed outside the anchors in four ways. (1) Private vs company account (`isCompanyAccount()` = `company_account && has_accepted_terms`) drives offer pricing (+23% gross for private owners), labels/colours, and which fields show. (2) `shipping_information` is injected into the Otomoto advert description. (3) `users.id` is the owner key on offers, reservations, inquiries, journals, change logs and bulk actions. (4) Admins are found via `User::getAdmins()` and notified.
- Only one account-change path exists outside anchors: `ProfilePage::update` (anchor), which fans out to `UserNotificationService` and `OfferUpdateService`.
- Sign-in has no custom controller. It runs on the Filament panel login (`/admin/login`), plus Fortify/Jetstream vendor routes. `/login` redirects to `/admin/login`.
- Deleting a user: FKs with `nullOnDelete`/`set null` on changes_logs, otomoto_bulk_actions, offer_inquiries and submissions. `cascade` on user_journals and the Spatie pivots. NO FK at all on offers, offer_reservations, settings, user_notifications, so those rows are orphaned.
- Orphaned offers are handled defensively in several tables (`!$record->user()->exists()`), but not everywhere (see Defects).
- `filament-impersonate` is used (an admin can sign in as another user) in the UserResource anchor only.
- A security incident file at V1 root is relevant to this module (see Leads).

## Findings

### Private vs company account sets owner-facing price (gross = net x 1.23 for private)
- Tag: confirmed · Impact: high (money)
- What happens: `getAdjustedOfferPrice($price, $owner)` returns the price unchanged if there is no owner or the owner `isCompanyAccount()`. Otherwise it returns `round(price*1.23, 2)`. The offer create form calls it with `Auth::user()` to set `price_gross`. The company flag is effective only if `has_accepted_terms` is also truthy.
- Evidence: app/Services/Offer/OfferPriceService.php:13-20 getAdjustedOfferPrice; OfferCreateForm.php:244 `->getAdjustedOfferPrice((float)$state, Auth::user())`; User.php:177 isCompanyAccount (anchor).
- Offers-owned. Only the user dependency is recorded here.

### Company accounts do not see/get some offer fields; admin sees "Firmowe"/"Prywatne" label
- Tag: confirmed · Impact: normal
- What happens: field hidden for company accounts at OfferCreateForm.php:259 (`hidden(fn() => Auth::user()->isCompanyAccount())`); OfferEditForm.php:294 and ViewOffer.php:245 hide a section when the offer owner is a company account; ViewOffer.php:131 shows an admin action only when the owner is NOT a company account. AdminTab.php:60 shows `'Firmowe' : 'Prywatne'`. Dashboard widget shows `'Konto firmowe' : 'Konto prywatne'` (accout-widget.blade.php:19). Owner badge colour is `info` for company or missing owner, otherwise `warning`.
- Evidence: FullListOffer.php:435,455; StockSaleTab.php:88; AdminReservedOffersTable.php:127,186; ReservedOffersTableSchema.php:97; MyOffersTableSchema.php:79; AdminInquiriesTable.php:95; ChangesTableSchema.php:40.
- Offers-owned. The exact field behind line 259 and the hit at ViewOffer 245 were not read, so which field is hidden is unknown.

### Private-account banner on "My offers"
- Tag: confirmed · Impact: normal
- What happens: if `has_accepted_terms` is falsy, a banner reads "Korzystasz z konta prywatnego. Jeśli chcesz zwiększyć sprzedaż, zmień typ konta na firmowe w zakładce Mój profil", linking to `ProfilePage`.
- Evidence: resources/views/filament/pages/my-offers.blade.php:3-7.
- Note: the banner tests `has_accepted_terms`, whereas the account type is `company_account && has_accepted_terms`. The accepted-terms flag therefore stands in for "company".

### User's shipping_information overrides default shipping text in Otomoto adverts
- Tag: confirmed · Impact: high (external system, Otomoto)
- What happens: the advert description gets "Wysyłka:" followed by the owner's `shipping_information`. If that is empty it falls back to the offer/type shipping method: "Wysyłka w 24 godziny w cenie {price}".
- Evidence: Offer.php:527-530 getUserShippingInformation; OtomotoCreationService.php:143-158.
- Not found: the other user fields (`phone`, `city`, `shipment_address`, `company_*`) in any Otomoto/Woo payload. Otomoto contact phone comes from the Otomoto meta settings (OtomotoCreationService.php:286), not from users.

### Company-account switch is rate-limited (30 min) and triggers re-sync of the user's Woo-published offers
- Tag: confirmed · Impact: high (state, external WooCommerce)
- What happens (all in anchor ProfilePage, with effects outside): on a change of `company_account`, a cache key `company_account_lock_{id}` is set for 30 min. The toggle is disabled while the key exists, with the message "Zmiana rodzaju konta jest zablokowana do: H:i". `UserNotificationService::createUserNotification` stores a `UserNotification` and notifies all admins with "Uzytkownik {name} zmienił rodzaj konta na: Firmowe/Prywatne". `OfferUpdateService::handleUsersPublishedOffer` dispatches `OfferUpdateOffersChunk` in chunks of 10 for offers that have a `wooSync` row.
- Evidence: ProfilePage.php:118-124,193-198; UserNotificationService.php:12-36; OfferUpdateService.php:11-17.
- Not examined: what OfferUpdateOffersChunk sends (app/Jobs/Offer). It is presumably the price/content re-push to Woo, which is `inferred`.

### Admin recipients are resolved dynamically as users with roles Admin / Super Admin
- Tag: confirmed · Impact: normal
- What happens: `User::getAdmins()` is used to send database notifications to all admins when users create/edit offers, activate drafts, and accept/reject inquiries, plus account-type changes. The role mechanism itself belongs to r05.
- Evidence: UserNotificationService.php:26; OfferCreatedByUserAction.php:202; OfferEditedByUserAction.php:157; ActivateFromDraftAction.php:76; AcceptInquiryAction.php:61; RejectInquiryAction.php:49.

### Acting user is the target of notifications and records
- Tag: confirmed · Impact: normal
- What happens: bulk Otomoto create sends `Auth::user()->notifyNow`. `OfferNotificationHandler` sends to `auth()->user()`. `BulkActionTracker` stores `user_id = actor->id` on `OtomotoBulkAction` and notifies `run->user`. If the user was deleted (`null`), it returns or skips. `BulkSynchProductToWooAction` takes a `User` and `sendToDatabase($user)`. `CreateInquiryAction` notifies `$record->user` (the offer owner).
- Evidence: BulkCreateAdvertsAction.php:124; OfferNotificationHandler.php:39; BulkActionTracker.php:29,36,72-85,154; BulkSynchProductToWooAction.php:28,55; CreateInquiryAction.php:20-38.

### Offers imported from WordPress/Woo are assigned to a configurable "default user"
- Tag: confirmed · Impact: high (state)
- What happens: the `FetchWooProducts` job creates offers and reservations with `user_id = Setting 'woo-offers-default-user' ?? 1`. The Api settings page (select "Uzytkownik dla zaczytanych ofert") lists ALL users by name.
- Evidence: FetchWooProducts.php:114,147; ApiSettings.php:143-150.
- User id 1 is assumed to exist if the setting is empty (`inferred` to be the Super Admin from the seeder).

### Ownership scoping of non-admin users' lists
- Tag: confirmed · Impact: high (permissions/data visibility)
- What happens: non-admins see only rows where `user_id = Auth::id()` in MyOffers, UserInquiriesPage, UserReservedOffersPage, UsersOtomotoOffers (via `offers.user_id`) and the LatestOffers widget. `Offer::canBeViewByUser`: admin sees all; otherwise soft-deleted is hidden and the owner must match. Create/replicate forces `user_id = Auth::user()->id`; the owner field is a hidden default for non-admins.
- Evidence: MyOffers.php:58,131-147; UserInquiriesPage.php:41-47; UserReservedOffersPage.php:50-69; UsersOtomotoOffers.php:41-63; LatestOffers.php:35; Offer.php:369-385,642-645; OfferCreateForm.php:89; OfferEditForm.php:96; UserOfferCreateForm.php:106.
- Offers-owned. Recorded as a user dependency. The admin check inside `canBeViewByUser` uses `Auth::user()`, not the `$user` argument, which is a `defect?` (see Defects).

### Dashboard stat widget uses per-user sales totals
- Tag: confirmed · Impact: normal
- What happens: OfferStatsOverview shows `Auth::user()->getTotalSalesAmount()` and `getTotalQuantitySold()`.
- Evidence: OfferStatsOverview.php:67-68.

### User names link to the user's view page across Offers screens
- Tag: confirmed · Impact: normal
- What happens: many tables link `user.name` to `ViewUser::getUrl(['record' => user_id])`. Admin tables also filter by user ("Użytkownicy"). `company_name` is a column in the admin offer list.
- Evidence: FullListOffer.php:453-454,534; FullListOfferTable.php:321-322,404-407; OfferResource.php:127; OffersRelationManager.php:65; ListUsersNotifications.php:50-58; OtomotoOfferResource.php:173,291-294; OtomotoQueueMonitor.php:95; SubmissionResource.php:87; ChangesTableSchema.php:38; AdminTab.php:48-51.
- `FullListOffer.php:534`/`FullListOfferTable.php:407` use `user.company_name`. If this column is absent from the users schema, it would be an error; not checked here since the migration is an anchor.

### Change log records actor = Auth::id()
- Tag: confirmed · Impact: normal
- Evidence: ChangesLogService.php:99; ChangesLog.php:18-20; migration 2025_04_12 `user_id` nullable nullOnDelete.

### User deletion: what happens to related data (FK behaviour)
- Tag: confirmed · Impact: high (deletion)
- What happens:
  - `submissions.user_id`: nullable, `set null`.
  - `user_journals.user_id`: `cascade` (journal entries deleted).
  - `changes_logs.user_id`: `nullOnDelete`.
  - `otomoto_bulk_actions.user_id`: `nullOnDelete`.
  - `offer_inquiries.user_id`: nullable, `nullOnDelete`.
  - Spatie permission pivots (model_has_roles/permissions): `onDelete('cascade')` on the permission side, but those pivots are polymorphic with no users FK. Not verified line by line.
  - `offers.user_id`, `offer_reservations.user_id`, `settings.user_id`, `user_notifications.user_id`: `foreignIdFor(User::class)` with NO `constrained()`, so no DB constraint. Rows persist with a dangling id.
  - `versions.user_id` (Versionable): plain column, no FK. Activitylog `causer` is morph, no FK.
  - Jetstream `DeleteAccountTest` and Features::accountDeletion() exist; the delete action is an anchor (Actions/Jetstream).
- Evidence: migrations 2024_11_10_145443:18; 2024_12_01_180306:18; 2025_04_12_181156:15; 2026_07_25_000000:12; 2025_11_15_153812:21-24; 2024_10_06_151733:49; 2025_01_26_5555235_create_offer_reservations:19; 2024_10_13_111544:18; 2025_03_29_174632:16; 2019_05_31 and 2021_03_18 versions.
- The app tolerates a deleted offer owner through `!$record->user()->exists()` checks in the tables listed above. `Offer::user()` has a commented-out `withDefault` "Użytkownik usunięty" (Offer.php:545-551).

### Sign-in and session facts
- Tag: confirmed · Impact: high (auth)
- What happens: Filament panel `admin` at `/admin` with `->login()` (the Filament default login page). Auth middleware is Filament's `Authenticate`. Panel middleware includes `AuthenticateSession`. `/` redirects to `admin`. `/login` redirects to `/admin/login` (route name `admin-login`). The user menu has "Edytuj profil" → `ProfilePage`. Session driver is `database` (.env.example), lifetime 120. `GET /api/user` returns `$request->user()` under `auth:sanctum`, which exposes the user JSON minus `$hidden` (password, remember_token, two_factor_*) plus the appended `profile_photo_url`.
- Evidence: AdminPanelProvider.php:75,100-107,108-118; routes/web.php:5-9; routes/api.php:6-8; config/session.php:21,35; config/sanctum.php:36,49; config/fortify.php:18,76,133,146-152; config/jetstream.php:19,47,63-65; User.php:55-70 (anchor).
- Fortify features enabled: registration, reset passwords, email verification, update profile information, update passwords, two-factor. Jetstream: api tokens, account deletion. Both use `views => true`, and views exist under resources/views/auth and profile.
- Open registration is therefore reachable at `/register` (inferred, from Fortify enabling registration plus the existing view). `CreateNewUser` is an anchor.
- Whether the Blade views are actually reachable (no `dashboard` route in web.php) is unknown.

### Admin-only settings/activity-log access hard-coded to one email
- Tag: confirmed · Impact: high (permissions)
- What happens: the Settings cluster shows only for admins whose email is exactly the developer's address. The ActivityLog plugin is authorised by the same email only.
- Evidence: Settings.php:17; PanelConfig.php:86-88.
- Role/permission aspects are for r05. Recorded as an identity-based rule on `users.email`.

## Entry points (user accounts / auth, whole V1, anchors included)

| Kind | Path | Does | Reachable |
|---|---|---|---|
| Route | GET `/` (routes/web.php:5) | redirect to admin | yes |
| Route | GET `/login` → `/admin/login` (web.php:9) | alias of panel login | yes |
| Filament panel login | AdminPanelProvider.php:75 `->login()` | email/password sign-in | yes |
| Filament auth + session middleware | AdminPanelProvider.php:108-118 | Authenticate, AuthenticateSession | yes |
| Route | GET `/api/user` (routes/api.php:6) | returns the authenticated user (Sanctum) | yes, with token/session |
| Fortify routes (vendor, registered by FortifyServiceProvider, anchor) | /register, /forgot-password, /reset-password, /email/verify, /user/password, /user/profile-information, /user/confirm-password, /user/two-factor-*, /two-factor-challenge | account registration, reset, 2FA | registered via vendor; incident file wants them disabled |
| Jetstream Livewire routes (vendor) | /user/profile, /user/api-tokens, account deletion | profile, tokens, delete account | registered via vendor, `inferred` |
| Anchor — Actions\Fortify\* / Actions\Jetstream\* | create user, update profile/password, reset password, delete user | invoked by the vendor routes above | reachable through the vendor routes |
| Filament page | `ProfilePage` (anchor), user menu "Edytuj profil" and my-offers banner link | edits own profile, company_account, password | yes |
| Filament resource | `UserResource` pages index/profile/create/edit/view (anchor) | admin CRUD of users; `profile/{record}` = `Pages\EditProfile` | yes (policy r05) |
| Filament table action | `Impersonate::make()` (UserResource.php:120, anchor) | admin signs in as another user | yes |
| UserResource relation managers | OffersRelationManager, UserChangesRelationManager | a user's offers and changes | yes |
| Livewire | `users.list-users-notifications` (app/Livewire/Users/ListUsersNotifications.php, embedded in resources/views/filament/users/list-users.blade.php) | table of UserNotification rows (account-type changes) with link to user | yes, on the Users list page (list-users.blade.php used by ListUsers: `inferred`, since the page file is an anchor and was not read) |
| Livewire | `CustomProfileComponent` (app/Livewire/CustomProfileComponent.php) | empty placeholder view | `unreachable`: no reference found |
| Filament widget | `CustomAccountWidget` (accout-widget.blade.php) | shows "Użytkownik", "Konto firmowe/prywatne" | yes, registered in panel |
| Filament widget | `OfferStatsOverview` | per-user sales totals | yes, registered |
| Filament pages | MyOffers, UserInquiriesPage, UserReservedOffersPage, UsersOtomotoOffers, LatestOffers | per-owner views using `Auth::id()` | yes, for non-admins; hidden by `canAccess`/`shouldRegisterNavigation`: `inferred` |
| Service | `UserNotificationService::createUserNotification` (called from ProfilePage:122) | store notification and notify admins | yes |
| Service | `OfferUpdateService::handleUsersPublishedOffer` (ProfilePage:123) | queue re-sync of the user's Woo offers | yes |
| Queued job | `OfferUpdateOffersChunk` | per-chunk update of offers | yes (queue worker is scheduled every minute: routes/console.php) |
| Queued job | `FetchWooProducts` | creates offers for default user | `unknown` whether reachable. Its dispatcher was not traced |
| Scheduled task | `queue:work --queue=otomoto,default` every minute (routes/console.php) | runs jobs that touch users | yes |
| Scheduled task | `UpdateFullDataOfActiveAdvertsCommand` twice daily | Otomoto refresh; no user access found (`Auth::` grep over Services/Jobs/Console found only the cases listed) | yes |
| Settings page | `ApiSettings` select 'woo-offers-default-user' | chooses the user that owns imported offers | yes (Settings cluster, single email) |
| Seeder | `DatabaseSeeder` | creates Super Admin from `ADMIN_EMAIL`/`ADMIN_PASSWORD` env and assigns role 2 | manual |
| Seeder | `OfferResourcesSeeder.php:70` | `User::find(2)->id`; would crash without user 2 | manual |
| Factory | `UserFactory` | test users | tests only |
| Tests | tests/Feature/* (Authentication, Registration, PasswordReset, 2FA, API tokens, Profile, DeleteAccount, BrowserSessions) | Jetstream starter tests | present; they reference `route('dashboard')`, `/register` etc.; whether they pass is unknown (not run, by rule) |
| Model hook | none found: no Observer directory; no `Event::listen`/`Gate::` in AppServiceProvider | | |

## Contradictions and defects
- `defect?` Offer::canBeViewByUser(User $user) checks `Auth::user()->isAdmin()` instead of `$user` (Offer.php:371). Behaves the same only when `$user` is the logged-in user (as at ViewOffer.php:62).
- `defect?` The account type needs both `company_account` and `has_accepted_terms`, but the banner and the profile page condition (ProfilePage.php:61 returns a badge `'i'` if not company) mix the two flags. A user with `company_account=1` and `has_accepted_terms=0` is treated as private for pricing but is not shown the private banner as a company. (Banner tests `has_accepted_terms` only.)
- `defect?` Several tables dereference `$record->user->isCompanyAccount()` without a null check (AdminReservedOffersTable.php:186, ReservedOffersTableSchema.php:97, MyOffersTableSchema.php:79, AdminTab.php:60, ViewOffer.php:131,245, OfferEditForm.php:294). Since offers have no FK on `user_id`, a deleted owner would cause a null error on those screens, while other tables guard it with `!$record->user()->exists()`.
- `defect?` Hard-coded personal email in access checks (Settings.php:17, PanelConfig.php:88).
- `defect?` The web.php has no `dashboard` route, though tests and Jetstream flows reference `route('dashboard')` (tests/Feature/AuthenticationTest.php:20); could be dead.
- `defect?` OfferResourcesSeeder.php:70 assumes user id 2; `woo-offers-default-user` defaults to id 1.
- `decide` Is open registration (Fortify `registration`) intended? The incident file says to disable it.

## Leads outside scope
- /home/kp/laravel/projekty/AllGlass/SECURITY_INCIDENT_2026-10.md: the todo list says to clear `two_factor_*` before rotating APP_KEY, reset user passwords, check `users` for foreign accounts, and disable the Fortify features `registration`, `resetPasswords`, `emailVerification`. This directly affects the Users spec decisions.
- Offers-owned: price gross calculation, ownership, journals (r05), Woo sync job content (`OfferUpdateOffersChunk`).
- `wordpress/` directory in V1 contains a WordPress install with its own `wp_users`; not searched.
- `stechstudio/filament-impersonate` in composer.json: its leave-impersonation route and banner are vendor.
- `joaopaulolndev/filament-edit-profile` plugin is commented out in PanelConfig.php:90-93, and only `config/filament-edit-profile.php` (`show_custom_fields=false`) remains. PanelConfig imports `UserResource\Pages\EditProfile` (anchor).

## Could not establish
- Whether the `/register`, `/user/profile` and other Jetstream/Fortify vendor routes respond in production. Needs runtime (`route:list`) or a read of the anchor service providers.
- What `OfferUpdateOffersChunk` pushes to Woo on a company/private switch. Not read; it sits in app/Jobs (Offers module).
- What dispatches `FetchWooProducts`. Not traced.
- Which Offer field is hidden for company accounts at OfferCreateForm.php:259 / ViewOffer.php:245 / OfferEditForm.php:294. Lines not read in context.
- Whether the Spatie permission tables have a `users` FK. Not verified (only grep for cascade).

## Coverage ledger
- Searched (grep, case-insensitive for several, over app/ routes/ config/ database/ resources/ tests/ bootstrap/ composer.json lang/, excluding vendor, node_modules, storage, public/build, .git, _local_stuff, wordpress):
  - `App\Models\User\b|\bUser::`; `auth()->user|id`, `Auth::user|id`, `Filament::auth`, `request()->user`, `->user()`
  - `company_account|isCompanyAccount|company_name|company_nip|company_address|shipment_address|shipping_information|city_code|has_accepted_terms|custom_fields|profile_photo|two_factor`
  - `getTotalSalesAmount|getTotalQuantitySold|getName(|getForm(|impersonat|ProfilePage|EditProfile|Mój profil|CustomProfile`
  - `user_id`, `->user->`, `user.name`, `\.user\b`
  - `company_account_lock`, `->phone|'phone'|->nip|NIP|Firma|Użytkownik|Użytkownicy`
  - `notifyNow|sendToDatabase|->notify(`
  - `Observer|listen|Event|Gate` in providers; `Kernel|schedule`
  - migration scan for `user|User` FK lines, `cascade`
  - `kpiechowski25`
  - Config terms (fortify, jetstream, sanctum, session, auth, filament-edit-profile) read via grep of key lines.
- Terms with zero hits outside anchors: `company_nip`, `company_address`, `city_code` (outside the anchor and the profile form), `password_reset_tokens`, `personal_access_tokens` (migration only), `sessions` (table), `/api/user` (only the route), `Jetstream` in app code beyond the anchors, `Sanctum` beyond the one route, `Impersonate` beyond UserResource, `Filament::auth`, `request()->user`.
- Examined: routes/web.php, api.php, console.php; AdminPanelProvider; PanelConfig; Offer.php (user parts); OfferPriceService; OfferUpdateService; UserNotificationService; CustomProfileComponent; my-offers.blade.php; OtomotoCreationService (shipping part); FetchWooProducts (user parts); ApiSettings (user part); CustomAccountWidget; DatabaseSeeder; offer_inquiries migration; bootstrap/app.php; SECURITY_INCIDENT file (head); grep-level examination of the others listed in the findings.
- Matched, not examined in full: the Offers-cluster forms, tables and actions (OfferCreateForm, OfferEditForm, UserOfferCreateForm, ViewOffer, StockSaleTab, FullListOffer and similar) are Offers-owned, so only their user-dependency lines were read. Also the Otomoto services and `OfferUpdateOffersChunk`; the Jetstream/Fortify Blade views (vendor-style stock); the test files (only the grep and headers); `ShieldSeeder*` (roles, r05); `lang/` (no user-related labels found by the grep).
