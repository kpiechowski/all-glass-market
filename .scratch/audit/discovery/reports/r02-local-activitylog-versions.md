# r02 — local: spatie activity log and overtrue versionable

Question: What do V1's two package-based history mechanisms record, for which models, and who can see or act on them? · V1 commit: 511ce3b

## Summary
- Spatie activity log (v4.9.1, composer.lock) is on 8 models: Offer, Car, Setting, OfferType, OfferProducer, OfferColor, OfferEquipment, User. It records only a fixed field list per model. There are no manual `activity()` calls, no custom log name (so "default") and no explicit causer handling (the package default, the authenticated user, applies).
- The viewer is the rmsramos Filament resource "Logi". It is read-only and has date and event filters. In practice it is reachable only by one hard-coded e-mail (`kpiechowski25@gmail.com`), because `ActivityPolicy` appears not to be registered. The `*_activitylog` permissions in ShieldSeeder are therefore probably dead.
- Retention is configured (365 days) but nothing schedules `activitylog:clean`, so in practice nothing is deleted.
- Overtrue versionable applies to Offer only. It keeps SNAPSHOT versions of 9 fields in `versions`, with no cleanup (`keep_versions` 0). Revisions CAN be restored, from the "Revisions" button on EditOffer (admins only) or by URL (owner or admin, see below).
- The comment in BulkPriceUpdateSyncJob is correct: Offer logs `price`.

## Findings
### Activity log records changes to eight models, each with a fixed field list
- Tag: confirmed · Impact: normal
- What happens: Each model uses `LogsActivity` with `LogOptions::defaults()->logOnly([...])`. Fields per model:
  - Offer: price, eurocode, usage_status, rating, year, type.name, producer.name, color.name.
  - Car: name, model.
  - Setting: key, value.
  - OfferType, OfferProducer, OfferColor, OfferEquipment: name, status.
  - User: name, email (re-confirmed; password is not logged).
- `logOnlyDirty`, `dontSubmitEmptyLogs` and `useLogName` are not set anywhere. The package defaults apply: events created, updated and deleted, and log name "default". The "default" log name is inferred from `config/activitylog.php:21`, which is not overridden in the models.
- Evidence:
  - app/Models/Offer.php:229-247 Offer::getActivitylogOptions (the other relations and fields are commented out).
  - app/Models/Car.php:192-196.
  - app/Models/Setting.php:36-40.
  - app/Models/OfferModels/OfferType.php:94-98.
  - app/Models/OfferModels/OfferProducer.php:52-56.
  - app/Models/OfferModels/OfferColor.php:51-55.
  - app/Models/OfferEquipment.php:43-47.
  - app/Models/User.php:150-154.
  - config/activitylog.php:21 default_log_name 'default'.
  - A grep of app/ for `activity(`, `causedBy`, `withProperties` and `useLog` found no hits.

### Offer price changes are logged, including from bulk price update
- Tag: confirmed · Impact: normal
- What happens: BulkPriceUpdateSyncJob first sets the previous price with `saveQuietly()` (no model events, so nothing is logged). It then saves the final price with `save()`, so the old and new price are logged as an update event. The comment on lines 39-41 matches this behaviour. The causer is whoever is authenticated. In a queue job this is probably null, shown as "—". That is inferred and not traced at runtime.
- Evidence:
  - app/Jobs/Offer/BulkPriceUpdateSyncJob.php:34-44.
  - app/Models/Offer.php:231 'price' is in logOnly.

### Causer is the authenticated user, with no special handling
- Tag: inferred · Impact: normal
- What happens: No code sets a causer. Spatie resolves it from the default auth guard, since `default_auth_driver` is null. Changes made by jobs, console commands or API sync (for example WooCommerce sync) have no causer. The viewer shows "—" when `causer_id` is null.
- Evidence:
  - config/activitylog.php:30 default_auth_driver null.
  - vendor/rmsramos/activitylog/src/Resources/ActivitylogResource.php getCauserNameColumnCompoment returns '—' when causer_id is null.

### Activity log table schema
- Tag: confirmed · Impact: normal
- What happens: `activity_log` has log_name, description, subject (morph), causer (morph), properties (json), timestamps, event and batch_uuid. The viewer displays `event`.
- Evidence:
  - database/migrations/2024_10_14_193507_create_activity_log_table.php:12-20.
  - 2024_10_14_193508 adds `event`.
  - 2024_10_14_193509 adds `batch_uuid`. This is not examined in detail; it is the standard Spatie migration.

### Retention is configured but never executed
- Tag: confirmed (config) / inferred (no scheduled clean) · Impact: normal
- What happens: `delete_records_older_than_days` is 365. It only takes effect when `activitylog:clean` runs. No grep hit exists for "activitylog:clean" in app/, routes/ or bootstrap/. routes/console.php schedules only UpdateFullDataOfActiveAdvertsCommand twice a day (6:00 and 16:00) and a queue worker. The deployment cron or a server crontab is not visible. Activity rows therefore probably accumulate forever.
- Evidence:
  - config/activitylog.php:11.
  - routes/console.php:16-21.

