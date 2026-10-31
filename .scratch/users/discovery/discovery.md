# Users — discovery

<!-- Working document for the module-discovery skill. Only the manager writes it.
     Item ids are stable and never reused. Every finding cites a report (rNN) and its V1 evidence.
     Tags: confirmed · observed · inferred · ambiguous · unknown · defect? · decide -->

## A. Metadata

- **Status:** spec-written
- **Next actions:** Discovery complete (D-01..D-47; spec audited by r11). Next: architecture process for Users, from `.scratch/users/functional-spec.md`.
- **Started:** 2026-10-31 · **Last updated:** 2026-10-31
- **V1:** `/home/kp/laravel/projekty/AllGlass/` @ `511ce3b` (dirty: `M docker-compose.yml`, `?? SECURITY_INCIDENT_2026-10.md`, both irrelevant to behaviour)
- **V2 state:** exists in `app/Modules/Users`. It predates this discovery and comes from an earlier port. The user asked for a fresh start, so it is **not evidence** and does not limit scope.
- **Prior material:** `docs/archive/v1-port/refactor-plan.md` (phase table marks Users "Done" and has no Users scan; the boundary table does not mention Users). There is no `docs/archive/v1-port/modules/users/`, no `GLOSSARY.md`, no `docs/adr/`, and no neighbouring module under `.scratch/` has been discovered yet. All prior claims are leads, tagged `inferred`.

### Scope

- **Apparent responsibility:** people who use the panel: accounts (individual or company), signing in and security (password, 2FA, tokens, sessions), their own profile, roles and permissions, admin user management including impersonation. Provisionally also a user's own journal and notifications.
- **Anchors:**
  - *Identity and account:* `app/Models/User.php`; migrations `0001_01_01_000000_create_users_table`, `2024_10_06_123448_add_two_factor_columns_to_users_table`, `2024_10_06_123456_create_personal_access_tokens_table`, `2024_10_23_174738_add_custom_fields_to_users_table`, `2025_09_02_55555_add_shipping_information_to_user`; `app/Filament/Resources/UserResource.php` + `UserResource/Pages/*` (List, Create, Edit, View, EditProfile) + `UserResource/RelationManagers/*` (Offers, UserChanges); `app/Filament/Pages/ProfilePage.php`; `app/Policies/UserPolicy.php`; `app/Actions/Fortify/*`, `app/Actions/Jetstream/DeleteUser.php`; `app/Providers/FortifyServiceProvider.php`, `JetstreamServiceProvider.php`, `Filament/AdminPanelProvider.php` (auth and panel parts); config `auth.php`, `fortify.php`, `jetstream.php`, `sanctum.php`, `session.php`, `filament-edit-profile.php`; `routes/web.php`, `routes/api.php`.
  - *Access control:* migration `2024_10_06_133235_create_permission_tables`; `app/Filament/Resources/Shield/RoleResource.php` + pages; `app/Policies/RolePolicy.php`; `database/seeders/ShieldSeeder*.php`; config `permission.php`, `filament-shield.php`; `User::isAdmin/isSuper/getAdmins`, `HasPanelShield`; impersonation (`stechstudio/filament-impersonate`).
  - *Journal and notifications (provisional):* `app/Models/UserJournal.php`, `app/Models/UserNotification.php`; migrations `2024_12_01_180306_create_user_journals_table`, `2025_03_29_174632_create_user_notifications_table`; `app/Filament/Resources/UserJournalResource.php` + pages; `app/Policies/UserJournalPolicy.php`; `app/Filament/Enums/JournalType.php`; `app/Livewire/Users/ListUsersNotifications.php`; `app/Services/User/UserNotificationService.php`; `User::createJournalEntry()`.
- **Vocabulary:** `User`, `App\Models\User`, `users`, `user_id`, `auth()->user()`, `Auth::`, `company_account`, `company_name`, `company_nip`, `company_address`, `shipment_address`, `shipping_information`, `city_code`, `has_accepted_terms`, `custom_fields`, `profile_photo_path`, `two_factor_*`, `password_reset_tokens`, `sessions`, `personal_access_tokens`, `isAdmin`, `isSuper`, `getAdmins`, `isCompanyAccount`, `getTotalSalesAmount`, `getTotalQuantitySold`, `hasRole`/`assignRole`/`roles`/`permissions`/`can(`, `super_admin`, `panel_user`, `Shield`, `RoleResource`, `Impersonate`, `UserJournal`, `user_journals`, `createJournalEntry`, `JournalType`, `UserNotification`, `user_notifications`, `UserNotificationService`, `ListUsersNotifications`; Polish labels `Mój profil`, `Mój dziennik`, `Użytkownik`/`Użytkownicy`, `Rola`, `Firma`, `NIP`; routes `/login` → `admin-login`, `/admin/login`, `/api/user`.
- **Boundary assumptions:** the archive boundary table is silent on Users. Assumed: authentication, profile, roles/permissions and impersonation belong here. `UserJournal` and `UserNotification` are **provisionally in**, since they are named after the user, but they may really be offer-lifecycle concerns; the reports must show who writes them (see G-01).
- **Out of scope:** offers and the user-scoped offer pages (`MyOffers`, `UserReservedOffersPage`, `UserInquiriesPage`, `UsersOtomotoOffers`, `AdminReservedOffersPage`) belong to Offers/Otomoto, recorded only as `X-` dependencies. `ChangesLog` (`app/Modules/ChangesLog`) belongs to Audit, and the `UserChanges` relation manager is recorded as `X-`. Sales totals belong to Offers (sales).
- **Limitations:** no V1 runtime and no production data access, so findings come from static reading.

## B. Module overview

Users covers the people who work in the AllGlass panel (`/admin`). That means signing in, a user's own profile, the private vs company account distinction (which affects how their offers are priced and shown), and admin management of accounts: list, detail, create, edit, delete and impersonate. It also covers roles and permissions, and two message channels: the per-user offer inbox "Mój dziennik" and the admin feed of account-type changes.

Main concepts:
- **Account**: name, e-mail, phone, company data, pickup address, shipping text for Otomoto.
- **Account type**: private or company. Company status needs both the company flag and the accepted seller declaration.
- **Role**: User, Admin or Super Admin.
- **Inbox entry** and **activity-feed row**.

Authorisation is in practice a two-way split, "admin" (Admin or Super Admin, decided by role name) vs everyone else. Permission checks only drive a few generated resources (r05).

Boundaries: the user is the owner key for offers, reservations, inquiries, submissions and Otomoto bulk actions. The account type feeds Offers pricing (+23% for private owners) and the WooCommerce resync. The shipping text feeds Otomoto adverts. Fortify/Jetstream self-service features (registration, reset, 2FA, API tokens, account deletion) are configured but not wired into the panel. Whether they are reachable at all is open (Q-10). A V1 security incident (Oct 2026) bears on how account data and credentials should migrate (G-08).

## C. Functional inventory

| ID | Functionality | Entry points | Workflow | Verified | Confidence | Open |
|---|---|---|---|---|---|---|
| F-01 | User's offer inbox ("Mój dziennik") | Filament resource UserJournalResource (index only) | n/a: display + mark-read/delete; writers are W- of Offers (X-01) | spot (cascade) | high | G-01, Q-01, Q-02 |
| F-02 | Admin feed of user account-type changes ("Aktywność użytkowników") | Livewire table on user list page | W-01 | spot | high | Q-03 |
| F-03 | Panel access by role | panel login (every request) | n/a: single guard check, no side effects | spot | high | Q-04 |
| F-04 | Role management (create/edit/delete roles and their permission sets) | Shield RoleResource in Settings cluster | n/a: plain CRUD; saving replaces the permission set (described in F-04) | — | med | Q-05, Q-06 |
| F-05 | Assign a role to a user; automatic `User` role on account creation | user create/edit form, section "Rola"; model `created` hook | W-03, W-05 | — | med | Q-04 |
| F-07 | Sign in to the panel | `/admin/login` (Filament), `/login` → redirect | n/a: vendor login, rules BR-05, BR-23 | spot | high | Q-10 |
| F-08 | Edit own profile ("Mój profil") | ProfilePage, user menu "Edytuj profil", My-offers banner link | W-02 (+ W-01 branch) | spot | high | Q-13 |
| F-09 | Account type and seller declaration (private vs company) | ProfilePage toggle + declaration checkbox | W-01 | spot | high | Q-03, Q-11 |
| F-10 | Admin: list users | UserResource index | n/a: display; actions F-06, F-13 | — | high | Q-08 |
| F-11 | Admin: user detail (data, offer stats, offers tab, change-log tab) | UserResource view + 2 relation managers | n/a: display; change-log deletion is Audit's (X-06) | — | high | Q-17 |
| F-12 | Admin: create user | UserResource create | W-03 | — | med | Q-09 |
| F-13 | Admin: edit user | UserResource edit | W-03 | — | med | Q-09 |
| F-14 | Delete user | UserResource edit header "Delete"; Jetstream account deletion (reachability open) | W-04 | spot (FKs) | med | Q-08, Q-12 |
| F-15 | Self-service auth features (register, reset password, verify e-mail, 2FA, API tokens, delete own account, Fortify profile/password update) | Fortify/Jetstream vendor routes (live) | W-05 (registration); rest n/a: vendor flows outside the panel | r08 + spot | high | Q-10, Q-16 |
| F-16 | API: current user | `GET /api/user` (Sanctum) | n/a: returns the signed-in user, no conditions | — | high | — |
| F-17 | Second profile editor for any user (`profile/{record}`) | UserResource page EditProfile (URL only) | n/a: reachable by URL, no nav link | r08 | med | Q-14 |
| F-18 | Owner shipping text for Otomoto adverts | admin user form "Wysyłka" | n/a: stored text; consumer X-04 | spot | high | — |
| F-19 | Dashboard account widget (who I am, account type, logout) | CustomAccountWidget | n/a: display | r09 | high | Q-11 |
| F-20 | Notification bell (personal panel notifications) | panel `->databaseNotifications()` | n/a: Filament built-in; senders mapped in X- | r09 | high | G-10 |
| F-21 | Language switch (pl/en) | switcher next to global search | n/a: per-browser preference, no business effect | spot | high | — |
| F-22 | Personal dashboard offer stats | OfferStatsOverview, LatestOffers | n/a: display, Offers-owned (X-03) | r09 | med | Q-17 |
| F-06 | Impersonate another user | user list row action "Impersonate" | n/a: vendor action, no app-owned steps; rules in BR-09 | — | med | Q-07 |

### F-01 User's offer inbox ("Mój dziennik")

