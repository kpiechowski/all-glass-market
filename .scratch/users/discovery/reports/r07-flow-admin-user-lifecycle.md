# r07 — flow: Admin user lifecycle (create / edit / delete)

Question: what happens end to end on create, edit, delete of a user in Filament UserResource; which roles end up on the user; what happens to related data · V1 commit: 511ce3b

## Summary
- Create with role X: HasPanelShield `created` hook assigns "User" first, then the Select's relationship save detaches every role not in the form state and syncs X. End state is [X] only (Admin -> [Admin], Super Admin -> [Super Admin], User -> [User]). Confirmed by reading code; no runtime run.
- Edit: roles are synced to the single selected role before the model update. User -> Admin leaves [Admin]. Such a user cannot pass canAccessPanel (needs Super Admin or User). Roles section is hidden, and so not saved, for records that already are Admin or Super Admin.
- Delete: only the EditUser header "Delete" does anything. ForceDelete and Restore are hidden because User has no SoftDeletes. The delete is a plain `$record->delete()`, with no explicit transaction. All Spatie role pivots go. Rows in tables without an FK are left orphaned. The Jetstream DeleteUser action is not used by Filament.

## Findings

### Create user: sequence of events
- Tag: confirmed · Impact: high (permissions)
- What happens: `CreateRecord::create()` begins a DB transaction, then `form->getState()`. In getState, `saveRelationships()` is a no-op because the record does not exist yet. `handleRecordCreation` does `new User($data)->save()` (fillable name/email/password only). The `created` hook of HasPanelShield runs and `assignRole('User')`. Then `form->model(record)->saveRelationships()` runs the roles Select callback. It detaches every currently attached role not in the state, which includes "User" when the selected role is not "User". It then syncs the selected role (detaching:false). The activity log writes a "created" entry with name and email. Other form fields (phone, company_*, city*, shipping_information) are not in `$fillable`, so they are not mass-assigned on create. See Leads.
- Evidence: vendor/filament/filament/src/Resources/Pages/CreateRecord.php:92-109 (order: getState, handleRecordCreation, saveRelationships); vendor/filament/forms/src/Components/Concerns/BelongsToModel.php:36-38 (skips if record does not exist); vendor/filament/forms/src/Components/Select.php:1004-1041 (`recordsToDetach = related roles − state`, `detach`, then `sync`); vendor/bezhansalleh/filament-shield/src/Traits/HasPanelShield.php:15-17 (`created` -> assignRole); config/filament-shield.php:27-30 (panel_user enabled, name "User"); app/Models/User.php:41-46 ($fillable), :243 area form.
- Role end-state: [Admin] / [Super Admin] / [User]; "confirmed" by code reading. Not run. Not [User, Admin].

### Hook only fires outside the console
- Tag: confirmed · Impact: normal
- What happens: the `created` hook is registered only when `!app()->runningInConsole()`. For web requests it is registered. Seeders, tinker and artisan-created users do not get "User" automatically. This is also why a user created via console and one created via the panel differ.
- Evidence: HasPanelShield.php:11-12.

### Password handling on create and edit
- Tag: confirmed · Impact: normal
- What happens: the form hashes with `Hash::make` in dehydrateStateUsing. The model also has the `'password' => 'hashed'` cast. Laravel's hashed cast checks whether the value is already hashed and skips re-hashing, so there is no double hash. Required on create only. On edit an empty value is not dehydrated (the password is kept).
- Evidence: app/Models/User.php form `password` TextInput (dehydrateStateUsing / dehydrated(filled) / required(operation==='create')), casts() 'hashed'. Skipping re-hash is Laravel behaviour (inferred from framework, not re-read here).

### Edit user: roles changed
- Tag: confirmed · Impact: high (permissions)
- What happens: `EditRecord::save()` calls `getState(afterValidate: closure)`. With the closure, `saveRelationships()` runs after validation and BEFORE `handleRecordUpdate` (`$record->update($data)`). The Select callback detaches all roles other than the selected one, then syncs the selected one. User -> Admin leaves exactly [Admin]; "User" is removed. The whole save runs in a DB transaction (beginDatabaseTransaction / commit / rollback on throwable). Password is updated only when filled. Activity log records name/email changes only.
- Evidence: vendor/filament/forms/src/Concerns/HasState.php:244-258; vendor/filament/filament/src/Resources/Pages/EditRecord.php:143-156,254-258; Select.php:1004-1041; User.php getActivitylogOptions logOnly(['name','email']).
- Role end-state: [Admin]; confirmed by code reading.

