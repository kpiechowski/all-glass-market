# r05 — cross-repo: roles/permissions gates, user journal, user notifications (census)

Question: Where across V1 do roles and permissions gate behaviour, and where are user journal entries and user notifications created or read? · V1 commit: 511ce3b

## Summary
- Authorization is mostly NOT Shield permissions. It is a two-way split by `User::isAdmin()` (role `Admin` or `Super Admin`). About 60 gate sites in Offers/Settings/Inquiries code use it. Shield permissions (`can('view_any_x')`) only drive the Shield-generated policies for Car, Submission, User, Role, Activity, taxonomies. OfferPolicy mixes both.
- Role names found: `User` (panel_user), `Admin`, `Super Admin` (config/filament-shield.php, ShieldSeeder). `isSuper()` (Super Admin only) is used only to allow revealing API secrets in ApiSettings. There are also two hardcoded-email gates for the developer account `kpiechowski25@gmail.com` (Settings cluster, Activity log plugin).
- Journal entries (`user_journals`) are written only through `User::createJournalEntry`, called by the abstract `JournalAction`. Its 11 concrete actions fire from offer lifecycle events: create, accept, reject, stock sold, reservation, archive, activate from draft. The only reader is `UserJournalResource` ("Mój dziennik"), scoped to the owner.
- `user_notifications` (admin "Aktywność użytkowników" feed) has exactly one writer, `UserNotificationService`, called from ProfilePage when a user changes company/private account type. It has one reader, the `ListUsersNotifications` Livewire component on the Users list page.
- Real user messaging uses Filament database notifications (`notifications` table, bell in panel) through `notifyNow(...->toDatabase())` and `sendToDatabase`. These are separate from `user_notifications`.

## Findings

### A. Role model
- Tag: confirmed · Impact: high
- What happens: "admin" = has role `Admin` or `Super Admin`. "Super" = `Super Admin` only. `getAdmins()` returns all users with either role; it is the recipient list for every admin notification.
- Evidence: app/Models/User.php:132-147 isAdmin/isSuper/getAdmins; config/filament-shield.php:20-30 super_admin 'Super Admin' (`define_via_gate=false`, `intercept_gate=before`), panel_user 'User'; database/seeders/ShieldSeeder.php:15 role names "User","Super Admin","Admin". database/seeders/DatabaseSeeder.php:21-45 seeds one Super Admin from ADMIN_EMAIL (role ids looked up with Role::find; the "user" role is commented out).