- **Purpose:** tells an offer owner what happened to their offers (accepted, rejected with reason, reserved, sold, archived…).
- **Description:** each user sees only their own entries, newest first, with an unread-count badge in the navigation. They can mark an entry read (eye icon), open "Szczegóły" (which also marks it read, shows the content, and offers "Przejdź do oferty"), and delete one or many entries. They cannot create or edit entries. If the offer is gone or Deleted, navigation is refused with a warning. Each new entry also pushes a panel (bell) notification with a "zobacz" link to the inbox.
- **Actors and permissions:** every user, their own entries only (query is scoped to the signed-in user, so admins see only theirs too). Delete is allowed for the owner only, otherwise 403. Policy is commented out, so Shield permissions decide (r02).
- **Entry points:** Filament resource `UserJournalResource` (list page only).
- **Workflows · rules · dependencies:** BR-01, BR-02, BR-03; X-01; Q-01, Q-02.
- **Evidence:** r03; `app/Filament/Resources/UserJournalResource.php:57-184`, `app/Filament/Actions/Abstracts/JournalAction.php:20-52`, `app/Models/User.php:156-173`.
- **Status:** mapped · **Confidence:** high for reading side; entry writers belong to the offer lifecycle (G-01).

### F-02 Admin feed of user account-type changes

- **Purpose:** lets admins notice when a user switches between private and company account.
- **Description:** when a user changes their account type in their profile, a feed row "Uzytkownik {name} zmienił rodzaj konta na: Firmowe|Prywatne" is stored, and every admin gets a panel notification with the same text. Admins see the feed, for all users and newest first, on the user list page. Each row links to the user, or shows "Konto usunięte" if the user is gone. Rows can be deleted singly or in bulk. Rows have no read state.
- **Actors and permissions:** written by any user editing their profile; read and deleted by whoever can open the user list (r02).
- **Entry points:** `app/Filament/Pages/ProfilePage.php:111-127 update()` (writer); `app/Livewire/Users/ListUsersNotifications.php` embedded in the ListUsers page (reader).
- **Workflows · rules · dependencies:** W-01; BR-04.
- **Evidence:** r03; spot-checked `ProfilePage.php:118-124`.
- **Status:** mapped · **Confidence:** high.

### F-03 Panel access by role

- **Purpose:** only accounts with a role entitled to the panel can use the application.
- **Description:** a signed-in account may use the panel only if it holds role `Super Admin` or `User`. An account holding only `Admin` is refused.
- **Actors and permissions:** all accounts.
- **Entry points:** panel authentication (`User implements FilamentUser`, `HasPanelShield::canAccessPanel`).
- **Workflows · rules · dependencies:** BR-05, BR-06; Q-04.
- **Evidence:** r02; spot-checked `vendor/bezhansalleh/filament-shield/src/Traits/HasPanelShield.php:26-29`, `config/filament-shield.php:20-30`.
- **Status:** verified · **Confidence:** high for the rule; whether real Admin accounts exist is unknown (G-03).

### F-04 Role management

- **Purpose:** define which roles exist and what each may do.
- **Description:** a permitted operator lists, views, creates, edits and deletes roles. The form has a unique name, a guard, a "select all" toggle, and tabbed checkboxes per resource (view, view_any, create, update, delete, delete_any), page and widget. Saving replaces the role's permission set. Built-in roles can be deleted with no guard, and deletion cascades to role assignments.
- **Actors and permissions:** needs the `*_shield::role` permissions (seeded for Super Admin only), and the Settings cluster also requires `isAdmin()` and the one hard-coded e-mail (BR-10).
- **Entry points:** `app/Filament/Resources/Shield/RoleResource.php` + pages (slug `shield/roles`, Settings cluster).
- **Workflows · rules · dependencies:** BR-07, BR-10; Q-05, Q-06.
- **Evidence:** r02; `RoleResource.php:25-113`, `Pages/CreateRole.php`, `Pages/EditRole.php`, `app/Filament/Clusters/Settings/Settings.php:17`.
- **Status:** mapped · **Confidence:** medium.

### F-05 Role assignment

- **Purpose:** give each account exactly one role.
- **Description:** every account created outside the console gets role `User` automatically, which is removed when the account is deleted. In the user create/edit form, an admin picks exactly one role from all roles (required). The section is hidden when editing an Admin or Super Admin, so admin roles can't be changed from the form, meaning promotion is possible but demotion is not. The user list shows the role as a coloured badge: Super Admin red, Admin green, User yellow.
- **Actors and permissions:** holders of `create_user` / `update_user` (seeded Admin and Super Admin).
- **Entry points:** `User::getForm()` section "Rola"; `HasPanelShield::bootHasPanelShield` (created/deleting hooks).
- **Workflows · rules · dependencies:** BR-05, BR-08; Q-04.
- **Evidence:** r02; `vendor/.../HasPanelShield.php:12-24` (spot-checked), `app/Filament/Resources/UserResource.php:99-105`.
- **Status:** mapped · **Confidence:** medium (create-then-sync interplay not traced).

### F-06 Impersonation

- **Purpose:** an admin can act as another user, e.g. to see what they see.
- **Description:** a row action on the user list switches the session to the target user and redirects to the dashboard. It is hidden only for oneself and while already impersonating. While impersonating, a banner offers to leave, returning to one's own account (`/filament-impersonate/leave`). Impersonating a user without panel access (e.g. Admin-only) ends on a 403 page with no banner, so leaving requires typing the URL. Nothing records impersonation.
- **Actors and permissions:** anyone who can open the user list (`view_any_user`: seeded Admin, Super Admin).
- **Entry points:** `app/Filament/Resources/UserResource.php:~118 Impersonate::make()`.
- **Workflows · rules · dependencies:** BR-09; Q-07.
- **Evidence:** r02, r08 (claims 11, 12).
- **Status:** verified · **Confidence:** high.

### F-07 Sign in to the panel