### Activity log viewer: "Logi" resource, read-only
- Tag: confirmed · Impact: normal
- What happens: The viewer is registered as a plugin in the admin panel. The navigation item is labelled "Logi", with icon clipboard-document-list, sort 100, no group and no count badge. There is no create, edit or delete page. The pages are `index` and `view`.
  - The list is sorted by `id` descending.
  - Columns, using the plugin's package translations: log name (badge), event (badge coloured created green, updated yellow, deleted red, other blue), subject (class name plus "# id"), causer name (searchable), properties (toggleable, hidden by default) and created_at (format `d/m/Y H:i:s`).
  - Filters: a created-at date range (from and until) and an event select.
  - The detail view is a disabled form. It shows the causer, subject, description, log name, event, date, and the old and new attribute values as key-value lists.
  - There is no restore or revert action in the activity log.
- Evidence:
  - config/filament-activitylog.php:3-14.
  - app/Filament/Utils/PanelConfig.php:86-89.
  - vendor/rmsramos/activitylog/src/Resources/ActivitylogResource.php form, table, getPages (the table has no actions).
  - Not checked: the UI strings in the plugin's translation files (`activitylog::tables.*`), so the Polish labels are unknown, and the `lang/` override question.

### Who can open the activity log: one hard-coded e-mail
- Tag: confirmed (e-mail check) / inferred (policy not registered) · Impact: high (permissions)
- What happens:
  - The plugin is authorized with `Auth::user()->email == "kpiechowski25@gmail.com"`.
  - The resource's `canAccess()` uses the Gate policy for Spatie's Activity model if one exists. Otherwise it falls back to that e-mail closure.
  - `App\Policies\ActivityPolicy` exists and checks `view_any_activitylog` and the other `*_activitylog` permissions. It is not registered anywhere: no Gate::policy and no `$policies` in app/, bootstrap/ or config/. Laravel auto-discovery would not map `Spatie\Activitylog\Models\Activity` to `App\Policies\ActivityPolicy`. So the policy is probably not used and only the e-mail check decides access. Runtime would settle this.
  - Consequence: Admin and Super Admin roles cannot see "Logi" unless their e-mail is that one.
- Evidence:
  - app/Filament/Utils/PanelConfig.php:86-89.
  - vendor/rmsramos/activitylog/src/Resources/ActivitylogResource.php canAccess.
  - app/Policies/ActivityPolicy.php:1-100.
  - No registration found (grep of `ActivityPolicy` returns only its own file).

### The activitylog permissions are granted in ShieldSeeder, but with no effect
- Tag: confirmed · Impact: normal
- What happens:
  - The Super Admin role gets all `*_activitylog` permissions (view, view_any, create, update, restore, restore_any, replicate, reorder, delete, delete_any, force_delete, force_delete_any).
  - The User role gets only `view_activitylog` (single record), not `view_any_activitylog`.
  - The Admin role gets none.
  - If the policy is not registered (above), these grants do nothing.
  - ShieldSeeder_old2.php also mentions activitylog; it is an old seeder and was not compared.
- Evidence: database/seeders/ShieldSeeder.php:15 (the roles JSON string).

### Offer versions: snapshots created on create and update
- Tag: confirmed · Impact: normal
- What happens:
  - Offer uses `Versionable` with `VersionStrategy::SNAPSHOT`.
  - Versionable fields: price, eurocode, user_description, description, usage_status, rating, without_eurocode, alt_eurocodes, year. Excluded: id, user_id, created_at, updated_at, status.
  - An initial version is created on `created`, or before the first update if none exists. A new version is created on `updated`.
  - Under SNAPSHOT the version's `contents` are taken from `attributesToArray()`, so possibly the whole model, not only the listed fields. The package decides this and was not fully traced.
  - A force delete removes all of the offer's versions. A soft delete does not.
  - Table `versions` holds user_id, a morph to the versioned record, json contents, timestamps and `deleted_at` (soft deletes).
- Evidence:
  - app/Models/Offer.php:125-146.
  - vendor/overtrue/laravel-versionable/src/Versionable.php:29-66, 227-250.
  - database/migrations/2019_05_31_042934_create_versions_table.php:14-23.
  - 2020_07_03_163707_add_deleted_at_to_versions.php:17.

### Version author is the offer's owner, not necessarily the editor
- Tag: confirmed (package code) · Impact: normal
- What happens: `getVersionUserId()` returns the offer's own `user_id` attribute if set, else `auth()->id()`. Offer has a `user_id` (the owner, see OfferPolicy). So a version made by an admin editing someone else's offer is probably attributed to the owner. Not traced at runtime.
- Evidence:
  - vendor/overtrue/laravel-versionable/src/Versionable.php:338-341.
  - app/Policies/OfferPolicy.php:57 shows `$offer->user_id` is the owner.
  - config/versionable.php:13 user_foreign_key 'user_id'.

