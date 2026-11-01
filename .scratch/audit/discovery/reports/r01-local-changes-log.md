# r01 — local: V1 custom change log (ChangesLog)

Question: What does V1's ChangesLog record, when, for which models, with what content, and how can users see, filter or delete it? · V1 commit: 511ce3b

## Summary
- Only one model is logged: Offer (the sole user of HasChangesLog / LoggableModel).
- Hooks on created, updated, deleted write one `changes_logs` row each (Polish message, JSON old/new per field, `meta_data.action`). Actor is `Auth::id()`, so null in console/queue/unauthenticated cases.
- `saveQuietly()` skips the log; V1 uses it in several Offer flows, so the log is incomplete by design or accident.
- UI: Offer view page, "Historia zmian oferty", admins only, not on the edit page. User view page, "Rejestr zmian użytkownika", lists entries the user made. Both allow slide-over detail, delete and bulk delete. No filters, only search on columns.
- The detail view has "Przywróć nowsze" / "Przywróć poprzednie" buttons that write old/new values back to the Offer. This is a rollback feature the archive scan did not mention.
- Entries are deleted for good (no soft delete) when the Offer is force-deleted. When the acting user is deleted, user_id is set to null.

## Findings

### Offer creation is logged with every non-excluded attribute as old=null, new=value
- Tag: confirmed · Impact: normal
- What happens: after the `created` model event, a row is written with message "Użytkownik dodał nowy rekord.", `meta_data = {action: created}`, `changes` = the dirty attributes except the not-loggable ones. Created always writes a row, even if `changes` ends up empty.
- Evidence: Traits/HasChangesLog.php:17-19 static::created; Services/ChangesLogService.php:12-16 createCreatedLogEntry; :42-75 getRawChanges (old = null when the key is absent from `original`, :64-66).

### Offer updates are logged only when at least one loggable field changed
- Tag: confirmed · Impact: normal
- What happens: on `updated`, skip if `!isDirty()` or if filtering leaves no changes. Otherwise message "Użytkownik wprowadził zmiany w [N] polu." (N=1) or "…w [N] polach.", action `updated`. Each field stores `{old,new}`. New is `getAttribute` (cast value). Old is `getOriginal` (cast value). The message always says "Użytkownik" even for system actors.
- Evidence: HasChangesLog.php:21-23; ChangesLogService.php:23-40, :35-37 message; :63-70.
- Caveat: Eloquent `updated` fires after save, when `isDirty()` may already be false (`getDirty` is synced after the event in Laravel; `syncChanges` runs before the `updated` event and `syncOriginal` runs after save, with `updated` fired in `finishSave` after `syncOriginal`). I did not verify against vendor or runtime that dirty values are still available in `updated`. Tag: unknown (runtime check). The code and the "already known" scan assume it works.

### Delete is logged as a plain "deleted" entry with no changes; force delete wipes the Offer's history instead
- Tag: confirmed · Impact: high (deletion)
- What happens: on `deleted`, if the model is force-deleting, all its `changesLogs` are deleted and no entry is written. Otherwise (soft delete) a row is written with message "Użytkownik usunął rekord.", `changes = []`, action `deleted`. There is no `restored` hook, so restoring an Offer is not logged.
- Evidence: HasChangesLog.php:25-33; ChangesLogService.php:18-21. Offer uses SoftDeletes (Models/Offer.php:52). No `restored` hook in the trait (HasChangesLog.php:15-34).

### Offer: which fields are recorded
- Tag: confirmed · Impact: normal
- What happens: `$loggable = ['*']` means all attributes. Excluded: `id, sku, user_id, updated_at, created_at, deleted_at`. The default (when a model defines none) is `['*']` and excludes `id, created_at, updated_at, deleted_at`. A field is skipped if the loggable list is not `['*']` and does not contain it, or if it is in the not-loggable list. Only direct attributes of the offers table are logged. Relations (e.g. `eurocodes`, equipment, media) are not captured by the hook. A `$logFieldLabels` entry for `eurocodes` exists, but nothing writes such a field in this hook, so it only matters if the column exists on offers (unknown).
- Evidence: Models/Offer.php:60-61; HasChangesLog.php:36-44; ChangesLogService.php:49-61; Models/Offer.php:83-97 labels.