- **Purpose:** authenticated access to the application.
- **Description:** e-mail and password sign-in on the stock panel login page. The panel is marked noindex/nofollow. `/` and `/login` redirect there. After sign-in, panel access requires a permitted role (F-03). Sessions are kept server-side for 120 minutes and do not end on browser close. Changing the password ends the user's other sessions. There is no UI to list or revoke sessions.
- **Actors and permissions:** anyone with an account.
- **Entry points:** `AdminPanelProvider.php:75 ->login()`; `routes/web.php:5-9`.
- **Workflows · rules · dependencies:** BR-05, BR-23; Q-10 (Fortify login throttle applies only to Fortify's own route, not to the panel login).
- **Evidence:** r01, r04.
- **Status:** mapped · **Confidence:** high.

### F-08 Edit own profile

- **Purpose:** users maintain their own contact, company and password data.
- **Description:** every signed-in user opens "Mój profil" (also via the user menu "Edytuj profil" and a banner on "My offers"). Editable fields: name, e-mail, phone, account type (F-09), company name, NIP, address, pickup/shipping address ("Adres do wysyłki", hint "Jeżeli inny niż adres firmy"), city, postal code, seller declaration, new password + confirmation (a generator fills both with 20 random characters). "Zapisz" validates, saves and shows "Zapisano dane profilu". A badge "i" appears in navigation while the user is not a company account. Users cannot edit their own Otomoto shipping text (F-18).
- **Actors and permissions:** every signed-in user (no permission check).
- **Entry points:** `app/Filament/Pages/ProfilePage.php`.
- **Workflows · rules · dependencies:** W-02, W-01; BR-15; Q-13.
- **Evidence:** r01, r04; spot `ProfilePage.php:111-127`.
- **Status:** mapped · **Confidence:** high.

### F-09 Account type and seller declaration

- **Purpose:** distinguish VAT-registered businesses (company) from private sellers. This affects offer pricing and public visibility.
- **Description:** the user toggles "Konto Firmowe" / "Konto prywatne". For company accounts, NIP and address become required, and the seller declaration "Oświadczenie Sprzedawcy" ("Oświadczam, że jestem zarejestrowanym czynnym podatnikiem podatku VAT…") must be accepted. For private accounts, a notice reads: "Korzystając z konta prywatnego Twoje oferty będą widoczne tylko dla Ciebie i pracowników allGlass. Zmień typ konta na firmowe, aby wyświetlać oferty w katalogu publicznym". A modal warns that private sellers' prices are increased by VAT before publication (whether it opens is unknown). After a change, the toggle is locked for 30 minutes.
- **Actors and permissions:** the user themself; admins cannot set it in the user form (BR-16).
- **Entry points:** ProfilePage.
- **Workflows · rules · dependencies:** W-01; BR-04, BR-12, BR-13, BR-14, BR-24; X-02, X-03; Q-03, Q-11.
- **Evidence:** r01, r04; spot `User.php:177-180`, `OfferPriceService.php:13-20`.
- **Status:** mapped · **Confidence:** high (rule); medium (downstream resync).

### F-10 Admin: list users

- **Description:** a table with name and e-mail (searchable), account type ("Firmowe" green / "Prywatne" amber), role badge, and join date (sortable). There are no filters and no bulk actions. Row click opens the detail. Row actions are Impersonate (F-06) and Edit, and the header has Create. The page also hosts the account-type feed (F-02).
- **Actors and permissions:** `view_any_user` (seeded Admin, Super Admin).
- **Entry points:** `UserResource.php:75-132`, `Pages/ListUsers.php`.
- **Evidence:** r01. **Status:** mapped · **Confidence:** high. Role badge crash: Q-08.

### F-11 Admin: user detail

- **Description:** name, e-mail, phone, join date; company block (company name, NIP, address, shipping address, city, postal code); offer stats: "Wszystkie oferty" (count), "Aktywne oferty" (Active count), "Łączna wartość ofert" (sum of price over all offers, any status, zł). Neither the account type nor the Otomoto shipping text is shown. Tab "Powiązane oferty": the user's offers with filters, a link to each offer, and a bulk delete of offers. Tab "Rejestr zmian użytkownika": change-log entries made by this user, viewable, deletable singly or in bulk.
- **Actors and permissions:** `view_user`; deletion inside tabs depends on Offers / Audit permissions.
- **Entry points:** `Pages/ViewUser.php`, `Resources/Shared/OffersRelationManager.php`, `UserResource/RelationManagers/UserChangesRelationManager.php`.
- **Workflows · rules · dependencies:** X-03, X-06.
- **Evidence:** r01. **Status:** mapped · **Confidence:** high.

### F-12 Admin: create user

- **Description:** form sections. "Rola": exactly one role, required. "Dane użytkownika": name (required, unique, ≤255, no "_"), e-mail (required, valid, unique), password (required, generator and copy button), phone. "Dane firmy": all optional. "Wysyłka szyb": rich text (F-18). The form has no account-type or declaration controls, so new users start as private. No e-mail is sent to the new user.
- **Actors and permissions:** `create_user`.
- **Entry points:** `User::getForm()` (`User.php:182-290`), `Pages/CreateUser.php`.
- **Workflows · rules · dependencies:** W-03; BR-16, BR-18; Q-09.
- **Evidence:** r01, r02. **Status:** mapped · **Confidence:** medium (role sync on create untraced).

### F-13 Admin: edit user

- **Description:** same form. A blank password keeps the old one, and the name-uniqueness check ignores the current record. The role section is hidden when the edited user is Admin or Super Admin.
- **Actors and permissions:** `update_user`, for any user including Super Admins (BR-11).
- **Entry points:** `Pages/EditUser.php`.
- **Workflows · rules · dependencies:** W-03; BR-11, BR-16.
- **Evidence:** r01. **Status:** mapped · **Confidence:** medium.

### F-14 Delete user

- **Description:** an admin deletes a user from the edit page. It is a hard delete (no archive). The Force delete / Restore actions shown are inert. Related data follows BR-17. A Jetstream self-deletion path exists in code (removes photo and tokens, then deletes), but its reachability is open.
- **Actors and permissions:** `delete_user`.
- **Entry points:** `Pages/EditUser.php` header; `app/Actions/Jetstream/DeleteUser.php`.
- **Workflows · rules · dependencies:** W-04; BR-17; Q-08, Q-12.
- **Evidence:** r01, r04; spot FKs. **Status:** mapped · **Confidence:** medium.

### F-15 Self-service auth features (Fortify / Jetstream)

- **Description:** these are live in V1 at their own URLs. No link from the panel points to them.
  - **Self-registration**, `/register`: open to anyone. It needs only name, unique e-mail and password, with no e-mail verification. The new account gets role `User` and can immediately use the panel (W-05).
  - **Password reset**, `/forgot-password` → e-mail → `/reset-password/{token}`: the token is valid 60 minutes. The panel login has no "forgot password" link. The committed mail driver is `log`, so whether e-mails really go out depends on production config (G-12).
  - **E-mail verification**: routes exist, but nothing requires a verified e-mail.
  - **Two-factor authentication**: can be enabled through Fortify's endpoints, but the panel login never asks for a code. That makes it ineffective.
  - **API tokens**, `/user/api-tokens`: reachable by every signed-in user, because verification isn't enforced. Default ability is `read`, tokens never expire, and the only API is F-16.
  - **Jetstream profile page** `/user/profile` (profile, password, 2FA, sessions, delete account): its layout calls a non-existent `dashboard` route, so it most likely errors (`inferred`, r09).
  - **Fortify profile/password updates**: PUT-only endpoints, used only by those Jetstream pages.
- **Evidence:** r01, r04, r08 (claims 1–5); spot `config/fortify.php` features + `views => true`, no `ignoreRoutes` anywhere.
- **Status:** verified · **Confidence:** high on registration and route presence; runtime behaviour of the Jetstream pages is `inferred`.

### F-16 API: current user

- **Description:** `GET /api/user` with a Sanctum token or session returns the signed-in user's data. It excludes password, remember token and 2FA fields, and adds the profile-photo URL. Tokens never expire.
- **Evidence:** r01, r04; `routes/api.php:6-8`. **Confidence:** high. No known consumer (G-09).

### F-17 Second profile editor (`profile/{record}`)

- **Description:** a UserResource page with name, e-mail, declaration toggle (required), company fields and password, for any user record. Its nav link is commented out. It is reachable by URL for holders of resource access, and lacks the account-type lock.
- **Evidence:** r01, r08 (claim 7): the route is registered, and editing needs `update_user` with no ownership check. Saving a new password there errors, because `Hash` is not imported (`EditProfile.php:124`, spot). It can set the declaration flag directly (W-01 alternative path). **Status:** verified · **Confidence:** medium.

### F-18 Owner shipping text for Otomoto adverts

- **Description:** admins write a per-user shipping text ("Wysyłka", helper: "Informacja wyświetli się w sekcji WYSYŁKA opisu otomoto dla ofert dotyczących tego użytkownika. Pozostaw puste aby użyć wysyłki z oferty/kategorii"). Otomoto adverts of that user's offers then show "Wysyłka:" + this text instead of the default shipping text.
- **Evidence:** r01, r04; `User.php:274-281`, `Offer.php:527-530`, `OtomotoCreationService.php:143-158`. **Confidence:** high.

### F-19 Dashboard account widget

- **Description:** the first dashboard tile shows the avatar (default initials, with no photo upload anywhere), "Użytkownik", the name, "Konto firmowe" / "Konto prywatne" (BR-12), and a logout button. It is hidden on narrow screens and has no link to the profile. The user menu has one custom item, "Edytuj profil".
- **Evidence:** r04, r09; `app/Filament/Widgets/CustomAccountWidget.php`, `accout-widget.blade.php:1-37`, `AdminPanelProvider.php:83-105`. **Confidence:** high.

### F-20 Notification bell

- **Description:** each user has a personal list of panel notifications with unread badge, mark-read, mark-all-read and clear (Filament built-in, default polling). It receives:
  - for admins: account-type changes (F-02), drafts activated by users, new offers and offer edits awaiting acceptance, and inquiry accept/reject;
  - for offer owners: every inbox entry (F-01) and new inquiries;
  - for whoever ran an Otomoto or WooCommerce bulk job: its progress and results.
  
  Notifications are never cleaned up automatically.
- **Evidence:** r05 §H, r09; `AdminPanelProvider.php:89`, migration `2024_10_24_161428_create_notifications_table.php`. **Confidence:** high. Ownership: a cross-cutting messaging channel used by Offers, Otomoto, WooCommerce and Users (G-10).

### F-21 Language switch

- **Description:** a switcher offers Polish and English. The choice is kept per browser (session plus permanent cookie), not per user. Nearly all UI text is hard-coded Polish, so only Filament's own labels change. The default is Polish.
- **Evidence:** r09; spot `AppServiceProvider.php:42-45`. **Confidence:** high.

### F-22 Personal dashboard offer stats

- **Description:** "Moje oferty" (total, this month, trend), "Aktywne oferty" (with pending count) and "Odrzucone oferty", each linking to "My offers" filtered. Also the user's latest offers. The sales-value stat is defined but not shown. Offers-owned; recorded because it is per-user.
- **Evidence:** r09; `OfferStatsOverview.php:12-85`, `LatestOffers.php:35,94`. **Confidence:** medium. This supersedes r04's claim that the dashboard shows per-user sales totals (Q-17 now concerns dead code only).

## D. Business rules and domain knowledge

### Confirmed (`confirmed` / `observed`)

- **BR-01** An inbox entry is created only for an existing offer owner, with an offer and one of the types Info / Success / Warning / Rejection. Otherwise no entry is created, but the offer status change still happens. Applies to F-01. Evidence: r03, `User.php:156-163`, `JournalAction.php:20-33`.
- **BR-02** Inbox entries are deleted together with their user (FK cascade). Account-type feed rows survive user deletion, since `user_id` is nullable with no FK constraint. Applies to F-01, F-02. Evidence: r03; spot-checked `2024_12_01_180306_create_user_journals_table.php:18`, `2025_03_29_174632_create_user_notifications_table.php:16`.
- **BR-03** Inbox entry types only drive colour and icon: Info=primary, Success=success, Warning=warning, Rejection=danger. No writer of `Warning` has been seen. Evidence: r03, `app/Filament/Enums/JournalType.php`.
- **BR-04** Changing the account type (private ↔ company) sets a 30-minute lock flag `company_account_lock_{id}`, records the F-02 feed row, notifies all admins, and resyncs the user's WooCommerce-published offers. Evidence: spot-checked `ProfilePage.php:118-124`, `app/Services/Offer/OfferUpdateService.php:11-18`. The lock's reader and the resync's effect are not yet traced (G-02).

- **BR-05** Three roles exist: `User`, `Super Admin`, `Admin` (guard `web`). The live seeder replaces each role's permission set. `ShieldSeeder_old`/`_old2` are dead. The installation seeder creates one Super Admin from env `ADMIN_EMAIL`/`ADMIN_PASSWORD`. Evidence: r02, `database/seeders/ShieldSeeder.php:15-52`, `DatabaseSeeder.php:19-40`.
- **BR-06** Seeded permissions (r02 matrix; second source: manager parse of the live `ShieldSeeder.php` JSON, User 8 / Admin 12 / Super Admin 133 permissions): `User` has view/view_any/create/update/delete on offers, `view_activitylog`, and view/delete on its own inbox. `Admin` has only the 12 user-management permissions. `Super Admin` has everything on users, cars, offers, offer taxonomies, activity log, submissions, inbox and roles, plus the settings/profile/offers pages and two dashboard widgets. Evidence: r02, `ShieldSeeder.php:15-16`.
- **BR-07** `Super Admin` has no automatic bypass. It passes only checks for permissions it holds, and `define_via_gate=false` with no `Gate::before` in the app. Evidence: r02; spot-checked `config/filament-shield.php:20-24`.
- **BR-08** Administrative behaviour outside user management is decided by role name, not permission: `isAdmin()` = Admin or Super Admin, and `getAdmins()` = all such users (used to notify admins). Evidence: r02; spot-checked `app/Models/User.php:132-148`. Full list of such gates: r05.
- **BR-09** Impersonation: anyone who can see the user list may impersonate any user except themselves, Super Admins included, but not while already impersonating. Afterwards they land on the dashboard. Impersonation is not logged (r08). Evidence: r02, `UserResource.php:~118`, vendor `Impersonates` concern.
- **BR-10** The Settings cluster (including role management) and the activity log are restricted to one hard-coded e-mail (`kpiechowski25@gmail.com`), and Settings also requires `isAdmin()`. Evidence: r02, `app/Filament/Clusters/Settings/Settings.php:17`, `app/Filament/Utils/PanelConfig.php:86-89`.
- **BR-26** Anyone who can create or edit users can give any role, including `Super Admin`, because the role selector lists all roles. Evidence: r07 lead, r02 (`User::getForm()` roles select, no option filter).
- **BR-27** Anyone can self-register at `/register` and immediately gets role `User` and panel access. No verification or approval is required. Evidence: r08 claims 1–2; spot `config/fortify.php`, `HasPanelShield.php:12-24`.
- **BR-11** User-management rights are per ability, not per record: whoever holds `update_user`/`delete_user` can edit or delete any user, Super Admin included. Evidence: r02, `app/Policies/UserPolicy.php`.

- **BR-12** A user counts as a **company account** only if the company flag is on **and** the seller declaration is accepted. Otherwise they are private. Evidence: r01, r04; spot `User.php:177-180`.
- **BR-13** (Offers-owned, recorded as a dependency) A private owner's gross offer price is net × 1.23, rounded to 2 decimals half-up. A company owner, or an offer with no owner, keeps the price unchanged. Evidence: r04; spot `OfferPriceService.php:13-20`.
- **BR-14** After an account-type change, the toggle is disabled for 30 minutes ("Zmiana rodzaju konta jest zablokowana do: HH:MM"). The lock is cache-only and does not bind admins. Evidence: r01, r04; `ProfilePage.php:118-124,193-198`.
- **BR-15** Profile form: name, e-mail and phone are required. E-mail has no format or uniqueness check (Q-13). NIP and address are required and visible only for company accounts, as is the declaration, which must be accepted. A blank password leaves it unchanged, and a given password must match its confirmation. Evidence: r01.
- **BR-16** Admin user form: name required, unique, ≤255, must not contain "_"; e-mail required, valid, unique; password required on create, blank on edit keeps it; exactly one role; company fields optional; no account-type or declaration controls, so admin-created users are private until they change it themselves. Evidence: r01, r02; `User.php:182-290`.
- **BR-17** Deleting a user is permanent, and all their role assignments go with it. Their inbox entries are deleted. Change-log entries, submissions, inquiries and Otomoto bulk-action records keep existing with no user. Offers, reservations, settings rows and feed rows keep a dangling owner id (no constraint). Evidence: r04; spot migrations (`create_offers_table.php:49`, `create_offer_reservations_table.php:19`, `create_settings_table.php:18`, `create_submissions_table.php:18`, `create_changes_logs_table.php:15`, `create_otomoto_bulk_actions_table.php:12`, `create_offer_inquiries_table.php:21`).
- **BR-18** Every account created through the application (not the console) automatically gets role `User`, which is removed when the account is deleted. Evidence: r02; spot `HasPanelShield.php:12-24`. Interaction with the form's role choice: Q-09.
- **BR-19** The activity log records changes to a user's name and e-mail only. Evidence: r01, `User.php:150`.
- **BR-21** (Offers-owned, dependency) Non-admins see only their own offers, reservations, inquiries and Otomoto adverts, and can open only their own non-trashed offers. Admins see everything. On create, a non-admin is always the owner. Evidence: r04, r05 matrix #6–14.
- **BR-22** Admin vs non-admin is decided by role name at about 60 places. The Offers cluster, full offer list, admin reservations, admin inquiries and Otomoto admin screens are admin-only. The user-side reservations, inquiries and Otomoto pages are non-admin only. Non-admin offer saves go to Pending, and non-admin taxonomy proposals default to Pending. `isSuper()` (Super Admin only) gates only revealing API secrets in ApiSettings. Shield permissions drive only the generated policies (users, roles, cars, submissions, activity log, taxonomies). Evidence: r05 matrix; spot `Clusters/Offers/Offers.php:26-28`.
- **BR-23** Sessions are stored server-side, last 120 minutes, and survive browser close. A password change ends the user's other sessions. Evidence: r01, `config/session.php`, `AuthenticateSession` middleware.
- **BR-24** Seller declaration text: "Oświadczam, że jestem zarejestrowanym czynnym podatnikiem podatku VAT. Jednocześnie oświadczam, że dokonuje sprzedaży oferowanych towarów w ramach prowadzonej przeze mnie działalności gospodarczej." Only a yes/no flag is kept, with no acceptance date or version. Evidence: r01.

### Inferred or questionable

- **BR-25** Mass-assignment protection is globally disabled (`AppServiceProvider.php:40`, spot-checked), so any submitted attribute can be written. Implementation fact; no behavioural rule follows. Recorded so the spec doesn't assume field-level write protection existed.

### Domain terms

- **Mój dziennik** (journal, "inbox"): per-user list of messages about the user's own offers. UI strings contain typos kept verbatim ("Wiadomośc", "Częśc", "Uzytkownik").
- **Rodzaj konta**: account type, **Prywatne** (private) or **Firmowe** (company).
- **Aktywność użytkowników**: the admin feed of account-type changes (F-02).
- **Konto firmowe / Konto prywatne** (also "Firmowe"/"Prywatne"): company / private account (BR-12).
- **Oświadczenie Sprzedawcy**: seller (VAT) declaration (BR-24).
- **Adres do wysyłki / Adres odbioru**: the user's pickup/shipping address (plain field). Not the same as **Wysyłka**, the per-user shipping text for Otomoto adverts (F-18).
- **Data dołączenia**: join date (account creation).
- **Rola**: role. **User** = ordinary panel user (seller); **Admin** = user-management tier, treated as admin by name checks; **Super Admin** = full permission set.

## E. Functional workflows

### W-01 User changes account type in their profile

- **Trigger / entry points:** user saves "Mój profil" (`ProfilePage::update()`) with a different account type. Clicking "private" first opens the informational modal "Pamiętaj!" (VAT will be added to private sellers' prices before they enter the public catalog; button "Rozumiem"), which appears even if the user never saves.
- **Preconditions:** signed in. The toggle is not locked (BR-14; a locked toggle is disabled and its value not submitted, which is `inferred` from Filament). BR-15 validation: switching to company requires NIP, address and the accepted declaration.
- **Main flow:** 1. Validate. 2. Detect that the type changed. 3. Set the 30-minute lock. 4. Store the feed row and notify every Admin/Super Admin synchronously (F-02). 5. Queue one resync job per 10 of the user's offers that have a WooCommerce link. This covers any offer status, despite the name "published". 6. Save the profile. 7. "Zapisano dane profilu".
- **State changes:** user row; `user_notifications` row; cache lock; admin bell notifications.
- **Side effects and external interactions:** in the worker, each offer is pushed in full to WooCommerce (images, terms, category, product). The only field that depends on the account type is the **regular price**: net for company, net × 1.23 for private. Woo publish/draft status comes from a setting, not the account type. The job reloads offers and owner when it runs, so it normally sees the **new** type (Q-03). Offers without a Woo link are not pushed. The gross price is not stored anywhere; it is computed on display, so V1 screens change instantly. Otomoto adverts are **not** repriced (Q-20).
- **Error scenarios:** no transaction. Steps 3–5 are committed before step 6, so if the save fails the user stays locked and admins are told of a change that didn't happen. In the job, a caught Woo HTTP error ends quietly with no retry. A Woo "invalid product id" error deletes the offer's Woo link. Other exceptions fail the job (one attempt). The user is never told of resync failures.
- **Alternative paths:** admins cannot change the account type in the user form. The second profile editor (F-17) can set the declaration flag for any user, which changes company status per BR-12 without the lock, feed row or resync (`EditProfile.php:67`; reachability r08).
- **Evidence:** r03, r06; spot `ProfilePage.php:111-127`, `OfferUpdateService.php:11-18`, `WooProductDTO.php:55-70`. Trace stopped at the WooCommerce REST API (external) and the WordPress side (whether the shop hides private owners' products: G-11).

### W-05 Self-registration

- **Trigger / entry points:** `GET/POST /register` (Fortify; `resources/views/auth/register.blade.php`).
- **Preconditions:** none. The visitor is anonymous.
- **Main flow:** 1. Validate name, unique e-mail, password + confirmation (the terms checkbox is not active). 2. Create the account with name, e-mail and hashed password only. 3. The system assigns role `User` (BR-18). 4. Sign the user in. The user can open `/admin` as an ordinary private user: create offers, use the inbox, etc.
- **State changes:** new user row and role assignment.
- **Side effects:** none: no admin notification, no feed row, no verification e-mail requirement.
- **Evidence:** r01, r08; spot `config/fortify.php` features. Trace stopped at Fortify vendor code (post-registration redirect target not established).

### W-02 User saves their profile

- **Trigger / entry points:** "Zapisz" on ProfilePage.
- **Preconditions:** signed in; BR-15.
- **Main flow:** 1. Validate. 2. If the account type changed, run W-01 steps. 3. Save all form data on the user, including the hashed password if given. 4. Toast "Zapisano dane profilu".
- **State changes:** user row. A password change ends other sessions (BR-23). A name/e-mail change goes to the activity log (BR-19).
- **Error scenarios:** a duplicate e-mail fails at the database (Q-13).
- **Evidence:** r01; spot `ProfilePage.php:111-127`. Trace stopped at the activity log (Audit).

### W-03 Admin creates or edits a user

- **Trigger / entry points:** UserResource Create / Edit.
- **Preconditions:** `create_user` / `update_user`; BR-16.
- **Main flow (create):** in one DB transaction: 1. Validate. 2. Hash the password. 3. Save the user with all form fields. 4. The system auto-assigns role `User` (BR-18). 5. The role selector then removes every role not chosen and assigns the chosen one, so the end state is exactly the chosen role. 6. The activity log records name and e-mail.
- **Main flow (edit):** in one DB transaction: 1. Validate. 2. Set roles to exactly the chosen one, before the data update. 3. Update the data; a blank password keeps the old one. The role section is hidden, so roles are untouched, when the edited user is already Admin or Super Admin.
- **State changes:** user row, role assignment.
- **Consequence:** creating a user as `Admin`, or promoting a `User` to `Admin`, leaves them with only `Admin`, so they **cannot open the panel** (F-03, Q-04). Their role can then no longer be changed in the form.
- **Side effects:** no e-mail to the user; no notification.
- **Evidence:** r07 (vendor `CreateRecord.php:92-109`, `EditRecord.php:143-156`); spot `vendor/filament/forms/src/Components/Select.php:1032-1042`, `HasPanelShield.php:12-24`. r07's claim that non-`$fillable` fields are dropped is **refuted** by the global unguard (BR-25, spot-checked).

### W-04 Delete user

- **Trigger / entry points:** EditUser header "Delete" is the only working path. The list's row delete is commented out. Force delete / Restore are hidden because there is no archive state. Jetstream's own deletion action (photo + API token cleanup) is not used by the panel.
- **Preconditions:** `delete_user`; any user, including Super Admins and oneself (no guard found).
- **Main flow:** 1. Role `User` removed (BR-18). 2. The row is deleted permanently, with no explicit transaction. 3. All role assignments are removed. 4. Database rules act (BR-17).
- **State changes:** BR-17. In addition, the user's sessions, API tokens, versions and profile photo are left behind.
- **Side effects:** none (no notification, no log beyond the activity log).
- **Error scenarios:** a deleted user's open session most likely ends at their next request (`inferred`). Offers screens may fail for orphaned offers (Q-12).
- **Evidence:** r04, r07; spot FK migrations.

## F. Cross-module dependencies

- **X-01** Offers → Users: offer-lifecycle transitions write inbox entries for the offer owner. The transitions are created, draft created, activated from draft, accepted, rejected (with reason), amount reserved, fully reserved, amount sold, fully sold and archived. Each also changes offer status. Writers live in `app/Filament/Actions/Journal/*`, called from AdminTab, AcceptOffersBulkAction, ViewSubmission, MyOffers, OfferCreatedByUserAction, OfferStockChangeAction, OfferReservationChangeAction and StockSaleTab. Evidence: r03 (titles and status effects partly from grep, `inferred` until r05 or a flow mission confirms).
- **X-03** Offers ← Users: the user is the owner of offers, reservations and inquiries. The account type (BR-12) drives private-owner pricing (BR-13), "Firmowe/Prywatne" labels and colours, and which offer fields or sections show (`OfferCreateForm.php:259`, `OfferEditForm.php:294`, `ViewOffer.php:131,245`). Ownership scoping is BR-21. `getAdmins()` is the recipient list for admin notifications on offer create/edit, draft activation and inquiry accept/reject. Many Offers screens link the owner's name to the user detail. The dashboard shows per-user sales totals (`getTotalSalesAmount` uses the offer's current price, not the price at sale, see Q-17). Evidence: r04, r05.
- **X-04** Otomoto ← Users: the owner's shipping text replaces the default shipping line in advert descriptions (F-18). Otomoto bulk actions record and notify the acting user. Evidence: r04.
- **X-05** WooCommerce import → Users: imported offers and reservations are owned by the user chosen in Settings → Api "Uzytkownik dla zaczytanych ofert" (all users listed). Fallback is user id 1. Evidence: r04, `FetchWooProducts.php:114,147`, `ApiSettings.php:143-150`. Dispatcher untraced.
- **X-06** Audit ↔ Users: change-log entries record the acting user. The user detail tab lets entries be deleted. The activity log covers name/e-mail changes and is visible only to one hard-coded e-mail (BR-10). Evidence: r01, r04, r05.
- **X-07** Settings ↔ Users: the Settings cluster is gated by admin role plus the hard-coded e-mail. `settings.user_id` exists with no constraint. Inbox message texts may be configured in Settings → "Powiadomienia dziennika" (`MessagesSettings.php:93-125`; contradiction Q-15). Evidence: r04, r05.
- **X-08** Submissions / Inquiries ← Users: offer submissions and inquiry events notify all admins (`getAdmins`). Submissions keep existing with no user after deletion. Evidence: r04, r05.
- **X-02** Users → Offers/WooCommerce: an account-type change triggers a resync of the user's WooCommerce-synced offers (W-01). Evidence: spot-checked `OfferUpdateService.php:11-18`.

## G. Ambiguities, contradictions and questionable behaviour

### Q-01 Inbox content rendered as raw HTML, including admin free text — `defect?`

- **Evidence:** r03; `resources/views/filament/components/journalEntryModal.blade.php:3` (`{!! !!}`), `JournalAction.php:31` appends the admin's reject or accept reason.
- **Interpretations:** a) intentional, to allow formatting in fixed messages; b) an oversight that lets admin input inject HTML.
- **Question for the user:** should inbox messages be plain text, or allow limited formatting?
- **Resolution:** open

