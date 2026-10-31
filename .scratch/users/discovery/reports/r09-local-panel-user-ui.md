# r09 — local: Signed-in user's own experience of the panel

Question: What does the panel offer a signed-in user about themselves beyond the profile form (user menu, account widget, notification bell, language switch, avatar), and what user-specific state is kept for each? · V1 commit: 511ce3b

## Summary
- User menu: Filament's default menu plus one custom item "Edytuj profil" (to ProfilePage); the same page is also in the sidebar as "Mój profil" with a badge "i" for non-company accounts.
- Dashboard widget shows avatar, "Użytkownik", name, account type and a logout button. The avatar is Filament's default (initials / ui-avatars); no custom avatar provider or upload exists.
- Notification bell: `->databaseNotifications()` with default polling (no custom interval). Per-user state is the Laravel `notifications` table (`read_at`). Senders: admin-targeted account-type change and activate-from-draft notices, and the owner's Otomoto/Woo bulk-job results. No cleanup exists.
- Language switch: package configured for `['en','pl']`. The choice is kept per browser (session plus a forever cookie), not per user. Almost no `en` strings exist, so switching changes little.
- Jetstream leftovers (navigation-menu, profile/*, dashboard, api) look unreachable or broken. `CustomProfileComponent` is an empty stub. The `test` Blade hook probably prints the literal text "test" in the body.

## Findings

### User menu has one custom entry, "Edytuj profil"
- Tag: confirmed · Impact: normal
- What happens: Every signed-in user sees "Edytuj profil" (icon heroicon-m-user-circle, `visible(true)`) in the avatar dropdown, linking to `ProfilePage::getUrl()`. Filament's default items (logout) remain. The plugin `FilamentEditProfilePlugin` is commented out, so no second profile page exists.
- Evidence: app/Providers/Filament/AdminPanelProvider.php:99-105 `->userMenuItems([MenuItem::make()->label(fn()=>'Edytuj profil')...`; app/Filament/Utils/PanelConfig.php:73-80 plugins list has FilamentEditProfilePlugin commented out. `EditProfilePage` is imported at AdminPanelProvider.php:42 but never used.

### Profile page is open to every signed-in user and is flagged in the sidebar for non-company accounts
- Tag: confirmed · Impact: normal
- What happens: `canAccess()` always returns true. The nav item is labelled "Mój profil" (sort 7, icon user-circle). The badge is the text "i" in colour `info` while `!isCompanyAccount()`, otherwise no badge. `isCompanyAccount()` = `company_account && has_accepted_terms`, so a user who ticked company but did not accept the terms also gets the badge. The page title is "Profil" and the heading "Edytuj swój profil". Form details belong to F-08/F-09 and are not repeated.
- Evidence: app/Filament/Pages/ProfilePage.php:38-62 `canAccess`, `getNavigationBadge`, `getNavigationBadgeColor`; app/Models/User.php:177-180 `isCompanyAccount`.

### Dashboard account widget: identity, account type, logout
- Tag: confirmed · Impact: normal
- What happens: This is the first dashboard widget (registered in `->widgets([...])`) and it replaces Filament's stock AccountWidget. It shows the avatar (`x-filament-panels::avatar.user`, size lg), the caption "Użytkownik", the display name, and "Konto firmowe" or "Konto prywatne" (via `isCompanyAccount()`, so the same terms caveat applies). A POST logout button uses the translation key `filament-panels::widgets/account-widget.actions.logout.label`. It is hidden below the `sm` breakpoint (`hidden sm:flex`). The widget has no link to the profile page. Nothing is stored.
- Evidence: app/Filament/Widgets/CustomAccountWidget.php:7-10; resources/views/filament/widgets/accout-widget.blade.php:1-37; AdminPanelProvider.php:83-88.

### Avatar is Filament's default, not an uploaded photo
- Tag: confirmed (default behaviour inferred from Filament) · Impact: normal
- What happens: `User` does not implement `HasAvatar` and has no `getFilamentAvatarUrl`. Filament therefore falls back to its default provider (ui-avatars initials). The Jetstream `HasProfilePhoto` trait is present and `profile_photo_path` exists in the users migration, but `Features::profilePhotos()` is commented out. No UI in the panel sets the photo. `profile_photo_url` is appended to the array form of the user.
- Evidence: app/Models/User.php:14,32,66-69; config/jetstream.php:60-66 (`// Features::profilePhotos()`); database/migrations/0001_01_01_000000_create_users_table.php:34; grep for `getFilamentAvatarUrl|HasAvatar` in app/ returned nothing. The Filament default-provider behaviour was not read in vendor, so that part is `inferred`.

### Notification bell shows the user's database notifications
- Tag: confirmed (bell, table) · Impact: normal
- What happens: `->databaseNotifications()` enables Filament's bell, modal and badge. No `databaseNotificationsPolling` call exists, so the Filament default interval applies (30s in v3; not verified in vendor). `User` uses `Notifiable`, and the table `notifications` (uuid id, type, morph notifiable, data text, `read_at`, timestamps) holds the per-user state. Read and unread state, "mark all as read" and "clear" are Filament's built-in bell actions, and each user sees only their own rows. No scheduled prune or cleanup exists (routes/console.php only has commented-out log lines).
- Evidence: AdminPanelProvider.php:89 `->databaseNotifications()`; app/Models/User.php:33; database/migrations/2024_10_24_161428_create_notifications_table.php:15-22; routes/console.php:27-31; grep for polling in app/config returned nothing.

### What lands in a user's bell
- Tag: confirmed · Impact: normal
- What happens:
  - **To every Admin and Super Admin** (`User::getAdmins()`): the "Uzytkownik {name} zmienił rodzaj konta na: Firmowe/Prywatne" notice (from `UserNotificationService::createUserNotification`, triggered by ProfilePage::update), and notices from `ActivateFromDraftAction`. Each one also writes a `user_notifications` row (the admin feed, F-02, out of scope).
  - **To the acting user or run owner**: Otomoto bulk-run progress and results (`BulkActionTracker`) and the WooCommerce bulk sync result (`BulkSynchProductToWooAction`).
  - `OfferNotificationHandler::notifyOfferCreatedByUser` sends a placeholder "Saved successfully / Changes to the post have been saved." to `auth()->user()`. No caller of it was found, so it is dead code.
- Evidence: app/Services/User/UserNotificationService.php:10-34; app/Filament/Pages/ProfilePage.php:90-94; app/Filament/Actions/Journal/ActivateFromDraftAction.php:74-76; app/Services/OtomotoService/BulkActionTracker.php:45,85,170,182,192; app/Services/ApiService/Actions/BulkSynchProductToWooAction.php:55; app/Services/NotificationService/Handler/OfferNotificationHandler.php:21-40. The call-site grep for `notifyOfferCreatedByUser` found only its definition.

### Language switch: pl/en, per browser, not per user
- Tag: confirmed (storage), observed (precedence from vendor source) · Impact: normal
- What happens: `LanguageSwitch::configureUsing(...->locales(['en','pl']))` in AppServiceProvider. The package registers a switcher at render hook `panels::global-search.after` (inside panels, since there are 2 locales and the panel is included) and a middleware that calls `app()->setLocale(getPreferredLocale())`. Choosing a language writes `session('locale')` and a forever cookie `filament_language_switch_locale`, then redirects back to the Referer. Nothing is written to the `users` table. `userPreferredLocale` is not configured. Precedence is session, then `?locale=`, then cookie, then `app.locale` (default `pl`), and an unlisted value falls back to `config('app.locale')`. The choice is therefore tied to the browser and not carried across devices or accounts. Effect is limited: lang/pl holds only validation.php and lang/en holds only auth, pagination, passwords and validation. All app UI strings are hard-coded Polish, so only Filament's own labels change. `config/filament.php` lists `'locales' => ['pl']`, which is a separate setting and was not traced.
- Evidence: app/Providers/AppServiceProvider.php:41-45; vendor/bezhansalleh/filament-language-switch/src/LanguageSwitch.php:42,55-78,217-221,261-270,321-329; src/Http/Middleware/SwitchLanguageLocale.php:11-17; src/FilamentLanguageSwitchServiceProvider.php:27-41; config/app.php:81-83; ls lang/pl lang/en.

### Dashboard widgets scoped to the signed-in user
- Tag: confirmed · Impact: normal
- What happens: `OfferStatsOverview` reads `Auth::user()->offers()` and shows:
  - "Moje oferty:" (total) with "W tym miesiącu: N", a trending up or down icon and a success or warning colour. It links to MyOffers.
  - "Aktywne oferty" with "Oczekujące oferty: N", linking to MyOffers filtered on status 'Aktywna'.
  - "Odrzucone oferty", linking to MyOffers filtered on status 'Odrzucona'.

  The sales-value stat (`getSumPriceStat`) is defined but not returned, so it is dead. `LatestOffers` filters `Offer.user_id = Auth::user()->id` ordered by `created_at` desc, and at line 94 it branches on `isAdmin()` (semantics out of scope). `AllGlassWidget` is a static view with no user data. Counts are per user, not per company or admin-wide. The widget is non-lazy (`$isLazy=false`). No `canView` is defined on any widget, so all users see them.
- Evidence: app/Filament/Resources/OfferResource/Widgets/OfferStatsOverview.php:12-85; LatestOffers.php:35,94; app/Filament/Widgets/AllGlassWidget.php; AdminPanelProvider.php:83-88.

### "My offers" banner nudges non-accepted users to the profile page
- Tag: confirmed · Impact: normal
- What happens: When `Auth::user()->has_accepted_terms` is falsy, the page shows an info "shout" banner: "Korzystasz z konta prywatnego. Jeśli chcesz zwiększyć sprzedaż, zmień typ konta na firmowe w zakładce Mój profil", with "Mój profil" linking to ProfilePage. The test is on `has_accepted_terms`, not on `isCompanyAccount()`. A user with `company_account` true but terms not accepted therefore also sees "Korzystasz z konta prywatnego".
- Evidence: resources/views/filament/pages/my-offers.blade.php:3-9; resources/views/components/shout.blade.php.

### Profile page info modal "Pamiętaj!"
- Tag: confirmed · Impact: normal
- What happens: The view includes a modal `account-info-modal` with the heading "Pamiętaj!" and the text "Jeśli publikujesz oferty z konta prywatnego, podane przez Ciebie ceny zostaną powiększone o wysokość podatku VAT przed dodanim ich do katalogu publicznego AllGlass." Its button "Rozumiem" closes it. It opens when the account-type toggle changes to the private value `"0"`. A submit button "Zapisz" also sits under the form. This is already mapped under F-09 and is noted only because it lives in the view.
- Evidence: resources/views/filament/pages/profile-page.blade.php:14-27; ProfilePage.php:178-182 `afterStateUpdated`.

### Panel-level user-facing chrome
- Tag: confirmed · Impact: normal
- What happens:
  - Default theme is Light (the user can switch dark mode in Filament; not stored per user by app code).
  - Breadcrumbs are off.
  - The sidebar is collapsible on desktop, collapsed width 6rem.
  - Navigation group "Ustawienia" has a cog icon.
  - `<meta name="robots" content="noindex, nofollow">` is injected at HEAD_START.
  - `login()` is the stock Filament login. No custom login page exists, and `/` and `/login` redirect to `/admin` and `/admin/login`.
  - `PanelConfig::getNavigationItems()` is empty (the "Profil" item is commented out).
  - Activity Log plugin access is restricted to the hard-coded email kpiechowski25@gmail.com.
- Evidence: AdminPanelProvider.php:56-72,106-134; PanelConfig.php:37-45,73-80; routes/web.php:6-10.

## Contradictions and defects

### BODY_START hook prints the literal text "test"
- Tag: defect? · Impact: normal
- What happens: `Blade::render('test')` renders the string template "test", not the view `test.blade.php`. It likely emits the bare word "test" at the start of every panel body. The `test` view (a `close-sidebar` listener) is not what runs. Runtime would confirm.
- Evidence: AdminPanelProvider.php:129-132; resources/views/test.blade.php.

### Jetstream leftovers are likely not rendered by anything in the panel
- Tag: inferred · Impact: normal
- What happens: `resources/views/navigation-menu.blade.php` is included only by `layouts/app.blade.php`, which is used through `<x-app-layout>` in `profile/show`, `dashboard` and `api/index`. `routes/web.php` has no `dashboard` route, yet `navigation-menu` calls `route('dashboard')`, so it would raise an exception if rendered. Jetstream's package routes (`/user/profile`, `/user/api-tokens`, with `Features::api()` and `accountDeletion()` enabled) may still serve `profile.show` and `api.index`, but no panel link points to them. They are reachable only by typing the URL, and they would likely fail on the missing `dashboard` route. The profile/* partials (update-profile-information, update-password, 2FA, browser sessions, delete-user) are used by `profile/show`. Fortify features `registration`, `emailVerification` and `twoFactorAuthentication` are enabled in config (r08 territory). Jetstream route registration was not read in vendor.
- Evidence: resources/views/navigation-menu.blade.php:1-15; routes/web.php; config/jetstream.php:60-66; config/fortify.php:146-160; grep for `navigation-menu|x-app-layout` matched only resources/views/{layouts/app,dashboard,profile/show,api/index}.blade.php and app/View/Components/AppLayout.php.

### `CustomProfileComponent` is an empty stub
- Tag: confirmed · Impact: normal
- What happens: The Livewire component renders `livewire.custom-profile-component`, which contains only a placeholder comment. Nothing references it.
- Evidence: app/Livewire/CustomProfileComponent.php; resources/views/livewire/custom-profile-component.blade.php; grep for `CustomProfile` found only the class itself.

### Misc
- `PanelNotificationProvider` imports `LanguageSwitch` and does not use it. It is also not in bootstrap/providers.php, so the Notifier singleton is likely unregistered. `ApiServiceProvider` also imports `LanguageSwitch` unused. Tag: confirmed. Evidence: bootstrap/providers.php; both files.
- `config/filament.php` `'locales' => ['pl']` differs from the switch's `['en','pl']`. Tag: ambiguous. This is likely an unrelated setting.

## Leads outside scope
- Cache lock `company_account_lock_{id}` (30 min) is the only other per-user transient state in the profile flow (F-09).
- `UserNotification` rows and their "Konto usunięte" display for deleted users belong to F-02.
- Otomoto `BulkActionTracker` run ownership and notifications (another module).
- Hard-coded email gate on the Activity Log plugin (permissions, probably the Changes module).

## Could not establish
- Actual default bell polling interval and avatar provider: not read in vendor/filament. A runtime check would settle it.
- Whether Jetstream/Fortify routes (`/user/profile`, `/register`, etc.) are actually reachable in production: needs a route list, which was not run (V1 is read-only).
- Whether the literal "test" is visible in the UI: runtime would settle it.

## Coverage ledger
- Searched: userMenuItems, databaseNotifications, polling, LanguageSwitch/locale/session/cookie, avatar/HasAvatar/getFilamentAvatarUrl/profile_photo, navigation-menu/x-app-layout/profile.show, sendToDatabase/notifyNow/toDatabase, schedule/prune, canView, CustomProfile, notifyOfferCreatedByUser.
- Examined: app/Providers/Filament/AdminPanelProvider.php; app/Filament/Utils/PanelConfig.php; app/Providers/AppServiceProvider.php; app/Filament/Widgets/CustomAccountWidget.php; resources/views/filament/widgets/accout-widget.blade.php; app/Filament/Widgets/AllGlassWidget.php; app/Filament/Resources/OfferResource/Widgets/OfferStatsOverview.php; LatestOffers.php (user lines only); app/Filament/Pages/ProfilePage.php; resources/views/filament/pages/profile-page.blade.php; resources/views/filament/pages/my-offers.blade.php; app/Livewire/CustomProfileComponent.php and its view; resources/views/components/shout.blade.php; resources/views/navigation-menu.blade.php (head); resources/views/test.blade.php; app/Models/User.php; app/Models/UserNotification.php; app/Services/User/UserNotificationService.php; app/Services/Notifier/Notifier.php; app/Providers/Filament/PanelNotificationProvider.php; the notifications and user_notifications migrations; routes/web.php; routes/console.php (grep); config/jetstream.php; config/fortify.php; bootstrap/providers.php; vendor/bezhansalleh/filament-language-switch/src (LanguageSwitch, ServiceProvider, Middleware).
- Matched, not examined: resources/views/profile/* (listed, not read, as they are only reachable via Jetstream's profile.show); resources/views/dashboard.blade.php, api/index.blade.php, layouts/app.blade.php (matched by the grep only); BulkActionTracker, BulkSynchProductToWooAction, ActivateFromDraftAction (sender lines only; their business semantics belong to other modules); app/Providers/JetstreamServiceProvider.php (route grep only); Jetstream vendor routes.