### Old/new rendering in the detail view
- Tag: confirmed · Impact: normal
- What happens: the "Zmiany" section shows only if `changes` is not empty. Per field: label (from `Offer::$logFieldLabels`, fallback `ucfirst(field)`), old as a red badge, new as a green badge. Labels: oem "Kod OEM", status "Status", year "Rok produkcji", eurocode "Eurocode/Oem", eurocodes "Eurokody zatwierdzone", quantity "Ilość", usage_status "Stan szyby", price "Cena", offer_producer_id "Producent", offer_type_id "Typ szyby", offer_color_id "Kolor szyby", without_eurocode "Bez eurokodu", alt_eurocodes "Alternatywne". Transforms: `offer_producer_id` → producer name via OfferProducer::find (null if the producer is deleted); `alt_eurocodes` → comma-joined list. `offer_type_id` and `offer_color_id` are shown as raw ids. Arrays without a transform are shown as JSON. The detail view also shows "Użytkownik", "Data utworzenia" (d.m.Y H:i) and "Wiadomość" under "Informacje ogólne".
- Evidence: Components/ChangesDetailsInfolistSchema.php:19-31, :33-75; ChangesLogService.php:77-94; Models/Offer.php:83-125 (formatArrayForDisplay, resolveProducerName; resolveEurocodes is an empty stub, its mapping commented out at :101).
- Defect candidate: `transformChangesForDisplay($record->loggable, …)` has no null check. If the logged model is gone the view breaks. `loggable()` uses withTrashed, so soft-deleted is fine and only a force-deleted model would fail, but the logs are removed with it.

### Rollback from a log entry ("Przywróć nowsze" / "Przywróć poprzednie")
- Tag: confirmed · Impact: high (state change, no permission check)
- What happens: in the detail view footer, "Przywróć nowsze" (green) writes each changed field's `new` value back to the Offer, and "Przywróć poprzednie" (orange) writes the `old` value. The latter is hidden when action is "created". It calls `$model->update($data)`, which fires the hooks again and creates a new log entry. Success notification: "Pomyślnie przywrócono". No confirmation dialog. No separate permission beyond viewing the relation manager. On the user-view tab it is available to anyone who sees the tab. Rollback of an entry that has no `old` key silently drops the field. Created entries restore `old=null` is hidden. Values are written via update, so mass-assignment `$fillable` rules apply (not examined).
- Evidence: ChangesDetailsInfolistSchema.php:35-44; ChangesLogService.php:108-123.
- Notes: `meta_data['action']` is read without `??`, so a row without it errors. Rollback on the user tab for a deleted entry (changes=[]) is not shown because the section is hidden when `changes` is empty.

### Actor recorded is Auth::id() at write time
- Tag: confirmed · Impact: normal
- What happens: `user_id = Auth::id()`. In console commands, queue jobs, webhooks, or unauthenticated requests it is null. The UI then shows the user column empty, and `$record->user()->exists()` is false so the colour is `info`. The message text is the same ("Użytkownik …") regardless. Column `user_id` is nullable, with `nullOnDelete` when the user is deleted. Jobs that call `save()` (not quietly) on offers run without auth and so log with null actor, e.g. BulkPriceUpdateSyncJob step 2 (not quiet). I did not trace all queue paths.
- Evidence: ChangesLogService.php:98-100; migration :15; Components/ChangesTableSchema.php:38-48; Jobs/Offer/BulkPriceUpdateSyncJob.php:36 and following comment.

### Several Offer saves bypass the log (saveQuietly)
- Tag: confirmed · Impact: normal
- What happens: these save without firing events, so no entry: EditOffer.php:167 (after equipment sync), CreateOffer.php:87 (manual SKU, but sku is not loggable anyway), BulkPriceUpdateSyncJob.php:36 (the silent price revert), SynchOemCodeFromWordpressJob.php:57 (oem set from WordPress). The SKU/oem sync is therefore never logged. Offer has `LogsActivity` and `Versionable` too (r02).
- Evidence: paths above at the cited lines.

### Offer change history tab (admin only, view page only)
- Tag: confirmed · Impact: high (permissions)
- What happens: the relation manager "Historia zmian oferty" is mounted on OfferResource. It is visible only when `Auth::user()->isAdmin()` (role Admin or Super Admin) and the page is not `EditOffer`, so it appears on the view page (and any other page of the resource except edit). Table columns: created_at (date-time plus relative "Dodano:"), user as initials with the full name in a tooltip (links to the user view page; `warning` colour for normal users, `info` for company accounts or no user), message coloured by action (created green, updated primary, deleted red). Default sort created_at desc. Search: "Dodano:" (created_at) and message. No filters (empty array). Row actions: View (slide-over) and Delete. Bulk: delete. The relation is read-only (`isReadOnly` true), yet the delete actions are defined. Filament's read-only mode hides the actions on the relation manager in the view page. Whether the delete buttons are effectively shown is unknown without runtime; the tab is mounted from `getRelations()` and `isReadOnly()` is true.
- Evidence: Filament/Resources/Shared/ChangesRelationManager.php:29-39, :48-73; Clusters/Offers/Resources/OfferResource.php:79-84; Components/ChangesTableSchema.php; Models/User.php:132-135 isAdmin.
- Note: the table shows no title or resource column (commented out at ChangesTableSchema.php:50-65), as it is per-offer.