### Q-02 Small inbox defects — `defect?`

- **Evidence:** r03. a) The "Szczegóły" submit label checks `status` on the inbox entry, which has none, so it always reads "Przejdź do oferty" (`UserJournalResource.php`, `modalSubmitActionLabel`). b) The "zobacz" link passes an unused `resource_id`, so it opens the list, not the entry (`JournalAction.php:47`). c) `createJournalEntry` is called with 5 arguments but takes 4. d) `AdminTab.php` imports a non-existent `Journal\OfferReservedAction`.
- **Interpretations:** dead or broken leftovers; the intended behaviour of b) was probably "open this entry".
- **Question for the user:** should the bell notification open the specific entry?
- **Resolution:** open

### Q-04 `Admin` role cannot enter the panel — contradiction / `decide`

- **Evidence:** r02; spot-check `HasPanelShield.php:26-29` (only Super Admin or User pass); the form allows exactly one role; `isAdmin()`/`getAdmins()` treat Admin as the admin tier; seeded Admin has only user-management permissions.
- **Update (r07, spot `Select.php:1032-1042`):** the admin form always leaves exactly one role, so any account made Admin through the UI holds only `Admin` and is locked out (W-03). Interpretation b) would need a console or DB path.
- **Interpretations:** a) the Admin role is unused in practice (only Super Admins exist); b) Admin accounts exist and also hold `User`, by some path other than the form; c) Admin is broken in V1.
- **Question for the user:** which roles do real people have today, and what should an "Admin" be able to do as distinct from a Super Admin?
- **Resolution:** open