### Version retention: unlimited
- Tag: confirmed · Impact: normal
- What happens: `keep_versions` is 0, meaning keep all. The Offer model does not override it. No cleanup job exists. `grep removeOldVersions|removeVersion` found nothing in app/.
- Evidence: config/versionable.php:7.

### Revisions page: view history of an offer and restore it
- Tag: confirmed · Impact: high (state change, permissions)
- What happens:
  - On EditOffer a header button "Revisions" (package label; the Polish translation is unknown) is shown with a badge equal to the number of versions minus 1. It is hidden for non-admins (`!Auth::user()->isAdmin()`) and hidden when the offer has 1 or fewer versions.
  - The page is `/{record}/revisions`, `OfferRevisionsPage`. It has a "Powrót" button back to the edit page.
  - The page returns 404 if the offer has 1 or fewer versions. It returns 403 unless the user passes `OfferResource::canEdit` for the offer. For Offer, `OfferPolicy::update` allows admins and the offer's owner.
  - The page lists the versions (10 per page, excluding the first, newest first). It shows a side-by-side word-level diff of the selected version, with previous and next navigation.
  - Restore action `restoreVersion`: confirmation modal, then `$this->version->previousVersion()->revert()`, then redirect to the edit page. This reverts the offer to the version BEFORE the one shown. That wording relies on the package's semantics and was not traced in `Version::revert`.
  - Who can restore: users with the page's `canEdit` (admins, or the offer's owner). The button is hidden for non-admins, but the direct URL is still reachable by owners. That makes the button's admin-only restriction not an authorization boundary.
  - The revert saves the offer, so it should itself create a new version and an activity-log entry (Offer is under both mechanisms). This is inferred, not traced.
  - Whether the revert also pushes to WooCommerce or Otomoto through Offer observers was not traced.
- Evidence:
  - app/Filament/Clusters/Offers/Resources/OfferResource/Pages/EditOffer.php:34-35.
  - app/Filament/Clusters/Offers/Pages/OfferRevisionsPage.php:11-21.
  - app/Filament/Clusters/Offers/Resources/OfferResource.php:297 route 'revisions'.
  - vendor/mansoor/filament-versionable/src/RevisionsPage.php:42-120 (mount, diff, restoreVersion, authorizeAccess).
  - vendor/mansoor/filament-versionable/src/Page/RevisionsAction.php:20-34.
  - app/Policies/OfferPolicy.php:54-58.

## Contradictions and defects
- The hard-coded e-mail gating the activity log (PanelConfig.php:88) is a defect-like restriction (`defect?`): it is a developer's personal address, it makes the whole Shield permission set for activity log redundant, and it hides history from business admins. Whether it is intentional is a `decide` for the business.
- `ActivityPolicy` is probably unregistered, so the Shield activity-log permissions grant nothing (`defect?`).
- Revisions button hidden for owners while the page is reachable by them through the URL (`defect?`, minor).

## Leads outside scope
- Offer also uses `HasChangesLog` and `LoggableModel` (the custom ChangesLog module, r01), so offers have up to three overlapping histories.
- UserJournal (Users module).
- WooCommerce and Otomoto sync triggered from Offer saves, which a revert may trigger.

## Could not establish
- Whether the activity viewer is effectively limited to the e-mail. A runtime check of Gate resolution would settle it.
- Whether `activitylog:clean` runs in production (server cron is not in the repo).
- The Polish UI labels of the plugin pages. They come from vendor translations and `lang/` was not searched.
- `Version::revert` exact semantics.
- Whether `$offer->user_id` is set on offers created by admins.

## Coverage ledger
- Searched: LogsActivity, activity(, causedBy, Versionable, activitylog, Activitylog, ActivityPolicy, Gate::policy, removeVersion, keep_versions, activitylog:clean, Schedule.
- Examined: config/activitylog.php, config/filament-activitylog.php, config/versionable.php, app/Policies/ActivityPolicy.php, app/Filament/Utils/PanelConfig.php (lines 80-95), migrations 2024_10_14_193507/08/09 (09 only listed), 2019_05_31_042934 and 2020_07_03_163707, models Car, Setting, OfferType, OfferProducer, OfferColor, OfferEquipment (OfferEquipment.php), User, Offer (relevant parts), app/Filament/Clusters/Offers/Pages/OfferRevisionsPage.php, EditOffer.php (version parts), BulkPriceUpdateSyncJob.php:25-60, database/seeders/ShieldSeeder.php, routes/console.php, app/Policies/OfferPolicy.php (update/viewAny), vendor rmsramos ActivitylogResource and the plugin, vendor mansoor RevisionsPage and RevisionsAction, vendor overtrue Versionable.
- Matched, not examined: database/seeders/ShieldSeeder_old2.php (old seeder), 2024_10_14_193509 (batch_uuid, standard).