### User change tab: entries made BY the user, across loggable models
- Tag: confirmed · Impact: high (deletion)
- What happens: on the user view page, "Rejestr zmian użytkownika" shows `userChangesLogs`, i.e. `ChangesLog where user_id = user`. Columns: date, resource name (Offer's `sku`) with the Offer icon `heroicon-o-rectangle-stack`, title (primary eurocode code, or "brak eurocodu" when `without_eurocode`), linking to the ViewOffer page, plus the message. The tab is not read-only (`isReadOnly` false). View (slide-over), Delete and bulk delete are available. There is no `canViewForRecord` override, so no admin check at tab level: it follows whoever can open the user view page and the default Filament policy handling (no ChangesLog policy exists in app/Policies). Who can reach ViewUser was not examined here (Users module). Search covers resource, title, message, created_at. No filters. Default sort desc.
- Evidence: Filament/Resources/UserResource/RelationManagers/UserChangesRelationManager.php:18-54; UserResource/Pages/ViewUser.php:29-35; Models/User.php:100-103; Components/UserChangesTableSchema.php:37-59; Models/Offer.php:63-81; ls app/Policies (no ChangesLogPolicy).
- Defects: `getLoggableTitle()` returns `string` but `primaryEurocode()?->code` may be null, which would throw a TypeError on entries for offers with no primary eurocode and `without_eurocode` false (defect?). For a force-deleted offer, entries are gone anyway. `getLoggableResourceName()` is the SKU, not a resource label.

### Retention and cascade
- Tag: confirmed · Impact: high (deletion)
- What happens: there is no retention/pruning job in this scope (none found by grep; schedule not checked, see ledger). Rows are deleted only by: UI delete/bulk delete, Offer force delete (HasChangesLog.php:27-30), and no cascade from users (user_id nulled). `morphs('loggable')` has no FK, so it is not enforced by the DB. `changes` and `meta_data` JSON columns are not nullable.
- Evidence: migration :15-21; HasChangesLog.php:27-30; Models/ChangesLog.php:51-54 morphTo()->withTrashed().

### Model/table plumbing
- Tag: confirmed · Impact: normal
- What happens: `ChangesLog` has casts `changes` and `meta_data` to array and no `$fillable`/`$guarded` in the file. `ChangesLog::create([...])` therefore relies on a global unguard (not found in the files I read). Tag: unknown whether mass assignment is globally unguarded (`Model::unguard()` search is below). The singleton `'changes-log'` is bound in ChangesLogServiceProvider and registered from AppServiceProvider.php:26; the facade `ChangesLogger` resolves it.
- Evidence: Models/ChangesLog.php:9-16; ChangesLogServiceProvider.php:13-18; Providers/AppServiceProvider.php:26.

## Contradictions and defects
- `$loggable ['*']` comment and label `eurocodes` imply relation logging that the hook does not do (attributes only).
- Rollback has no confirmation and no guard (see finding). Possible stale `loggable` null error.
- Rollback "Przywróć nowsze" is shown for `deleted` entries? No: the section is hidden when `changes` is empty.
- The Offer view-page relation manager is read-only yet defines delete actions (see finding; unknown effect).

## Leads outside scope
- Offer also uses `LogsActivity` and `Versionable` (r02).
- Users module: who can open ViewUser, which determines who sees and deletes entries on the user tab.
- OfferResource edit page excludes the history tab: possible deliberate business choice.

## Could not establish
- Whether `updated` hook sees dirty data (see finding): needs a runtime test or reading vendor Eloquent. I did not run anything.
- Whether `ChangesLog::create` works (guarded/fillable): a grep for `unguard` was not done.
- Whether read-only mode hides Delete actions on the Offer tab: runtime.
- Whether a scheduled prune exists: routes/console.php and Kernel were not read.

## Coverage ledger
- Searched: HasChangesLog, LoggableModel, ChangesRelationManager, UserChangesRelationManager, ChangesLogger, ChangesLogService, ChangesLog, changesLogs, userChangesLogs in app/config/database/routes/resources/tests/lang; saveQuietly across app; `logField`; Policies directory.
- Examined (in full): all 8 files under app/Modules/ChangesLog/**, app/Models/ChangesLog.php, the create_changes_logs migration, Shared/ChangesRelationManager.php, UserChangesRelationManager.php. Partly: Models/Offer.php:40-140, Models/User.php:95-135, OfferResource.php:70-95, ViewUser.php:20-45, EditOffer.php:160-170, CreateOffer.php:80-90, BulkPriceUpdateSyncJob.php:25-40, SynchOemCodeFromWordpressJob.php:50-58.
- Matched, not examined: UserResource.php and UserPolicy.php (the Users module owns them), other saveQuietly users (Car, Wordpress fetch jobs, Otomoto, MigrateOemCodes; not Offer-related or out of scope), the tests folder (no ChangesLog references), schedule files.