### Q-05 Built-in roles deletable; RolePolicy stubs — `defect?`

- **Evidence:** r02. Roles `User`/`Admin`/`Super Admin` can be deleted with no guard, while code depends on their names (BR-08). `RolePolicy` restore/forceDelete/replicate/reorder check literal `'{{ Restore }}'` placeholders. Direct `*_role` permissions are seeded but never checked. `makeDirectPermissions` passes an array to `whereName`. `DatabaseSeeder` finds roles by id.
- **Question for the user:** should the built-in roles be protected from deletion and renaming?
- **Resolution:** open

### Q-06 Hard-coded operator e-mail — `decide`

- **Evidence:** BR-10.
- **Interpretations:** a) a developer safeguard that should become a configurable "system operator" concept; b) settings and roles really should be limited to one person.
- **Question for the user:** who should be able to manage settings, roles and the activity log?
- **Resolution:** open

### Q-07 Unrestricted impersonation, no audit — `decide`

- **Evidence:** BR-09, BR-11.
- **Question for the user:** may a lower tier impersonate a Super Admin? Should impersonation be logged?
- **Resolution:** open

### Q-08 Soft-delete actions without soft deletes; role badge crashes on unknown role — `defect?`

- **Evidence:** r02. `EditUser` header offers Delete/ForceDelete/Restore, and `getEloquentQuery` drops SoftDeletingScope, but `User` has no `SoftDeletes`. The role-badge `match` in `UserResource.php:99-105` has no default arm, so it throws for a user with no role or a custom role.
- **Question for the user:** should deleting a user be reversible (archive) or final?
- **Resolution:** open

### Q-09 Does a new user get role `User` on creation? — contradiction (settled in part)

- **Evidence:** r01 says no code assigns a role on creation, so self-registered users would be roleless. r02 says the HasPanelShield `created` hook assigns `User`. Manager spot-check of `HasPanelShield.php:12-24` confirms r02 for non-console creation.
- **Settled (r07, spot `Select.php:1032-1042`):** yes. The role selector removes the auto-assigned `User` whenever another role is chosen (W-03).
- **Resolution:** settled → BR-18, W-03; consequence in Q-04

### Q-10 Self-service auth features configured but not offered — `decide`

- **Evidence:** F-15. The V1 incident note (`SECURITY_INCIDENT_2026-10.md`, "Do zrobienia") lists disabling the Fortify `registration`, `resetPasswords` and `emailVerification` routes.
- **Update (r08, spot):** registration is **live** and grants immediate panel access (BR-27, W-05). The other routes are live too (F-15).
- **Question for the user:** in V2, should users be able to self-register, reset a forgotten password by e-mail, verify e-mail, manage API tokens, or delete their own account? Or are accounts created only by admins?
- **Resolution:** open

### Q-11 What makes a company account? Flags used inconsistently — `defect?` / `decide`

- **Evidence:** BR-12 needs both flags. The "My offers" private-account banner checks only the declaration flag (`my-offers.blade.php:3-7`). Switching to private leaves the declaration flag set. The admin form sets neither.
- **Question for the user:** is "company" one choice that requires the declaration, or two independent facts?
- **Resolution:** open

### Q-12 A deleted offer owner breaks Offers screens — `defect?`

- **Evidence:** BR-17 (offers keep a dangling owner). Several screens call the owner's account type without a null guard (`AdminReservedOffersTable.php:186`, `ReservedOffersTableSchema.php:97`, `MyOffersTableSchema.php:79`, `AdminTab.php:60`, `ViewOffer.php:131,245`, `OfferEditForm.php:294`), while others guard it. r04.
- **Question for the user:** what should happen to a user's offers, reservations and history when the user is removed: block deletion, reassign, archive the user, or delete?
- **Resolution:** open

### Q-13 Profile e-mail has no format or uniqueness check — `defect?`

- **Evidence:** r01, ProfilePage e-mail field `required()` only; DB unique.
- **Resolution:** open

### Q-14 Three role-management screens, and a second profile editor — `defect?` (settled)

- **Evidence:** r08 claim 6 (`inferred` from Filament/Shield source): two app copies plus Shield's own RoleResource are all registered under slug `shield/roles` in the "Ustawienia" cluster, which only the hard-coded e-mail can open (BR-10). Which one serves the URL needs runtime. The second profile editor is reachable (F-17).
- **Question for the user:** none on behaviour. V2 needs one role-management screen (whether at all: Q-06).
- **Resolution:** settled as duplication; behaviour per F-04

### Q-15 Are inbox texts fixed or configurable? — contradiction (settled)

- **Evidence:** r08 claims 8–9: inbox titles and contents are fixed strings in the Journal actions, with only the admin's reason appended. The Settings → "Powiadomienia dziennika" texts (`{type}_title`, `{type}_desc`) are saved but **never read**.
- **Question for the user:** should inbox texts be editable by staff in V2 (the unfinished V1 intent)?
- **Resolution:** settled on behaviour (fixed texts; settings dead); the intent question goes to grilling

### Q-16 Two-factor data exists but is never enforced — `decide`

- **Evidence:** F-15. The incident note says to clear `two_factor_*` before rotating APP_KEY.
- **Question for the user:** should V2 offer or require 2FA? Should existing 2FA data be dropped?
- **Resolution:** open

### Q-17 Per-user sales total uses current price — `defect?` (Offers-owned)

- **Evidence:** r01, `User::getTotalSalesAmount` = Σ sale quantity × offer's current price. r04 said it shows on the dashboard, but r09 finds the stat defined and not returned, so it is dead.
- **Resolution:** open; hand to Offers discovery

### Q-18 Panel prints the word "test" on every page — `defect?`

- **Evidence:** r09; spot `AdminPanelProvider.php:127-130` renders the Blade string `'test'` at BODY_START (not the view `test.blade.php`).
- **Resolution:** open (needs-runtime to see; trivially excluded from V2)

### Q-03 Side effects committed before the profile save — `defect?`

- **Evidence:** r06; spot `ProfilePage.php:118-127`. The lock, feed row, admin notifications and job dispatch all happen before the save, with no transaction. The resync normally reads the new type, because the job reloads data, but a fast worker could read the old one.
- **Question for the user:** none (implementation). The V2 spec should state that these effects follow only a successful change.
- **Resolution:** open (likely IMP)

