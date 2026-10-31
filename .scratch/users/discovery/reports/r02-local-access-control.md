# r02 — local: Roles, permissions, impersonation and panel access

Question: What roles and permissions exist in V1, how are they assigned and managed, what does each role allow or deny, and what are the rules of impersonation and panel access? · V1 commit: 511ce3b

## Summary
- Three roles exist (guard `web`): `User`, `Super Admin`, `Admin` (ids 1, 2, 3 by seeding order). Spatie permission plus Filament Shield. Permissions follow Shield naming `<prefix>_<resource>`.
- Panel access: only `Super Admin` or `User` role (HasPanelShield::canAccessPanel). A user holding only `Admin` is refused at the panel.
- Super Admin has no Gate bypass (`define_via_gate=false`). It is just a role holding many permissions, so Super Admin power depends on the seeded or DB permission rows.
- The live seeded `Admin` role has only the 12 `*_user` permissions. Almost all "admin" behaviour elsewhere is hardcoded via `isAdmin()` (Admin or Super Admin by name), not permissions.
- Impersonation: a table action on the user list. No V1 code restricts who may impersonate or be impersonated beyond "not self, not already impersonating"; visibility depends on reaching the user list (`view_any_user`).
- Roles are managed in the Shield RoleResource (Super Admin only as seeded). A user holds exactly one role in the user form.
- Many policy permission names are defined but not seeded, and the RolePolicy contains unfilled stub placeholders.

## Findings

