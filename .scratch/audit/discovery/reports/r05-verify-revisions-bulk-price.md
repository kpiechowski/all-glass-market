# r05 — verify: revisions, restore, rollback, bulk price, ActivityPolicy

<!-- Saved by the manager from the investigator's inline hand-back (the investigator did not write the file). Content condensed, verdicts and evidence unchanged. -->

V1 commit: 511ce3b

## Verdicts

1. **partly** — "Revisions" button is admin-only, but EditOffer's `->hidden()` overwrites the package's "≤1 version" rule (`vendor/filament/actions/src/Concerns/CanBeHidden.php:69-72`), so admins see it on single-version offers; the page then 404s (`vendor/mansoor/filament-versionable/src/RevisionsPage.php:50`). Evidence: `EditOffer.php:34-35`, `RevisionsAction.php:22`.
2. **confirmed** — page `mount()` → `authorizeAccess()` → `OfferResource::canEdit` → `OfferPolicy::update` = `isAdmin() || user->id == offer->user_id`; owner reaches `/{record}/revisions` by URL. Evidence: `RevisionsPage.php:56-60,159-162`, `OfferResource.php:297`, `app/Policies/OfferPolicy.php`.
3. **confirmed** — restore `requiresConfirmation()`; `restoreVersion()` runs `$this->version->previousVersion()->revert()` (version BEFORE the viewed one), redirect to edit. `revert()` = `revertWithoutSaving()->save()`; SNAPSHOT merges first version + target onto current attributes. Evidence: `RevisionsPage.php:108-117,129-134`, `overtrue/.../Version.php:~95-125`.
4. **partly** — restore is a normal `save()`; each effect only if something is dirty: change-log entry by restorer; activity entry by restorer; new version only if a versionable field changed; WooCommerce sync synchronously (`afterUpdateSyncAction`, when wooSync + auto_update + qualifying status, `Offer.php:~211-236`); queued Otomoto `UpdateAdvertAfterOfferChange` via `OfferUpdatedEvent` when Active with an advert (`OfferEventServiceProvider.php:32-34`, registered `bootstrap/providers.php:10`; `UpdateAdvertAfterOfferChange.php:27-43`). Hooks at `Offer.php:157-161`.
5. **confirmed** — version author = `getAttribute('user_id') ?? auth()->id()` → normally the owner, also after admin edit or restore. Evidence: `Versionable.php getVersionUserId`, `Version.php createForModel`, `config/versionable.php`.
6. **confirmed** — "Przywróć nowsze/poprzednie" → `rollbackChanges` → `$log->loggable->update($data)`: same events as 4 (change log, activity, version, Woo sync, Otomoto listener). Generic for any loggable model. Evidence: `ChangesDetailsInfolistSchema.php:36-42`, `ChangesLogService.php rollbackChanges`.
7. **partly** — bulk action: price set + `saveQuietly()` per offer (≤0 skipped), dispatch `BulkPriceUpdateSyncJob` in chunks of 15. Job: restore previous price quietly, then `save()` final price → change log and activity with null actor (`inferred`: no queue auth), plus a version authored by the owner, plus Woo sync and queued Otomoto listener, plus explicit `UpdateAdvertAction::handle` for any offer with an advert (possible double Otomoto update when Active, `defect?`). Evidence: `ChangePriceBulkAction.php:~84-120`, `BulkPriceUpdateSyncJob.php` steps 1-3.
8. **confirmed** — `ActivityPolicy` never registered; Gate auto-discovery for `Spatie\Activitylog\Models\Activity` only looks in Spatie namespaces (`vendor/laravel/.../Gate.php:692-709`); resource falls back to the plugin's e-mail closure (`rmsramos/.../ActivitylogResource.php:308-318`, `PanelConfig.php:86-89`).

## Could not establish

- Polish label of the revisions action (`lang/vendor/filament-versionable/pl/actions.php` not read).
- Whether a restore changing only non-logged fields writes an activity row (runtime).

## Coverage ledger

- Examined: OfferRevisionsPage, EditOffer, OfferResource:297, OfferPolicy, Offer, OfferEventServiceProvider, UpdateAdvertAfterOfferChange, HasChangesLog, ChangesLogService, ChangesDetailsInfolistSchema, BulkPriceUpdateSyncJob, ChangePriceBulkAction, ActivityPolicy, PanelConfig, configs versionable/activitylog/queue; vendor mansoor, overtrue, spatie LogsActivity, filament CanBeHidden/Resource, laravel Gate, rmsramos, filament-shield provider.
- Matched, not examined: FullListOffer, MyOffers (where bulk action attaches), WoocommerceApiService, UpdateCoreAdvertData, UpdateAdvertAction internals.