### Q-19 Do private sellers' offers reach the public catalog? — contradiction / `decide`

- **Evidence:** r06. The profile notice for private accounts says offers are "widoczne tylko dla Ciebie i pracowników allGlass" and that switching to company is needed "aby wyświetlać oferty w katalogu publicznym". The "Pamiętaj!" modal says private prices get VAT added "przed dodaniem ich do katalogu publicznego", i.e. they **are** published. Code implements only the pricing. Nothing in V1 hides private owners' offers, and their WooCommerce products publish like any other.
- **Question for the user:** what is the intended rule for a private seller's offers: hidden from the public shop, or published with VAT added?
- **Resolution:** open

### Q-20 Account-type change doesn't reprice Otomoto adverts — `defect?` / `decide`

- **Evidence:** r06. Otomoto adverts compute price through the same rule at create/update time but are not re-pushed by W-01.
- **Question for the user:** should an account-type change reprice everything published (WooCommerce and Otomoto)?
- **Resolution:** open

## H. Missing information and gaps

| ID | What is unknown | Kind | Why | Resolution |
|---|---|---|---|---|
| G-01 | Do `UserJournal` and `UserNotification` belong to Users or to Offers? | needs-business | The boundary table is silent. r03: every inbox writer is an offer-lifecycle transition and every entry needs an offer, so the inbox reads as an Offers record delivered to a user. UserNotification is Users-only (account-type feed). | open: r03 informs; decide in grilling |
| G-03 | Real production roles, permission sets, and whether Admin accounts exist | needs-runtime | Roles can be edited live in RoleResource; seeders may not match the DB | open: user can check V1 DB (`roles`, `model_has_roles`, `role_has_permissions`) |
| G-04 | Does the impersonation package log or fire events, and how does one leave impersonation? | not-investigated | Vendor code not read | r08: not logged (events fired, no listener); leave via banner / `/filament-impersonate/leave`. Closed. |
| G-05 | Are the Fortify/Jetstream routes (register, forgot/reset password, `/user/profile`, `/user/api-tokens`, 2FA) registered and working in V1? | not-investigated / needs-runtime | Vendor route registration not read; can't run V1 | r08: all registered (F-15). Whether the Jetstream pages render and which RoleResource serves `shield/roles` still needs runtime; user can run `route:list` and open `/user/profile` on V1 |
| G-12 | Does production send e-mail (password reset)? | needs-runtime | Committed mail driver is `log`; real `.env` not read | open: user to check production `.env` |
| G-06 | How do real users get accounts today: admin-created, self-registered, or imported? | needs-business | Code allows admin creation and maybe registration | open: grilling |
| G-07 | V1 `wordpress/` install has its own `wp_users` | not-investigated | Separate WordPress system; r04 found no user fields in Woo/WP payloads | dismissed: not part of the V1 app; WooCommerce module's concern |
| G-08 | Security incident (Oct 2026): account DB may have been read; to-dos include resetting all panel passwords, checking for foreign accounts, clearing 2FA secrets | needs-business | Affects how user data and credentials migrate to V2 | open: grilling |
| G-09 | Is `GET /api/user` used by anything (WordPress plugin, scripts)? | needs-business | No consumer in V1 code | open: grilling |
| G-10 | Who owns the general panel-notification channel (bell) in V2: Users, or a cross-cutting notifications capability? | needs-business | Used by Offers, Otomoto, WooCommerce and Users alike | open: grilling |
| G-02 | What reads `company_account_lock_{id}`, and what does the WooCommerce resync change after an account-type change? Also what reads the cache flags `pending_offer_status_notice_{id}` / `draft_offer_status_notice_{id}`? | not-investigated | Outside r03's scope | r06: lock is read only by the profile toggle. Resync changes only the Woo regular price. The two notice flags are per-offer, 1-minute "offer added / draft added" banners on the offer view, owned by Offers. Closed. |
| G-11 | Does the WordPress shop hide private owners' products? | needs-business | WordPress side is outside the V1 repo | open: grilling (Q-19) |

## I. Potential improvements

<!-- Proposals only; not requirements until a D- decision. -->

- **IMP-01** Account creation by admin only. Today: open self-registration grants panel access (W-05, BR-27) → disable public registration, or require admin approval / invitation. Value: security (see also the V1 incident note). Decision: open (Q-10).
- **IMP-02** Invitation / set-your-own-password flow. Today: the admin types or generates a password and hands it over (F-12) → the new user receives an e-mail link to set their password. Value: no shared passwords. Decision: open.
- **IMP-03** Password reset reachable from the login page. Today: reset exists only at a hidden URL (F-15) → offer "Nie pamiętasz hasła?" on the panel login. Decision: open (depends on G-12).
- **IMP-04** Fix the Admin role. Today: Admin-only accounts can't enter the panel, and admin behaviour is decided by role name (Q-04, BR-08, BR-22) → define the Admin tier's capabilities explicitly and let it use the panel. Decision: open.
- **IMP-05** Protect built-in roles and privileged accounts. Today: built-in roles are deletable and renamable, anyone with user-management rights can mint Super Admins or delete them (Q-05, BR-11, BR-26) → built-in roles fixed; only Super Admins can grant, revoke or delete Super Admin. Decision: open.
- **IMP-06** Replace the hard-coded operator e-mail with a role or permission (Q-06, BR-10). Decision: open.
- **IMP-07** Audit impersonation, and restrict it (no impersonating Super Admins) (Q-07). Decision: open.
- **IMP-08** Archive (deactivate) users instead of hard delete, or block deletion while the user owns offers (Q-08, Q-12, BR-17). Decision: open.
- **IMP-09** Account-type change effects only after a successful save (one unit), and reprice everything published, WooCommerce and Otomoto (Q-03, Q-20). Decision: open.
- **IMP-10** One clear definition of "company account" (Q-11), and keep acceptance date and declaration version for the seller declaration (BR-24). Decision: open.
- **IMP-11** Profile e-mail validated as a unique, well-formed address (Q-13). Decision: open.
- **IMP-12** Inbox texts plain text (no raw HTML) and the bell link opening the specific entry (Q-01, Q-02). Decision: open.
- **IMP-13** Two-factor authentication: either enforce it at panel login or drop it (Q-16). Decision: open.
- **IMP-14** Admin user form: show and allow setting account type, show the Otomoto shipping text on the detail page (F-11, F-12). Decision: open.

## J. Additional functionality to implement

- **ADD-01 Forced password change.** An account can be flagged so that its holder must set a new password before using the panel. Raised by the user in round 1 (G-08: after the Oct 2026 incident every migrated account would carry the flag). Dependencies: F-07, F-08. Status: candidate for Phase 7.
- **ADD-03 Optional two-factor authentication.** A User may enable 2FA in their profile; when enabled, panel sign-in asks for the code. Not required for anyone. Raised in round 4 (D-35). Dependencies: F-07, F-08. Status: candidate for Phase 7.
- **ADD-04 Erasure on request.** A Super Admin action that wipes a deactivated User's personal data while keeping history and offers (e.g. a GDPR request). Raised in round 5 (D-39). Dependencies: D-39. Status: candidate for Phase 7.
- **ADD-02 Onboarding wizard.** A step-by-step first-login flow (forced password change, profile and account-type data). Raised by the user in round 1 as a possible later addition. Dependencies: ADD-01, F-08, F-09. Status: candidate for Phase 7.

## K. Investigation log

| Report | Mission | Scope / question | Status | Key results | Limits | Follow-ups | Checked |
|---|---|---|---|---|---|---|---|
| r01 | local | Identity and account anchors | returned | F-07..F-19 (most), BR-12..16, BR-19, BR-23..25, Fortify/Jetstream status table | Vendor routes not run; profile-page.blade.php, profile views not read; wrongly says no role is auto-assigned (Q-09) | r07, r08 | spot: BR-12, BR-25 |
| r02 | local | Access control anchors (roles, permissions, Shield, impersonation) | returned | F-03..06, BR-05..11, Q-04..08; role→permission matrix | Other policies read by name only; vendor impersonate not read; DB state unknown | G-03, G-04; create-then-sync of roles (verify) | spot: F-03/BR-07, BR-08 |
| r03 | local | Journal and notification anchors | returned | F-01, F-02, BR-01..04, W-01, X-01, X-02, Q-01..03. Ownership view: inbox = offer lifecycle, feed = Users | Journal action files read by grep only (Woo side effect in OfferAmountReservedAction unconfirmed) | G-02; full callers via r05 | spot: BR-02, W-01 |
| r04 | cross-repo | Account/auth vocabulary + entry-point census | returned | census ~30 rows; BR-13, BR-17, BR-21; X-03..08; Q-11, Q-12; incident lead | OfferUpdateOffersChunk, FetchWooProducts dispatcher not traced | r06, r08, r09 | spot: BR-13, BR-17 |
| r05 | cross-repo | Roles/permissions + journal/notification vocabulary + census | returned | 33-row gate matrix (BR-22); full inbox writers/readers; notification senders; second RoleResource copy | Vendor not read; registration of duplicate policies/resources unknown | r08, r09 | spot: BR-22 (Offers cluster) |

| r06 | flow | W-01 account-type change end to end (resync, lock readers, modal) | returned | W-01 complete; Q-03 refined; Q-19, Q-20; G-02 closed | WordPress side and runtime (disabled-field submit, worker tries) unknown | G-11 | spot: Woo price (`WooProductDTO.php:58`) |
| r07 | flow | W-03/W-04 admin create/edit/delete, role end-states, deletion effects | returned | W-03, W-04; Q-09 settled; Q-04 confirmed by code | Not run; its 'fields dropped by `$fillable`' claim refuted by unguard (BR-25) | — | spot: role detach (`Select.php:1032-1042`) |
| r08 | verify | 12 claims: Fortify/Jetstream reachability, registration role, 2FA, reset, RoleResource copies, profile/{record}, inbox texts, impersonation | returned | 6 confirmed, 3 refuted, 3 partly; BR-27, W-05; Q-14, Q-15 settled; G-04 closed | Not run; wrongly calls the live seeder unknown (corrected by r02 + manager parse) | G-12; lead `dd($livewire)` at `AdminTab.php:122` (Offers-owned, dismissed) | spot: BR-27 (`config/fortify.php`), F-17 Hash |
| r09 | local | Panel user UI: user menu, widget, bell, language switch, Jetstream leftovers | returned | F-19..22, Q-18, G-10; my-offers banner confirms Q-11; Jetstream views would fail (no `dashboard` route) | bell polling default and avatar provider not read in vendor | — | spot: F-21, Q-18 |