### B. Role assignment
- Tag: confirmed · Impact: high
- What happens: Role is chosen in the user form, section "Rola": a single select (min 1, max 1, required). It is hidden when the edited record is already an admin, so admin roles can't be changed from this form. No other `assignRole`/`syncRoles` call exists outside seeders and Shield RoleResource (syncPermissions).
- Evidence: app/Models/User.php:187-199 `getForm` Section 'Rola', `->hidden(fn($record)=>!empty($record)&&$record->isAdmin())`; app/Filament/Resources/UserResource.php:101 table column shows `roles()->first()->name`; grep assignRole|syncRoles|removeRole found only database/seeders/DatabaseSeeder.php:45.
- Note: tag `ambiguous` whether new-user registration (Fortify/Jetstream) assigns a role. No hit found in app/Actions in my searches (r04's territory).

### C. Panel access
- Tag: inferred · Impact: high
- What happens: `User` uses `HasPanelShield` and implements `FilamentUser`, so panel access is decided by the vendor trait (panel_user/ super_admin role check). There is no V1 `canAccessPanel` override. Not verified in vendor (out of scope: vendor/ excluded).
- Evidence: app/Models/User.php:24,29,36 `HasPanelShield`; grep canAccessPanel in app/ = no hit.

### D. Gate matrix (behaviour guarded → for whom)

Legend: A = admin (Admin or Super Admin), U = non-admin user. Owner = Offers-owned, recorded as gate only.

| # | Guarded behaviour | Who | Owner | Tag | Evidence |
|---|---|---|---|---|---|
| 1 | Offers cluster (Offers nav/entry) accessible | A only | Offers | confirmed | app/Filament/Clusters/Offers/Offers.php:26-28 |
| 2 | Settings cluster accessible | Admin AND email == kpiechowski25@gmail.com (hardcoded) | Settings | confirmed (decide: hardcode) | app/Filament/Clusters/Settings/Settings.php:15-18 |
| 3 | Activity log plugin | email == kpiechowski25@gmail.com only | Audit | confirmed | app/Filament/Utils/PanelConfig.php:85-89 |
| 4 | Reveal (eye) of WordPress/API secrets in ApiSettings | Super Admin only | Settings | confirmed | app/Filament/Clusters/Settings/Pages/ApiSettings.php:138,189,196 |
| 5 | Settings pages GeneralSettings, MessagesSettings, ApiSettings; EditProfile page | Shield page permission (HasPageShield) | Settings/Users | confirmed (permission names resolved by vendor) | those 4 files `use HasPageShield` |
| 6 | Full offer list page (`FullListOffer`) | A | Offers | confirmed | app/Filament/Pages/FullListOffer.php:74-77 |
| 7 | AdminReservedOffersPage ("Zarezerwowane" admin) | A | Offers | confirmed | app/Filament/Pages/AdminReservedOffersPage.php:36-39 |
| 8 | UserReservedOffersPage ("Zarezerwowane") | U only | Offers | confirmed | app/Filament/Pages/UserReservedOffersPage.php:48-51 |
| 9 | UserInquiriesPage ("Zapytania ofertowe", user side) | U only | Inquiries | confirmed | app/Filament/Pages/UserInquiriesPage.php:39-42 |
| 10 | OfferInquiryResource (admin inquiries) | A | Inquiries | confirmed | app/Filament/Resources/OfferInquiryResource.php:30-33 |
| 11 | UsersOtomotoOffers ("Otomoto - ogłoszenia", user side) | U only | Otomoto | confirmed | app/Filament/Pages/UsersOtomotoOffers.php:39-42 |
| 12 | OtomotoOfferResource, CreateOtomotoOffer, OtomotoQueueMonitor | A | Otomoto | confirmed | app/Filament/Resources/OtomotoOfferResource.php:57-60; Pages/CreateOtomotoOffer.php:102; Pages/OtomotoQueueMonitor.php:31-34 |
| 13 | ProfilePage | everyone (`return true`) | Users/r04 | confirmed | app/Filament/Pages/ProfilePage.php:50-53 |
| 14 | Offer record view (ViewOffer page): 403 unless `canBeViewByUser` (admin always; else owner only, not trashed) | A, owner | Offers | confirmed | Pages/ViewOffer.php:60-64; app/Models/Offer.php:369-382 (partial read; full rule not read) |
| 15 | OfferPolicy: viewAny = admin only on route `admin/glass/offers`, otherwise true; view/update/delete = admin or owner; create = permission `create_offer`; deleteAny/forceDelete/restore/replicate/reorder = permissions | A / owner / Shield perms | Offers | confirmed (note: route-string coupling) | app/Policies/OfferPolicy.php:21-122 |
| 16 | Offer create/edit: status forced to Pending when non-admin saves (Draft if "save as draft"); admin keeps chosen status | U vs A | Offers | confirmed | Pages/CreateOffer.php:36-41; Pages/EditOffer.php:55-58 |
| 17 | Admin header block "Ustawienia oferty" with status select, user pickers; create/edit forms: many fields admin-only (status default Active for admin / Pending, SKU, hide of free-text eurocode field for admin, eurocode select options) | A vs U | Offers | confirmed | Components/OfferCreateForm.php:55-60,194,269,307-309,375-405,443; Components/OfferEditForm.php:63-68,209,227,370-399,437; UserOfferCreateForm.php:55-60,130,191-193; Partials/SelectEurocodesPartial.php:32,84,99,119 |
| 18 | Taxonomy create/edit (color, type, producer, equipment): "status" select visible only to admin; non-admin creations default to Pending (user proposes new taxonomy value, admin activates) | A vs U | Offers taxonomies | confirmed | app/Models/OfferModels/OfferColor.php:39-41, OfferType.php:50-59, OfferProducer.php:40-42; app/Models/OfferEquipment.php:36-38 |
| 19 | Taxonomy view: WordPress sync section | A | Offers taxonomies | confirmed | app/Filament/Traits/IsOfferTaxonomyView.php:88,122 |
| 20 | Taxonomy resource access (Color/Type/Producer/Equipment/Car/Submission/Activity) | Shield permissions via policies | Offers/other | confirmed | app/Policies/{OfferColor,OfferType,OfferProducer,OfferEquipment,Car,Submission,Activity}Policy.php, and duplicates under app/Policies/OfferPolicies/ |
| 21 | ViewOffer: management tabs (StockSale, WooCommerce sync, Otomoto sync) | A | Offers | confirmed | Pages/ViewOffer.php:104; StockSaleTab.php:172 (hidden unless admin); WooCommerceSync.php:112 |
| 22 | ViewOffer: sold notice, delete redirect, "(Admin) Kod od użytkownika:", "Informacja od użytkownika", account-status notice (company accounts) | A (some U) | Offers | confirmed | Pages/ViewOffer.php:83,124,131,193-213,269 |
| 23 | Revisions action on EditOffer; Changes relation manager (not on EditOffer) | A | Offers/ChangesLog | confirmed | Pages/EditOffer.php:35; app/Filament/Resources/Shared/ChangesRelationManager.php:37-40 |
| 24 | MyOffers bulk actions: admin set (Bulk remove from WP, Otomoto advert create, Woo sync, accept) vs user set (change price) | A vs U | Offers | confirmed | app/Filament/Pages/MyOffers.php:92-101 |
| 25 | Reserved offers table: user filter / "Tylko oferty" indicator / WP-sync filter admin only; "Zarządzanie rezerwacjami" section owner and non-admin only; eurocode priority slide-over admin | A / owner U | Offers | confirmed | AdminReservedOffersTable.php:261,268; ReservedOfferFilters.php:51; UserReservationSection.php:28; ReorderEurocodesViewAction.php:23 |
| 26 | Dashboard LatestOffers "Zobacz pełny widok" link | A | Offers | confirmed | app/Filament/Resources/OfferResource/Widgets/LatestOffers.php:94 |
| 27 | Offer-created-by-user and edited-by-user: submission + admin alert skipped when the author is admin; journal "draft created" skipped for admin authors; activation-from-draft sets Active for admin author, Pending (+ admin alert) otherwise | author role | Offers/Submissions | confirmed | Actions/OfferCreatedByUserAction.php:51; OfferEditedByUserAction.php:39; Journal/DraftOfferCreatedAction.php:28; Journal/OfferCreatedAction.php:28; Journal/ActivateFromDraftAction.php:31-34 |
| 28 | User management (UserResource): per Shield permission prefixes view/view_any/create/update/restore/replicate/reorder/delete/(force)... via UserPolicy | Shield perms | Users (anchor) | confirmed | app/Filament/Resources/UserResource.php:38-52; UserPolicy |
| 29 | Impersonate action on user table | decided by vendor plugin (`canImpersonate`) , no V1 override found | Users | unknown: gating not shown in V1 | UserResource.php:120; grep canImpersonate = none. Needs vendor/runtime check |
| 30 | Role management (Shield RoleResource) | permission `*_shield::role` | Users (anchor) | confirmed | app/Policies/RolePolicy.php; note force-delete..reorder methods contain unrendered template `{{ ForceDelete }}` strings (defect?) |
| 31 | "Mój dziennik" row delete: owner only else 403; list scoped to owner | owner | Users | confirmed | UserJournalResource.php:57-62 (query), 172 |
| 32 | UserJournalPolicy fully commented out → no policy gate | everyone | Users | confirmed | app/Policies/UserJournalPolicy.php (all methods commented) |
| 33 | Email-verification, API tokens (Jetstream teams menu `@can('create', Team)`) | Jetstream | r04 | confirmed, ignore | resources/views/navigation-menu.blade.php:50,197 (legacy Jetstream view; tests/Feature/*ApiToken* ) |

- Defect? OfferPolicy::viewAny decides by `request()->route()->uri === 'admin/glass/offers'` (OfferPolicy.php:21-26): behaviour depends on URL, breaks off-request (queue/console). Tag: defect?.
- Duplicate policy files: app/Policies/OfferColorPolicy.php vs app/Policies/OfferPolicies/OfferColorPolicy.php (same for Type/Producer; Equipment only at root). Tag: observed duplication; registration unknown (auto-discovery assumed, not verified).
- No `Gate::` definitions, no `Gate::before`, no `@can` in app Filament code, no `authorize(` outside PanelConfig plugin closure. Gate/Shield super admin bypass is configured in config only (`intercept_gate before`).

### E. User journal (writers)
- Tag: confirmed · Impact: normal
- What happens: Every entry is created by `User::createJournalEntry(offer_id, title, content, type)` – it returns false without an offer id or with a disallowed type. The entry goes to the OFFER OWNER (`User::find($record->user_id)`), then the owner also gets a Filament database notification (title = entry title, icon newspaper, button "zobacz" linking to the journal list filtered by `resource_id`).
- Entry triggers (class → caller → type):
  - OfferCreatedAction (Success) ← OfferCreatedByUserAction (CreateOffer.php:94); skipped when author is admin per class code.
  - DraftOfferCreatedAction (Success) ← OfferCreatedByUserAction:45 (draft); skipped for admin authors.
  - ActivateFromDraftAction (Success) ← MyOffers.php:213; also notifies all admins.
  - OfferAcceptedAction (Success) ← AdminTab.php:130,163 (single accept), AcceptOffersBulkAction.php:89 (bulk), SubmissionResource ViewSubmission.php:483.
  - OfferRejectedAction (Rejection) ← AdminTab.php:121,149 (with reason), ViewSubmission.php:486.
  - OfferArchivedAction (Info) ← StockSaleTab.php:199.
  - OfferFullySoldAction (Success) / OfferAmountSoldAction (Success) ← Actions/Stock/OfferStockChangeAction.php:42,44.
  - OfferFullyReservedAction (Info) / OfferAmountReservedAction (Info) ← Actions/Stock/OfferReservationChangeAction.php:39,42.
  - OfferReservedAction – imported in AdminTab.php:18 but no `new OfferReservedAction` call found (unreachable? tag inferred; class file not read).
- Evidence: app/Models/User.php:156-175; app/Filament/Actions/Abstracts/JournalAction.php:20-53; callers as listed.
- Message texts are editable in Settings → "Powiadomienia dziennika" (description 'Treść powiadomień dla zapisów w zakładce "Mój dziennik"'), stored through Setting (`{type}_desc`). Evidence: MessagesSettings.php:93-94,120-125.
- FK: user_journals.user_id cascades on user delete; offer_id nullable with no FK. Evidence: migration 2024_12_01_180306_create_user_journals_table.php:19-21.
- UserJournal imported but not used in OfferCreatedByUserAction.php:9 and OfferEditedByUserAction.php:9 (dead imports).

### F. User journal (readers)
- Tag: confirmed. "Mój dziennik" (nav sort 6): table of own entries only; unread badge counts `is_read=false` for current user; marking read by clicking the eye icon ("Oznacz jako przeczytaną"/"Wiadomośc odczytana") or opening the entry modal; row action "Usuń" (confirmation, owner only) and bulk delete. No create/edit pages (commented out). Evidence: UserJournalResource.php:34-62,84-100,159-172,176-194.
- Admins see only their own journal; no admin-wide view of journals found.

### G. user_notifications (admin activity feed)
- Tag: confirmed.
- Writer: `UserNotificationService::createUserNotification($user,$title,$sendMessage=true)` inserts a row (title, user_id) and, if `$sendMessage`, sends a Filament database notification (`info`) to every admin. Only call: ProfilePage::update when the user's `company_account` flag changes; title "Uzytkownik {name} zmienił rodzaj konta na: Firmowe|Prywatne"; also sets a 30-minute cache lock `company_account_lock_{id}` and runs `OfferUpdateService::handleUsersPublishedOffer`. Evidence: app/Filament/Pages/ProfilePage.php:114-124; app/Services/User/UserNotificationService.php:12-33; singleton registered AppServiceProvider.php:22.
- Reader: `ListUsersNotifications` Livewire (heading "Aktywność użytkowników", empty state "Brak powiadomień", delete/bulk delete, shows "Konto usunięte" when user_id null), embedded in Users list page view resources/views/filament/users/list-users.blade.php:4 via ListUsers.php:13. Access inherits UserResource list permission (view_any_user); no own gate. All rows visible to whoever reaches that page.
- Schema: user_notifications.user_id nullable, no FK constraint/cascade (rows survive user deletion); `message` column never written. Evidence: migration 2025_03_29_174632:16-19; User::userNotifications relation (User.php:105).

### H. Filament/Laravel database notifications to users (senders)
- Tag: confirmed. Panel has `->databaseNotifications()` (AdminPanelProvider.php:99); User uses Notifiable.
- To all admins (`User::getAdmins()->each(notifyNow)`): OfferCreatedByUserAction:202 (new offer awaiting acceptance, link to submission), OfferEditedByUserAction:157, ActivateFromDraftAction:76, AcceptInquiryAction:61, RejectInquiryAction:49 ("Uzytkownik odrzucił zapytanie ofertowe"), UserNotificationService (above).
- To the offer owner: JournalAction.sendNotification (above); CreateInquiryAction.php:38 ("Nowe zapytanie ofertowe!" – body "Otrzymałeś nowe zapytanie do jednej ze swojej oferty") – triggered from StockSaleTab.php:228 (admin side, `hidden` for non-admin).
- To acting user/run user via `sendToDatabase`: Otomoto BulkActionTracker (45,85,170,182,192), BulkSynchProductToWooAction:55, BulkCreateAdvertsAction:124 (notifyNow to Auth user). OfferNotificationHandler.php:39 sendToDatabase with placeholder text "Saved successfully" – no caller found outside its folder; tag inferred unreachable.
- Many other files use `Notification::make()->send()` as flash toasts (not persisted); classified as UI feedback, not user notifications: Otomoto actions, ListOffers, CreateOffer, SettingsSchemaBuilder, Woo/Wordpress API services, ChangesLogService, Notifier service wrapper, etc.
- No `->notify(` call with a Laravel Notification class, no `Notification::send`, no `->broadcast(`. Password reset uses Illuminate ResetPassword (tests/Feature/PasswordResetTest.php) – r04.

## Entry points

| Kind | Path | Does | Reachable |
|---|---|---|---|
| Filament resource | app/Filament/Resources/UserJournalResource.php (+Pages/ListUserJournals) | "Mój dziennik" list, mark read, delete | yes, nav |
| Filament action | UserJournalResource.php:84-100 icon `set_as_read` | mark one entry read | yes |
| Filament action | UserJournalResource.php:150-165 view modal | open entry, marks read | yes |
| Filament action | UserJournalResource.php:167-172 `delete` | delete own entry | yes |
| Bulk action | UserJournalResource.php:176 DeleteBulkAction | delete selected entries | yes (no owner check beyond query scope) |
| Nav badge | UserJournalResource::getNavigationBadge | unread count | yes |
| Livewire | app/Livewire/Users/ListUsersNotifications.php | admin activity feed, delete / bulk delete | yes (Users list page) |
| Page | UserResource ListUsers (view filament.users.list-users) | hosts the feed | yes |
| Service | app/Services/User/UserNotificationService.php::createUserNotification | write feed row + notify admins | yes, from ProfilePage::update |
| Page action | app/Filament/Pages/ProfilePage.php::update | changes account type → feed row | yes |
| Model method | User::createJournalEntry | only journal writer | via JournalAction |
| Abstract + 11 classes | app/Filament/Actions/Abstracts/JournalAction.php, Actions/Journal/* | journal + notify owner | see section E; OfferReservedAction no caller |
| Filament actions | OfferCreatedByUserAction, OfferEditedByUserAction | submission + admin notification | yes (CreateOffer.php:94, EditOffer.php:118) |
| Filament actions | Inquiry Create/Accept/Reject actions | owner or admins notified | yes (StockSaleTab:228, UserInquiriesTable:149,158) |
| Settings page | MessagesSettings (Powiadomienia dziennika form) | edits journal texts | yes, Settings cluster gate #2/#5 |
| Panel config | AdminPanelProvider ->databaseNotifications() | notification bell | yes |
| Role admin | Shield RoleResource (app/Filament/Resources/Shield and app/Filament/Clusters/Settings/Resources/Shield – two copies, `syncPermissions` in Create/EditRole) | create/edit roles & permissions | resource copies: which one is registered unknown (discoverResources vs cluster discovery both exist; tag ambiguous) |
| Form | User::getForm "Rola" section | assign single role | yes via UserResource |
| Seeders | DatabaseSeeder, ShieldSeeder (+ ShieldSeeder_old, _old2) | create roles/perms, super admin | manual run only; _old files unreachable |
| Policies | app/Policies/*.php | Shield permission checks | auto-discovered (not verified) |
| Gate sites | see matrix D | | yes |
| Scheduled / commands / jobs / API / webhooks | none touch roles, journal or user_notifications | | n/a (routes/console.php, app/Console/Commands, routes/api.php grep: no hit) |
| Impersonate | UserResource.php:120 (plugin) | log in as another user | yes, gating unknown |

## Contradictions and defects
- defect?: RolePolicy.php:66-106 contains literal `'{{ ForceDelete }}'` etc. (unrendered stub) – those abilities check a nonexistent permission.
- defect?: OfferPolicy::viewAny depends on request URI (see D).
- defect?: Settings cluster and Activity log gated on one hardcoded personal email (dev backdoor in production code).
- defect?: User::createJournalEntry is called with 5 args but accepts 4; harmless.
- defect?: JournalAction notifies via `notifyNow` even for admin-authored offers where the action wasn't skipped (e.g. Accepted) – fine, no defect.
- Inconsistency: nav/page gating uses isAdmin; Shield page permissions on settings pages and EditProfile are layered under, so a Super Admin only passes both if permissions exist (config super_admin interception before gate handles Shield side).
- Dead data: user_notifications.message never written; unused imports in the two ByUser actions.

## Leads outside scope
- OfferPolicy / Offer visibility rules (Offers): `Offer::canBeViewByUser` full body.
- Submissions flow created by OfferCreatedByUserAction/EditedByUserAction (Submissions module).
- Inquiries module: Create/Accept/Reject inquiry notifications.
- Otomoto BulkActionTracker per-user DB notifications.
- Settings keys `{type}_desc` for journal texts (Settings module).
- STS Impersonate plugin and Shield vendor behaviour (panel access, impersonation rules).
- Duplicated Shield RoleResource under Clusters/Settings (anchor mission).

## Could not establish
- Who may impersonate whom (vendor plugin default; V1 has no override). Runtime or vendor read would settle it.
- Whether new registrations get a role (and which). Searched assignRole/syncRoles/Fortify actions; only the seeder assigns. Registration check belongs to r04; a runtime check would settle it.
- Whether the duplicate policies/resources (OfferPolicies/*, Clusters/Settings/Resources/Shield) are registered; auto-discovery assumed.
- The exact permission list per role in production DB (seeder JSON only partially read: role names confirmed, permission lists not parsed).
- Whether OfferReservedAction and OfferNotificationHandler are ever executed (no caller found by grep; dynamic invocation unlikely).

## Coverage ledger
- Searched (app, routes, config, database, resources, tests, bootstrap): isAdmin, isSuper, getAdmins, hasRole, hasAnyRole, hasAllRoles, assignRole, syncRoles, removeRole, syncPermissions, givePermissionTo, hasPermissionTo, ->can(, cannot(, @can, authorize(, authorizeAccess, Gate::, canAccessPanel, canAccess, canViewAny/canCreate/canEdit/canDelete/canView/canDeleteAny/canForceDelete/canRestore/canReorder/canReplicate, shouldRegisterNavigation, canImpersonate, impersonat, super_admin, panel_user, Super Admin, 'Admin', Shield/FilamentShield/HasPageShield/HasPanelShield, abort(40, abort_if/unless, middleware(, roles, Rola/Role; UserJournal, user_journals, createJournalEntry, JournalType, dziennik, UserNotification, user_notifications, UserNotificationService, ListUsersNotifications, list-users-notifications, userNotifications, is_read, journalEntryModal, ListUserJournals, ->notify(, notifyNow, Notification::send, sendToDatabase, ->broadcast(, databaseNotifications, DatabaseNotification, unreadNotifications, ->notifications, Notifications\ (namespace), Schedule/ console routes; Journal*Action and Inquiry*Action callers.
- Examined: User.php (95-215, 20-60), UserNotificationService, JournalAction, ListUsersNotifications, UserJournalResource (30-194), UserResource (1-60), ProfilePage (40-130), MessagesSettings (85-140), OfferPolicy (14-34), PanelConfig, AdminPanelProvider (90-105), config/filament-shield.php, DatabaseSeeder, both migrations, and the ~35 gate sites with surrounding lines cited above.
- Matched, not examined in full: Policies for Car/Submission/Activity/taxonomies (generated Shield boilerplate, one `can('<verb>_<name>')` per method); ShieldSeeder_old/_old2 (superseded seeders); Shield RoleResource pages (anchor); OfferCreateForm/OfferEditForm/UserOfferCreateForm (admin-only field list cited by line, not each field read); Otomoto and Woo/WP service files (`Notification::make()->send()` toasts); navigation-menu.blade.php (Jetstream team menu); tests/Feature/*ApiToken* (Sanctum token abilities, r04); public/js/filament echo.js (vendor bundle, false positive).