### Admin without "User" role cannot enter the panel
- Tag: confirmed · Impact: high (permissions)
- What happens: `canAccessPanel` = hasRole('Super Admin') || hasRole('User'). A user with only [Admin] returns false, and so cannot log in to the panel. Super Admin passes. Consequence: an admin created or edited through this UI with the role Admin is locked out of the panel (they remain `isAdmin()` for app logic). This contradicts the apparent intent, since the hook assigns "User" precisely for panel access. Whether this is intended is a business question.
- Evidence: HasPanelShield.php:21-24; Select.php:1004-1041 (detach); User.php isAdmin().
- See Contradictions.

### Roles section hidden for existing Admin / Super Admin records
- Tag: confirmed · Impact: high
- What happens: the Section 'Rola' is `hidden` when the record isAdmin(). Hidden components' relationships are not saved (shouldSaveRelationshipsWhenHidden = false), so editing an admin cannot change their roles. Their roles are untouched by edit. Requires at least one role, so an admin's roles cannot be changed in this UI. A non-admin can be promoted to Admin or Super Admin (no option filter on the Select, and minItems 1 / maxItems 1).
- Evidence: User.php getForm() `->hidden(fn($record) => !empty($record) && $record->isAdmin())`; BelongsToModel.php:45-47 (skip when hidden; the default is false at :20).

### Delete user: only the EditUser header "Delete"
- Tag: confirmed · Impact: high (deletion)
- What happens: the table row actions offer Impersonate and Edit only; the Delete / bulk Delete actions in UserResource::table are commented out. Delete is available only via the EditUser header action, after a confirmation modal (requiresConfirmation). Visibility is also gated by UserPolicy::delete (`delete_user` permission). The action calls `$record->delete()` (hard delete) via `process()` with no transaction wrapper. Sequence: `deleting` listeners (Spatie HasRoles: `roles()->detach()`; Shield: `removeRole('User')`) run, then the row is deleted. Without a transaction, a DB FK failure after role removal would leave roles already detached (no FK blocks deletion, see below, so the risk is low). Activity log writes a "deleted" entry.
- Evidence: app/Filament/Resources/UserResource.php table() actions (Delete commented); EditUser.php:19-23; vendor/filament/actions/src/DeleteAction.php:22,48-49; vendor/spatie/laravel-permission/src/Traits/HasRoles.php:21-30 (User has no `isForceDeleting`, so the guard does not return and the roles are detached); HasPanelShield.php:17; app/Policies/UserPolicy.php:64-66.
- Note: ambiguity about transaction: Filament DeleteAction has no transaction; Laravel `delete()` is a single statement. Tag confirmed for "no explicit transaction".

### ForceDelete and Restore are inert
- Tag: confirmed · Impact: normal
- What happens: both actions are `hidden` unless the record responds to `trashed()` and is trashed. User has no SoftDeletes (no `trashed` method), so `if (!method_exists($record,'trashed'))` makes them hidden (ForceDelete and Restore both). They never show and do nothing. `getEloquentQuery` removes SoftDeletingScope, which is harmless. Note the DeleteAction's own hidden check is also false for User, so Delete shows.
- Evidence: vendor/filament/actions/src/ForceDeleteAction.php:49-53, RestoreAction.php:57-61; app/Models/User.php (no SoftDeletes); UserResource.php getEloquentQuery.

### What deletion does to related rows
- Tag: confirmed (FK from migrations; actual DB not inspected) · Impact: high
- Cascade-deleted: `user_journals` (cascade). Spatie `model_has_roles` / `model_has_permissions` rows removed by the detach in the deleting hook (the FK cascade there is on role/permission, not on the model, so the detach is what cleans them).
- Set to null: `submissions.user_id`, `changes_logs.user_id`, `otomoto_bulk_actions.user_id`, `offer_inquiries.user_id`.
- No constraint, rows stay orphaned: `offers.user_id` (NOT NULL, no constraint; offers remain pointing to a missing user), `offer_reservations.user_id`, `settings.user_id`, `user_notifications.user_id`, `sessions.user_id`, versionable `versions.user_id` (nullable, no FK), `personal_access_tokens` (morph tokenable, no FK; remain), `notifications` (morph, remain), activity_log rows (subject/causer, remain).
- No code deletes offers, tokens, profile photo, or sessions on delete. The Offer `deleted` hook is not triggered, since offers are not deleted.
- Evidence: database/migrations/2024_12_01_180306_create_user_journals_table.php:18; 2024_11_10_145443_create_submissions_table.php:18; 2025_04_12_181156_create_changes_logs_table.php:15; 2026_07_25_000000_create_otomoto_bulk_actions_table.php:12; 2025_11_15_153812_create_offer_inquiries_table.php:21-24; 2024_10_06_151733_create_offers_table.php:49; 2025_01_26_5555235_create_offer_reservations_table.php:19; 2024_10_13_111544_create_settings_table.php:18; 2025_03_29_174632_create_user_notifications_table.php:16; 0001_01_01_000000_create_users_table.php:46; 2019_05_31_042934_create_versions_table.php:18 and 2021_03_18_160750_make_user_nullable.php:17; 2024_10_06_123456_create_personal_access_tokens_table.php:16. User.php does not use HasChangesLog, no observers (no app/Observers; grep for User observe/deleted in app returned only Offer and HasChangesLog).