| r10 | cross-repo | Saturation sweep: any user-related entry point or behaviour not in F-01..F-22 | returned | No new entry points. Its 4 'new' items are already BR-10 (×2), BR-19, BR-05. Dead: edit-profile plugin, CustomProfileComponent, Jetstream team code, old seeders | Git history by subject only; starter tests not run | — | cross-checked against BR-05/10/19 |
| r11 | spec-audit | Is functional-spec.md faithful, traceable and implementation-free? | returned | All D-01..D-46 present; counts correct. D-18 misrendered (fixed). FR-23/FR-25 contradicted D-21/D-23 (fixed). WF-04 role contradiction (fixed). §16 missing IMP-04/11/13 (fixed). 22 details beyond any decision → grouped as A-01..A-12 in spec §15 for user confirmation | Borderline wording ("wildcard", kinds as code words) left as is | — | spot-checked D-18 against M |

## L. Completeness assessment

- **Exit criteria:**
  1. ✓ Every census row (r04, r05) maps to F-01..F-22, an X- dependency, or is dismissed. Dismissed: `CustomProfileComponent` (unreachable stub), Jetstream team code and views, `ShieldSeeder_old*` (dead), starter tests in `tests/Feature` (not V1 behaviour), queue/schedule runners (infrastructure), `OfferResourcesSeeder` (dev data).
  2. ✓ Every F- item has entry points, evidence and confidence, plus a workflow or a written n/a reason.
  3. ✓ The "matched, not examined" ledgers hold Offers / Otomoto / WooCommerce-owned files. They are dismissed because only their user-dependency lines matter here, and those lines were read. Vendor files were read where a claim needed it (r07, r08). Jetstream `profile/*` views are dismissed as reachable only via F-15 pages that likely error.
  4. ✓ Leads followed: G-02 → r06; impersonation → r08; registration role → r07/r08; fillable → refuted. Dismissed with reason: `dd($livewire)` at `AdminTab.php:122` (Offers); queue worker ops; WordPress `wp_users` (G-07). Remaining leads are logged as G-11 and Q-20.
  5. ✓ Contradictions: Q-09, Q-14, Q-15 settled. r04 vs r09 (sales stat) settled. r07 fillable and r08 seeder-liveness refuted. Q-11 and Q-19 are open because they need the user.
  6. ✓ Second sources for high-impact items:
     - BR-02, BR-06, BR-07, BR-08, BR-12, BR-13, BR-17, BR-18, BR-22, BR-25, BR-27: manager spot-checks;
     - W-01: r03 + r06 + spot;
     - W-03: r07 + spot;
     - W-04: r04 + r07;
     - BR-09: r02 + r08;
     - BR-10: r02 + r04 + r05 + r10.
  7. ✓ Saturation: r10 found no new F- items or entry points.
  8. ✓ Section I has 14 proposals. Section J says "none identified yet", by design, for the additions round.
- **Mapped:**
  - functionality F-01..F-22 (22);
  - business rules BR-01..BR-27 (26; BR-20 unused);
  - workflows W-01..W-05 (5);
  - dependencies X-01..X-08 (8);
  - questions Q-01..Q-20 (20);
  - gaps G-01..G-12 (12);
  - improvements IMP-01..IMP-14 (14).
- **Verified:** see criterion 6. F-03, F-06, F-15 and F-17 were verified by an independent `verify` mission (r08).
- **Still uncertain:**
  - `decide`: Q-04, Q-06, Q-07, Q-10, Q-11, Q-12, Q-16, Q-19, Q-20.
  - `defect?` awaiting confirmation: Q-01, Q-02, Q-03, Q-05, Q-08, Q-13, Q-17, Q-18.
  - Open gaps: G-01, G-03, G-05 (runtime part), G-06, G-08, G-09, G-10, G-11, G-12.
- **Weak coverage:**
  - Runtime-only facts:
    - which RoleResource serves `shield/roles`;
    - whether Jetstream `/user/profile` renders;
    - production mailer;
    - the live roles and permissions in the V1 DB, which may differ from the seeder.
  - The WordPress shop's handling of private sellers' products is outside the repo.
  - All findings come from static reading; V1 was never run.
- **Ready because:** all eight criteria hold. What remains is either a business decision for grilling, or a runtime fact the user can check by hand in V1. No further static mission would settle any of them.

## M. Decision log

### Round 1: purpose and boundaries (2026-10-31)

- **D-01** `preserve` (G-06). One population of **Users**: AllGlass staff and external sellers alike, differing only by role. Admins also list their own glass, so account type and seller data apply to every User who lists offers. How accounts are created (admin vs self-registration) stays open under Q-10.
- **D-02** `correct` (F-03, F-04, F-05, BR-05..BR-08, BR-22; Q-05, Q-06 reframed). Users is the core module that owns roles and permissions. Filament Shield and its generated permissions are dropped entirely. Users provides role management and a **permission registry**: every module declares its own permissions, and Users lets them be granted to roles. Every check goes through Users (ADR-0004, own Authorizer and Actor). The V1 mechanism is evidence of intent only; the role model itself is settled in round 2.
- **D-03** `correct` (G-01, F-01, X-01; keeps Q-01, Q-02, BR-01..BR-03 in Users). The **Journal** belongs to Users as a general per-user record of things that happened to the User's affairs. Any module may write entries through a convention defined by Users. Each entry carries a kind; today's offer messages map to kind "offer". The offer-lifecycle triggers stay owned by Offers.
- **D-04** `exclude` from Users (G-10, F-20). The panel notification bell is a shared panel channel, not a Users feature. A shared service makes sending easy, with helpers such as "to all admins". Users only sends its own notifications through it. Who counts as "admin" for that helper is answered by Users (round 2).
- **D-05** `exclude` from Users (F-21, F-22, offer stats and the "Powiązane oferty" tab inside F-11). Offer statistics and the user's offers tab belong to Offers (X-03); Users only offers a place on the user detail page. The language switch is a panel setting. The rest of F-11 is decided with F-10..F-13.
- **D-06** `preserve` (F-18). The Otomoto shipping text is information about the User, owned by Users and read by Otomoto (X-04). Who may edit it is decided with the profile round.
- **D-07** `exclude` (G-08, V1 clean-up). The spec covers V2 behaviour only; cleaning up V1 after the Oct 2026 incident is an operational task. The user's answer raised ADD-01 (forced password change) and ADD-02 (onboarding wizard), both for Phase 7. The account-migration policy is settled in the sign-in and security round.

Source pointers: G-06 → D-01; F-03, F-04, F-05 → D-02; G-01, F-01 → D-03; G-10, F-20 → D-04; F-21, F-22 → D-05; F-18 → D-06; G-08 → D-07.

### Round 2: roles, permissions, impersonation, Journal (2026-10-31)

- **D-08** `correct` (F-04, Q-05). Roles and their permissions are defined **in code only**. There is no role editor in the panel, so V1's role management screen is dropped. Admins only pick a role when creating or editing a User. Later changes (for example workshops with owner and employee roles) come from code.
- **D-09** `correct` (BR-07, F-05, BR-26). **Super Admin** can be granted only outside the panel (a console command), never from a form. A Super Admin passes every Permission check, including those added by future modules (wildcard).
- **D-10** `correct` (F-03, F-05, Q-04, Q-09, BR-18). Each User holds exactly one Role. Panel access is granted to any User whose Role is one of the Roles currently registered in the system. Modules register their Roles with Users when the application starts, so the set of Roles is whatever the installed modules declare. The V1 lock-out of Admin-only accounts is a bug and is not carried over.
- **D-11** `preserve`+`correct` (Q-04). Tiers: **User** is a seller who sees only their own affairs. **Admin** is AllGlass staff for daily operations: accepts offers, sees everyone's offers, manages sellers. **Super Admin** additionally manages settings, API keys, the activity log and other admins. Each module defines what Admin may do in its own discovery.
- **D-12** `correct` (BR-08, BR-22). No behaviour is decided by role name. Every behaviour checks a specific Permission. The single exception is the "to all admins" recipient group for panel notifications (D-04): Users holding Admin or Super Admin.
- **D-13** `correct` (BR-11, BR-26, F-05, F-12, F-13). An Admin may create Users and Admins and assign the role User or Admin. Nobody can assign Super Admin from the panel (D-09). Details of editing Super Admin accounts, self-edits and demotion: round 3.
- **D-14** `correct` (Q-06, BR-10). The hard-coded operator e-mail is removed. Settings and the activity log are gated by Permissions held by Super Admin by default.
- **D-15** `pending` (F-06, Q-07, BR-09). Impersonation: the user leans towards "Super Admin always; Admin only with a Super Admin's consent", possibly through per-user extra Permissions. Fallback if that is too costly: a separate Permission, only towards lower Roles, start and end logged to audit, banner with return button. Settled in round 3.
- **D-16** `correct` (F-01, BR-01..BR-03, D-03). Journal entry: kind (area, e.g. "offer"), severity (info, success, warning, rejection), title, content, optional link to its subject. The owner sees only their own entries, marks them read, deletes them, and cannot create or edit them. Following a link to a subject that no longer exists ends in a warning. Entries never expire and are deleted with the User. Every new entry also sends a panel notification.
- **D-17** `correct` (Q-01). Journal entry content is plain text with line breaks preserved, always escaped. Any formatting comes from the entry's fields, never from HTML.
- **D-18** `correct` (Q-02). The panel notification for a new entry opens that specific entry and marks it read. The "go to subject" button is named after the entry's kind and appears only when the entry has a link. Q-02 c and d are dead code and do not carry over.

Source pointers: F-04, Q-05 → D-08; BR-07 → D-09; F-03, Q-04, Q-09, BR-18 → D-10, D-11; BR-08, BR-22 → D-12; BR-11, BR-26 → D-13; Q-06, BR-10 → D-14; F-06, Q-07, BR-09 → D-15; F-01 → D-16; Q-01 → D-17; Q-02 → D-18.

### Round 3: role registration, extra permissions, account type (2026-10-31)