### Roles that exist and how they are seeded
- Tag: confirmed · Impact: high
- What happens: `ShieldSeeder` creates `User`, `Super Admin`, `Admin` (guard `web`) with `firstOrCreate`, then `syncPermissions` (replaces each role's permissions with the listed set). Direct permissions `view_role, view_any_role, create_role, update_role, delete_role, delete_any_role` are also created (assigned to no role).
- Evidence: database/seeders/ShieldSeeder.php:15 `$rolesWithPermissions` JSON; :16 `$directPermissions`; :34-52 `makeRolesWithPermissions`; roles unique on (name, guard_name): database/migrations/2024_10_06_133235_create_permission_tables.php:50.

### Seeder liveness: ShieldSeeder is live, _old and _old2 are dead
- Tag: confirmed · Impact: normal
- What happens: only DatabaseSeeder calls `ShieldSeeder`. `ShieldSeeder_old.php` and `ShieldSeeder_old2.php` both declare `class ShieldSeeder` (same name, so they cannot be loaded alongside it). Nothing references them (grep of php/json/md/yml/sh finds no caller). `_old` has Super Admin with `*_role` and `*_user` perms, Admin with `*_user`, User with only `view_user`. `_old2` differs from live only in formatting/JSON (diff shows just the JSON lines and `!`-spacing).
- Evidence: database/seeders/DatabaseSeeder.php:19 `$this->call(ShieldSeeder::class)`; ShieldSeeder_old.php:9 and _old2.php:9 class decl; `git log` shows ShieldSeeder.php was last changed in 504c6c5, and _old files from earlier commits.

### DatabaseSeeder creates one Super Admin only
- Tag: confirmed · Impact: high
- What happens: after ShieldSeeder, creates a user "Super Admin" with email/password from env `ADMIN_EMAIL`/`ADMIN_PASSWORD` (defaults empty string) and gives it `Role::find(2)`. `Role::find(1)` is stored as `'user'` but never assigned; the Admin and plain User demo accounts are commented out. This depends on id order (User=1, Super Admin=2 from the JSON order), so it breaks if the seeder order changes (old seeders had Super Admin first). It also seeds Setting rows for WooCommerce/WordPress credentials from env (outside scope).
- Evidence: DatabaseSeeder.php:19-40, :43-49 (Setting values).

### Role -> permission matrix (live seeder)
- Tag: confirmed · Impact: high
- What happens: see table. Resources use Shield prefixes `view, view_any, create, update, restore, restore_any, replicate, reorder, delete, delete_any, force_delete, force_delete_any` ("full12").

| Permission group | User | Admin | Super Admin |
|---|---|---|---|
| `*_user` (full12) | no | yes | yes |
| `*_car` (full12) | no | no | yes |
| `*_offer` (full12) | `view, view_any, create, update, delete` only | no | yes (full12) |
| `*_offer::taxonomies::offer::{color,equipment,producer,type}` (full12 each) | no | no | yes |
| `*_activitylog`: `view_activitylog` | yes | no | yes |
| `*_activitylog`: `view_any, create, update, restore, restore_any, replicate, reorder, delete, delete_any, force_delete, force_delete_any` | no | no | yes |
| `*_submission` (full12) | no | no | yes |
| `*_user::journal`: `view_user::journal, delete_user::journal` | yes | no | yes |
| `*_user::journal`: other 10 prefixes | no | no | yes |
| `*_shield::role`: `view, view_any, create, update, delete, delete_any` | no | no | yes |
| pages: `page_ApiSettings, page_GeneralSettings, page_MessagesSettings, page_EditProfilePage, page_Offers` | no | no | yes |
| widgets: `widget_OfferStatsOverview, widget_LatestOffers` | no | no | yes |
| `*_role` (`view_role` ... `delete_any_role`) | direct perms, no role holds them | | |

- Evidence: ShieldSeeder.php:15-16. Admin = lines for `"name":"Admin"` list of 12 `*_user` perms only.
- Note: User role (seeded) has no `*_user` permission, so a plain User cannot see the Users resource.

### Panel access rule
- Tag: confirmed · Impact: high
- What happens: `canAccessPanel` returns true only if the user has role `Super Admin` or role `User` (Shield `panel_user.name`). A user with only `Admin` role gets false and cannot open the `admin` panel. `Admin` thus only works if the account also holds `User` (or Super Admin), but the user form allows exactly one role (`minItems(1)`, `maxItems(1)`) and the role section is hidden when the edited record is already an admin. Whether Admin is usable in production is therefore unclear.
- Evidence: vendor/bezhansalleh/filament-shield/src/Traits/HasPanelShield.php `canAccessPanel`; app/Models/User.php:`use HasPanelShield`, `implements FilamentUser`; config/filament-shield.php `panel_user` enabled, name `User`; app/Models/User.php `getForm()` roles Select (`->multiple()->minItems(1)->maxItems(1)`, `->hidden(fn($record) => !empty($record) && $record->isAdmin())`).
- Tag for "Admin cannot enter panel in practice": inferred (the trait is clear; no runtime check of real accounts). decide: confirm whether Admin should reach the panel.

### New accounts get the `User` role automatically (non-console only)
- Tag: confirmed · Impact: high
- What happens: HasPanelShield boot hook: when not running in console, ensures role `User` exists (`createPanelUserRole`), assigns it on every user `created`, and removes it on `deleting`. Console-created users (seeders, tinker) do not get it. Registration is enabled in Fortify (details are r01's scope).
- Evidence: HasPanelShield.php `bootHasPanelShield`; config/fortify.php:147 `Features::registration()`; config/filament-shield.php `panel_user.enabled=true`.

### Role assignment by admins
- Tag: confirmed · Impact: high
- What happens: In the user create/edit form (section "Rola") an admin picks one role from all roles (relationship `roles`, preload, searchable, required). On creation the hook above has already assigned `User`; the Select relationship then syncs the chosen role. The section is hidden when editing a record that is Admin or Super Admin, so admin accounts' roles cannot be changed from this form. The user table shows the first role as a badge: Super Admin red ("danger"), Admin green ("success"), User yellow ("warning"). Any other role name or a user with no role makes the `match` throw (no default arm).
- Evidence: app/Models/User.php getForm() first Section('Rola'); app/Filament/Resources/UserResource.php:99-105 role column `match`.
- Tag for the throw on unknown role / no role: inferred (PHP `match` without default throws `UnhandledMatchError`; also a null state fails the `string $state` type), `defect?`.

### Who can create/edit/delete users (UserPolicy)
- Tag: confirmed · Impact: high
- What happens: UserPolicy maps each ability to the permission of the same name: viewAny `view_any_user`, view `view_user`, create `create_user`, update `update_user`, delete `delete_user`, deleteAny `delete_any_user`, forceDelete `force_delete_user`, forceDeleteAny `force_delete_any_user`, restore `restore_user`, restoreAny `restore_any_user`, replicate `replicate_user`, reorder `reorder_user`. Seeded Admin and Super Admin hold all of them. Note policy methods `view/update/delete...(User $user)` take no target model, so there is no per-record rule (an admin can edit or delete anyone, including Super Admin).
- Evidence: app/Policies/UserPolicy.php:22,33,44,55,66,77,88,99,110,121,132,143.
- The EditUser page offers header actions Delete, ForceDelete, Restore. UserResource's table has Delete/BulkDelete commented out, and `getEloquentQuery` removes SoftDeletingScope (but User has no SoftDeletes trait in User.php, so soft-delete actions may do nothing or fail). Tag: inferred, `defect?`.
- Evidence: app/Filament/Resources/UserResource/Pages/EditUser.php (header actions); app/Models/User.php (no SoftDeletes); UserResource.php getEloquentQuery.

### Role management (Shield RoleResource)
- Tag: confirmed · Impact: high
- What happens: The role resource (slug `shield/roles`, inside the Settings cluster) lets a holder of the `*_shield::role` permissions list, view, create, edit and delete roles. Create/edit form: name (unique, required), guard_name (default from auth guard, nullable), a "select all" toggle, and tabbed checkbox lists for resources, pages, widgets (custom permissions tab exists but `custom_permissions=false`). On save, the chosen permissions are `firstOrCreate`d and `syncPermissions` replaces the role's permission set. Table has Edit, Delete and bulk delete; shows name, guard, permission count.
- Resource-level prefixes: `view, view_any, create, update, delete, delete_any`. RolePolicy checks the `*_shield::role` names (`view_any_shield::role` etc.), which are the seeded Super Admin permissions. RolePolicy is registered by Shield (`register_role_policy.enabled=true`).
- The whole Settings cluster is gated separately: `Settings::canAccess` (via `Auth::user()->isAdmin() && email == "kpiechowski25@gmail.com"`) so, in effect, only one hardcoded email can reach the role resource.
- Evidence: app/Filament/Resources/Shield/RoleResource.php:25-34, :37-80, :82-113; Pages/CreateRole.php afterCreate, Pages/EditRole.php afterSave (`syncPermissions`); config/filament-shield.php `shield_resource.cluster`, `slug`; app/Policies/RolePolicy.php:18-58; app/Filament/Clusters/Settings/Settings.php:17; FilamentShieldServiceProvider.php `Gate::policy(...RolePolicy)`.
- Roles can be deleted freely, with no guard on built-in roles (`User`, `Admin`, `Super Admin`). Deletion cascades to `role_has_permissions` and `model_has_roles`. Tag: confirmed (cascadeOnDelete in migration, no guard found in Pages/EditRole.php, RoleResource.php).
- Evidence: migration lines 85-88, 105-113 (`onDelete('cascade')`).

### Super Admin is not a bypass
- Tag: confirmed · Impact: high
- What happens: `super_admin.enabled=true`, `define_via_gate=false`. Shield therefore does not register a `Gate::before`; Super Admin only passes checks for permissions assigned to the role (Shield's `giveSuperAdminPermission` grants new permissions to Super Admin when generated through its commands). No `Gate::before/after` exists in app code. Any permission checked in a policy but not seeded for Super Admin (see below) is denied to Super Admin.
- Evidence: config/filament-shield.php `super_admin`; FilamentShieldServiceProvider.php `packageBooted` (gate only if `isSuperAdminDefinedViaGate`); FilamentShield.php `giveSuperAdminPermission`; `grep Gate:: app bootstrap` -> none; AppServiceProvider holds no permission logic.

### Name-based helpers
- Tag: confirmed · Impact: high
- What happens: `isAdmin()` = has role `Admin` OR `Super Admin`; `isSuper()` = role `Super Admin`; `getAdmins()` = all users with either role (used to notify admins in UserNotificationService:26). Many views/actions elsewhere branch on `isAdmin()` instead of permissions (e.g. offer edit, taxonomy default status, reserved filters, Settings cluster). Role names are strings in code, so renaming a role breaks them.
- Evidence: app/Models/User.php isAdmin/isSuper/getAdmins; app/Services/User/UserNotificationService.php:26; examples one hop out: app/Filament/Clusters/Settings/Settings.php:17, app/Models/OfferModels/OfferType.php:50, app/Filament/Pages/UserInquiriesPage.php:41 (visible when NOT admin).
- `isSuper()` has no callers in app/ (grep).

### Hardcoded privileged email
- Tag: confirmed · Impact: high
- What happens: Settings cluster access requires admin AND email `kpiechowski25@gmail.com`; the Activity log plugin is authorized only for that email. Permissions seeded do not control these.
- Evidence: app/Filament/Clusters/Settings/Settings.php:17; app/Filament/Utils/PanelConfig.php:86-89 `ActivitylogPlugin::make()->authorize(fn() => Auth::user()->email == "kpiechowski25@gmail.com")`.
- decide: whether V2 needs a configurable super-operator concept.

### Impersonation
- Tag: confirmed · Impact: high
- What happens: The users list has an `Impersonate` row action (package stechstudio/filament-impersonate ^3.14). After switching, the redirect is to the admin dashboard. The package rules are: action hidden when the target is the current user, when already impersonating, when the current user's `canImpersonate()` (if defined) is false, or the target's `canBeImpersonated()` (if defined) is false. User defines neither method, so there are no extra restrictions. Any role that can reach the list (`view_any_user`: seeded Admin and Super Admin) can impersonate any other user, including a Super Admin. The impersonation guard is the panel auth guard (default `web`). The target must still pass `canAccessPanel` to use the panel; an `Admin`-only target cannot (inferred).
- No app-level permission, audit log or custom config for impersonation; no `config/filament-impersonate.php` or `laravel-impersonate.php` in config/. Whether the package logs activity: unknown (vendor, not read).
- Evidence: app/Filament/Resources/UserResource.php:~118 `Impersonate::make()->redirectTo(route('filament.admin.pages.dashboard'))`; vendor/stechstudio/filament-impersonate/src/Concerns/Impersonates.php `canBeImpersonated`, `getGuard`; composer.json:25; `grep canImpersonate|canBeImpersonated app` -> none.

### Other Spatie settings
- Tag: confirmed · Impact: normal
- What happens: teams off, wildcard permissions off, permission cache 24h in default store, standard table names, `register_permission_check_method=true`. After role edits the cache is invalidated by Spatie when changed via its API; the seeder calls `forgetCachedPermissions`.
- Evidence: config/permission.php; ShieldSeeder.php:12.

## Permissions defined but not checked / checked but not seeded
- Tag: confirmed · Impact: normal
- Seeded and policy-checked: all `*_user`, `*_car`, `*_offer`, taxonomy, `*_activitylog`, `*_submission`, `*_user::journal`, `*_shield::role` (the 6 prefixes) names exist in the policies (policy name list matches seeded names).
- Seeded but never checked by any policy in app/Policies (apart from page/widget perms which Shield checks itself, not examined): `view_role`, `view_any_role`, `create_role`, `update_role`, `delete_role`, `delete_any_role` (direct permissions; RolePolicy checks `*_shield::role` instead). Tag: confirmed.
- Checked but never seeded for any role: RolePolicy `forceDelete/forceDeleteAny/restore/restoreAny/replicate/reorder` check the literal strings `'{{ ForceDelete }}'`, `'{{ ForceDeleteAny }}'`, `'{{ Restore }}'`, `'{{ RestoreAny }}'`, `'{{ Replicate }}'`, `'{{ Reorder }}'` (unrendered stub placeholders). They deny always, which is harmless because RoleResource only exposes six prefixes. Tag: confirmed, `defect?`.
- Policy-granted to no one: `User` role holds `view_offer::...`? no. Permissions in User role (`view_activitylog`, `view_user::journal`, `delete_user::journal`) are used by other modules (r03/r05).
- Evidence: app/Policies/RolePolicy.php:66,74,82,90,98,106; ShieldSeeder.php:16.
- Direct-permission seeding bug: `makeDirectPermissions` calls `whereName($permission)` with an array element (not the name string), so the existence check is not meaningful; `firstOrCreate` is not used, so rerunning the seeder may attempt duplicate inserts and violate the unique (name, guard) index. Tag: inferred, `defect?`. Evidence: ShieldSeeder.php:~58-67.

## Contradictions and defects
- `Admin` role cannot enter the panel by Shield's rule, yet the code treats `Admin` as the administrative tier (isAdmin, getAdmins, user table badge). `defect?`/decide.
- Admin seeded with only user-management permissions, so it cannot see offers, submissions, taxonomies in permission terms; but `isAdmin()` branches grant admin behaviours in offer screens.
- Role-by-id (`Role::find(1)`, `Role::find(2)`) in DatabaseSeeder.
- Role column `match` has no default arm.
- User form lets a single-role limit and hides the section for admin records, so promoting someone to Admin is possible, demoting an admin via the form is not.

## Leads outside scope
- r01: registration, `has_accepted_terms`, `company_account`, password flows; HasPanelShield created hook assigns `User` on self-registration.
- r05: all `isAdmin()`, `can()`, `hasRole()` usages; `page_*` and `widget_*` permission checks.
- r03: `UserJournalPolicy` and permission names `*_user::journal`.
- Vendor impersonate package: whether it fires events or logs; the banner "leave impersonation" route.
- Settings cluster `Settings::canAccess` and `ApiSettings/GeneralSettings/MessagesSettings` pages use the Shield `page_*` perms plus hardcoded email.

## Could not establish
- Actual production roles/permissions: DB state may differ from seeders (roles can be edited live in RoleResource). Needs a DB dump or business answer.
- Whether any Admin accounts exist and how they enter the panel. Business answer.
- Whether `User` has `SoftDeletes` columns (users migration was not in the anchors; model has no trait, so Restore/ForceDelete actions' behaviour unverified).
- Whether the impersonate package fires events or logs (vendor not read).

## Coverage ledger
- Searched: `impersonat`, `canImpersonate|canBeImpersonated`, `Gate::`, `ShieldSeeder`, `assignRole|syncRoles|isSuper()|isAdmin()|getAdmins`, `can('...')` names in app/Policies, `intercept_gate|define_via_gate`, `Policy`, `canAccessPanel`, `kpiechowski25`.
- Examined: database/migrations/2024_10_06_133235_create_permission_tables.php; app/Filament/Resources/Shield/RoleResource.php and Pages/{CreateRole,EditRole,ListRoles,ViewRole}.php; app/Policies/RolePolicy.php; app/Policies/UserPolicy.php; database/seeders/{ShieldSeeder,ShieldSeeder_old,ShieldSeeder_old2,DatabaseSeeder}.php (old ones by diff); config/permission.php; config/filament-shield.php; app/Models/User.php; HasPanelShield (vendor); app/Filament/Resources/UserResource.php and Pages/{CreateUser,EditUser,ListUsers,ViewUser}.php; app/Providers/Filament/AdminPanelProvider.php; PanelConfig.php (plugins section); Shield service provider and FilamentShield.php (gate parts); Impersonates concern (vendor).
- Matched, not examined: other Policies (names list only via grep, not read in full: ActivityPolicy, CarPolicy, Offer*Policy, SubmissionPolicy, UserJournalPolicy); app/Providers/AppServiceProvider.php (grep showed no Gate/permission code; not read in full); isAdmin() callers (r05); config/fortify.php (only the registration feature line).