### Deleted user's open session
- Tag: inferred · Impact: high
- What happens: the session driver defaults to `database` (.env.example SESSION_DRIVER=database); the `sessions` row is not removed on delete. On the next request the session guard retrieves the user by id, gets null, and is guest, so Filament's Authenticate middleware redirects to the login page. The panel also uses `AuthenticateSession`. Not traced in vendor and not run.
- Evidence: config/session.php:21; AdminPanelProvider.php:112,119-120; sessions migration (user_id no FK).

### Jetstream DeleteUser differs from the Filament path
- Tag: confirmed · Impact: normal
- What happens: `App\Actions\Jetstream\DeleteUser` does deleteProfilePhoto(), deletes all Sanctum tokens, then `$user->delete()`. It is registered via `Jetstream::deleteUsersUsing` and runs only on the Jetstream self-delete (profile) path; the Filament delete does NOT delete the profile photo file or tokens. The profile photo file (HasProfilePhoto, stored on the configured disk) stays orphaned after an admin delete.
- Evidence: app/Actions/Jetstream/DeleteUser.php:14-18; app/Providers/JetstreamServiceProvider.php:26; no other callers found in app/ (grep).

## Contradictions and defects
- defect? (Admin lock-out): creating or editing a user as Admin leaves [Admin] only, so canAccessPanel is false. Roles-section hidden for admins means the admin can't be repaired in this UI. Tag: defect? / decide whether Admins are intended to open the panel (maybe a separate access path exists, such as Shield panel config; not checked).
- defect? (orphans): deleting a user leaves offers, reservations, settings, notifications, tokens, sessions and profile photo behind; offers.user_id then points to nothing, and `Offer->user` could be null in code that assumes non-null (not examined).
- Inert actions ForceDelete / Restore in EditUser header (dead code).

## Leads outside scope
- Create form fields phone, company_*, city, city_code and shipping_information are not in User::$fillable (only name, email, password). If `fillable` is the only path, these values would be silently dropped on create and on update via `$record->update($data)`. Not verified whether they are fillable by another mechanism (e.g. `$guarded`, Model::unguard in a provider). Would be a major finding for the Users module; worth a quick check (`grep unguard app/Providers`). Not done.
- Order of the HasRoles `deleting` and Shield `deleting` listeners is not relevant to the result.
- The role-section Select offers all roles, including "Super Admin" (promotion path), and any admin who can create users can mint Super Admins.

## Could not establish
- Runtime behaviour (actual role end-state, session after delete): not run, since V1 must not be executed. A runtime check of create-with-Admin would settle the role end-state with certainty.
- Whether `unguard` is used (see Leads). Whether the production DB has additional FKs beyond the migrations.

## Coverage ledger
- Searched: HasPanelShield, saveRelationships, saveRelationshipsUsing, recordsToDetach, bootHasRoles, removeRole, SoftDeletes, trashed, foreignId/foreignIdFor/user_id in migrations, SESSION_DRIVER, observers/deleting/deleted in app, DeleteUser usage, unfound HasChangesLog on User.
- Examined: app/Filament/Resources/UserResource.php, UserResource/Pages/CreateUser.php, EditUser.php, app/Models/User.php, app/Actions/Jetstream/DeleteUser.php, app/Policies/UserPolicy.php (signature level), the migrations listed above, config/filament-shield.php, config/session.php, vendor Filament CreateRecord/EditRecord/HasState/BelongsToModel/Select/DeleteAction/ForceDeleteAction/RestoreAction, vendor Shield HasPanelShield and Utils, Spatie HasRoles.
- Matched, not examined: app/Models/Offer.php (deleted hook; irrelevant since offers aren't deleted), Offer relation null-safety, app/Providers/AppServiceProvider.php (unguard check not done), vendor Laravel hashed cast behaviour.