- **D-19** `correct` (D-08, D-10). Each module declares which Roles receive its own Permissions. Users knows which Roles and Permissions exist but holds no central role-to-permission map. When a module goes, its Roles and grants go with it.
- **D-20** `new` (Q-08 role-badge crash, D-10). A User whose Role is no longer registered cannot enter the panel and gets a clear message at sign-in. The user list marks them "unknown role", and an Admin can assign a new Role. Nothing is deleted automatically.
- **D-21** `new` (D-15 resolved). **Extra Permissions**: a Super Admin can grant any User individual Permissions beyond their Role, in the user create/edit form. Only a Super Admin sees and edits them. The user detail shows them separately, and every grant and revocation is audited.
- **D-22** `correct` (F-06, Q-07, BR-09; supersedes D-15). Impersonation is a Permission. Super Admin has it by wildcard. An Admin has it only as an Extra Permission granted by a Super Admin. Only a User with a lower Role than one's own can be impersonated, and **nobody, including another Super Admin, can impersonate a Super Admin**. Start and end go to the audit log, and a banner with a return button shows during impersonation.
- **D-23** `correct` (D-13, BR-11). Admins see Super Admin accounts but cannot edit, delete or impersonate them. An Admin may demote another Admin to User. Nobody changes their own Role or deletes their own account from user management. Super Admins edit other Super Admins' data (contact, password); a Super Admin's Role changes only through the console (D-09).
- **D-24** `correct` (Q-11, BR-12, BR-24). Account type is one choice: Private or Company. A User is Company only when they chose Company **and** accepted the seller declaration; switching to Company requires accepting it. The acceptance date and the declaration's text version are recorded. Switching to Private clears the acceptance.
- **D-25** `correct` (Q-19, G-11). A private seller's offers are published like any other, with VAT added to the price (BR-13, Offers-owned). Offer visibility in the panel and the shop does not depend on account type, and WordPress does nothing with it beyond the price. The private-account notice in the profile ("widoczne tylko dla Ciebie…") is misleading and must be rewritten to say only that VAT is added.
- **D-26** `correct` (Q-20, Q-03, W-01, BR-04, X-02). An account-type change is **announced** by Users as a domain event, after a successful save only. Other modules (Offers, WooCommerce, Otomoto) react to it and do their own repricing and republishing; Users does not trigger it. The lock and the admin notification also follow only a successful save.
- **D-27** `preserve` (BR-14). After an account-type change, the User cannot change it again for 30 minutes and sees when it unlocks. The lock exists so background syncs finish before another change. Only the User themself changes their account type (BR-16).
- **D-28** `correct` (F-02). The separate "Aktywność użytkowników" feed is dropped. An account-type change goes to the audit log and every admin gets a Journal entry of kind "users" with a link to the User (plus the panel notification, D-16). Filters or tabs in the Journal let admins sort such events.

Source pointers: D-15 → D-21, D-22; F-06, Q-07, BR-09 → D-22; BR-11 → D-23; Q-11, BR-12, BR-24 → D-24; Q-19, G-11 → D-25; Q-20, Q-03, BR-04, X-02 → D-26; BR-14 → D-27; F-02 → D-28; Q-08 (badge) → D-20.

### Round 4: profile, admin form, sign-in and self-service (2026-10-31)

- **D-29** `correct` (Q-13, BR-15, BR-16). One validation rule for User data wherever it is edited: e-mail required, well-formed and unique; name required, at most 255 characters; phone required in the profile. V1's unique-name and no-"_" constraints have no known reason and are dropped.
- **D-30** `preserve`+`correct` (F-08, F-12, F-13, F-18, BR-16). The User edits in their profile: name, e-mail, phone, password, company name, NIP, address, pickup address, city, postal code, account type and seller declaration. The User does not edit the Otomoto shipping text. An Admin edits in the user form: Role (D-13), name, e-mail, password, phone, company data and addresses, Otomoto shipping text, and Extra Permissions only when the editor is a Super Admin (D-21). Admins never set account type or the declaration, so a new User starts Private. IMP-14's account-type part is rejected. "For now"; may change later.
- **D-31** `exclude` (F-17, Q-14). The second profile editor (`profile/{record}`) is dropped.
- **D-32** `correct` (F-10, F-11, Q-08 badge). User list: name, e-mail, account type, Role (including "unknown role", D-20), join date, with filters by Role and account type. User detail: contact and company data, account type with declaration acceptance date, Role, Extra Permissions, Otomoto shipping text; offer statistics and offers come from Offers (D-05). The change history tab shows audit entries read-only, with no deletion.
- **D-33** `preserve`+`correct` (F-19). The dashboard account tile stays (name, account type, sign out) and gains an "Edytuj profil" link. The rest of the dashboard belongs to other features.
- **D-34** `correct` (Q-10, F-15, BR-27, W-05, IMP-01, IMP-03). No self-registration: accounts are created only by an Admin. Password reset by e-mail exists, with a "Nie pamiętasz hasła?" link on the panel login and a 60-minute link. E-mail verification, API tokens, the Jetstream profile page and self-deletion are dropped. Production sends no e-mail today; it will later, so password reset depends on mail being configured (see round 5).
- **D-35** `exclude` (Q-16, IMP-13). No two-factor authentication requirement in V2, and V1's 2FA data is not migrated. Optional 2FA that a User enables in their profile becomes ADD-03 for Phase 7.
- **D-36** `exclude` (F-16, G-09). `GET /api/user` is dropped; no known consumer.
- **D-37** `preserve`+`correct` (F-07, BR-23). Sign-in by e-mail and password; sessions last 120 minutes and survive browser close; a password change signs out the User's other sessions. New: sign-in attempts are rate-limited (for example 5 per minute per e-mail and IP).
- **D-38** `correct` (G-08, migration). V1 password hashes are not migrated. Each migrated User receives an e-mail link to set their password (the reset flow, D-34). An Admin can re-send a password-reset link to any User they manage, for lost or deleted e-mails. Contact data, company data, shipping text, Role (User→User, Admin→Admin; Super Admin granted by command) and account type migrate; for Users who had accepted the declaration in V1, the acceptance date is the migration date and the text version is "V1". Depends on production mail (round 5).

Improvement pointers: IMP-01, IMP-03 → D-34; IMP-04 → D-10, D-11; IMP-05 → D-08, D-09, D-23; IMP-06 → D-14; IMP-07 → D-22; IMP-09 → D-26; IMP-10 → D-24; IMP-11 → D-29; IMP-12 → D-17, D-18; IMP-13 → D-35; IMP-14 → D-30 (rejected part), D-32. Still open: IMP-02, IMP-08.

Source pointers: Q-13 → D-29; F-08, F-12, F-13 → D-30; F-17, Q-14 → D-31; F-10, F-11 → D-32; F-19 → D-33; Q-10, F-15, BR-27, W-05 → D-34; Q-16 → D-35; F-16, G-09 → D-36; F-07, BR-23 → D-37; G-08 → D-38.

### Round 5: deletion, mail, dependencies (2026-11-01)

- **D-39** `correct` (F-14, W-04, BR-17, Q-12, Q-08, IMP-08). Users are **deactivated**, never deleted, from the panel. A deactivated User cannot sign in and all their sessions end at once. Their data, Journal, history and offers stay, and other screens show the owner as "konto nieaktywne". An Admin can reactivate them. Same limits as editing (D-23): an Admin cannot deactivate a Super Admin, and nobody deactivates themselves. Users announces "deactivated" and "reactivated" events; what happens to a deactivated seller's published offers is decided in Offers discovery (handed over, no preference stated). V1's dead restore/force-delete actions do not carry over. Erasure on request becomes ADD-04 for Phase 7.
- **D-40** `correct` (D-34, D-38, G-12). Mail will be configured in production; until then, an Admin can set a User's password manually in the user form (less prominent than in V1, but always available). A password set by an Admin should force a change at next sign-in (ADD-01, Phase 7).
- **D-41** `new` (IMP-02, F-12). When creating a User, an Admin either sends an invitation (e-mail link to set the password, same mechanism as D-38) or sets a temporary password. The invitation needs working mail.
- **D-42** `exclude` (Q-17, Q-18, Q-15). Q-17 (sales total at current price) is handed to Offers discovery. Q-18 (the "test" text) does not carry over. Q-15 is moot: Journal texts are supplied by the writing module (D-03).
- **D-43** `preserve`+`correct` (X-01..X-08). Users offers other modules: User data (name, contact, account type, Otomoto shipping text, whether active); events (account-type changed D-26, deactivated/reactivated D-39); adding Journal entries (D-03); Permission checks and the Role/Permission registry (D-02, D-19); the "admins" recipient group (D-12). Everything else belongs to the consuming modules: ownership and visibility of offers, reservations and inquiries (Offers); "Użytkownik dla zaczytanych ofert" (Settings/WooCommerce), whose fallback to user id 1 is dropped and the setting becomes required; recording who acted (Audit, Otomoto).

Source pointers: F-14, Q-12, Q-08, IMP-08 → D-39; G-12 → D-40; IMP-02 → D-41; Q-15, Q-17, Q-18 → D-42; X-01..X-08 → D-43.

### Phase 6 close and Phase 7 selection (2026-11-01)

- **D-44** The user confirmed shared understanding of the existing functionality (Phase 6 done).
- **D-45** `pending` (ADD-02, ADD-03, ADD-04). Onboarding wizard, optional 2FA and erasure on request are deferred.
- ADD-01 (forced password change) selected; grilled as its own subtree in round A1.

### Round A1: ADD-01 forced password change (2026-11-01)

- **D-46** `new` (ADD-01). The user accepted all recommendations without further grilling. The flag "password change required" is set automatically when an Admin sets a temporary password at creation or changes a User's password, and manually by an Admin without changing the password. It is never set when the User sets their own password (profile, reset, invitation). While flagged, every panel page after sign-in leads to "Ustaw nowe hasło", where the User can only set a password or sign out. The new password: at least 8 characters (the same rule everywhere a password is set), confirmed, different from the current one; the old password is not asked for. Afterwards the flag clears, other sessions end, the change is audited as forced, and the User lands on the dashboard. User list and detail show a "wymaga zmiany hasła" marker; an Admin can clear the flag by hand. Edge cases: a password reset by e-mail clears the flag; an impersonator is not sent to the change screen and cannot change the password for the User; Super Admins are subject to the flag; a Deactivated User keeps the flag for reactivation. Out of scope: periodic password expiry, onboarding steps (ADD-02), e-mail notice about the flag.

### Spec audit defaults (2026-11-01)

- **D-47** `correct` (r11 findings). The user confirmed the defaults A-01..A-12 listed after the spec audit, except A-10. In force: reset and invitation links single-use, reset 60 minutes, invitation 7 days; neutral response to reset requests for unknown or deactivated e-mails; setting a password from a link signs the User in; at most 5 failed sign-ins per minute per e-mail and IP; Journal filters by kind and severity, user list filter active/inactive; "Admins" group is active Users only; Deactivated Users still receive Journal entries; deactivation asks for confirmation; module-declared Roles rank below Admin unless declared otherwise, and their module sets who may assign them; V1 Journal entries migrate as kind `offer` in plain text; sessions, tokens, photos and feed rows are not migrated; the declaration gets a new version whenever its wording changes, V1 text = "V1"; spelling in carried-over texts is corrected. **A-10 rejected:** nobody deactivates a Super Admin from the panel, not even another Super Admin.
