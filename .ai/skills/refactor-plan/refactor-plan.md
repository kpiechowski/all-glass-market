# Refactor Plan — AllGlass V1 → V2

This document is the **source of truth for the refactor sequence**. It describes what modules to build, in what order, what V1 code maps to each module, and what "done" means per milestone. Future agents should read this before touching any module.

Architecture rules live in `architecture.md`. This file is about the journey, not the rules.

---

## V1 source reference

Old project: `/home/kp/laravel/projekty/AllGlass/`

Never copy V1 code verbatim. Use it to understand:

- What the model fields and relationships are
- What the business rules are (e.g. which statuses allow editing)
- What events exist and what they trigger
- What the Filament UI needs to expose

Always rewrite for the V2 architecture. V1 is a reference, not a template.

Remember to Scan and find all functionalities related to given module across whole codebase - they might be everywhere due to poor app management

---

## Decision log — module boundaries

These decisions were made before scaffolding began. Do not revisit them without reason.

| Decision                                                                | Rationale                                                                          |
| ----------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| Otomoto and WooCommerce are `Integrations/`, not `Modules/`             | They depend on external platforms; removing them must not touch domain modules     |
| Vehicles (Car, Eurocode) is its own module, not nested inside Offers    | Eurocodes and cars exist independently of any offer                                |
| Reservations and Sales are sub-concerns of Offers, not separate modules | They have no meaningful existence outside an offer; they live in `Modules/Offers/` |
| OfferInquiry lives in Offers module                                     | An inquiry is a lifecycle event of an offer, not a standalone domain object        |
| ChangesLog becomes an Audit module                                      | It is a cross-cutting concern that any module can use                              |
| Settings stays a single flat module                                     | No complex domain logic; just key-value config records                             |
| Submissions (contact form) is its own small module                      | Unrelated to offers; cleaner boundary                                              |

---

## Module map

These are the `Modules/` to scaffold. Each has its own V1 source mapping.

### 1. `Users`

**Status:** ✅ Complete

**V1 source:**

- `app/Models/User.php`
- `app/Filament/Resources/UserResource.php`
- `app/Actions/Fortify/*`, `app/Actions/Jetstream/*` → replaced by Commands
- `app/Livewire/Users/ListUsersNotifications.php`

**V1 problems to fix:**

- User model had no repository — add `UserRepository`
- `EditProfile` page was a standalone Filament page without command bus, include
- `ListUsersNotifications` Livewire component — deferred to Audit module (admin activity log, cross-cutting concern)
- `UserPolicy` — blocked; requires spatie/laravel-permission which is not installed in V2

**Done when:**

- [x] User model in `Domain/Models/`
- [x] `CreateUserCommand`, `UpdateUserCommand`, `DeleteUserCommand` with handlers
- [x] `UserRepository` extending `ModelRepository`
- [x] Filament `UserResource` in `UserInterface/Filament/`
- [x] Filament pages use `HasCommandBus`, not direct model calls
- [x] User observer registered for audit events
- [x] `EditProfile` self-service page dispatches `UpdateUserCommand` via command bus

---

### 2. `Vehicles`

**Status:** ⬜ Not started

**V1 source:**

- `app/Models/Car.php`, `app/Models/CarModels/CarBrand.php`
- `app/Models/Eurocode.php`, `app/Models/EurocodeOffer.php` ← pivot, stays as Eloquent pivot only
- `app/Filament/Resources/CarResource.php`
- `app/Console/Commands/FetchDonorDataCommand.php` (Otomoto donor car fetch — belongs to Otomoto integration, not here)
- `app/Console/Commands/GenerateEurocodesAndRelationFromOffers.php` → `Application/Commands/`

**Domain objects:**

- `Car`, `CarBrand`, `Eurocode`
- `EurocodeOffer` is a pivot — keep as Eloquent `belongsToMany`, no standalone model needed unless it gets its own attributes

**Done when:**

- [ ] `Car`, `CarBrand`, `Eurocode` models in `Domain/Models/`
- [ ] `CarRepository`, `EurocodeRepository`
- [ ] Commands: `CreateCar`, `UpdateCar`, `DeleteCar`, `CreateEurocode`, `LinkEurocodeToCar`
- [ ] Filament `CarResource`, `EurocodeResource` in `UserInterface/Filament/`
- [ ] All console command logic extracted to Application Commands (not `Console/Commands/`)

---

### 3. `Offers`

**Status:** ⬜ Not started — highest complexity, tackle after Vehicles

**V1 source:**

- `app/Models/Offer.php` (658 lines — requires significant cleanup)
- `app/Models/OfferModels/{OfferColor, OfferProducer, OfferType, OfferExtras}.php`
- `app/Models/OfferEquipment.php`, `app/Models/ShippingMethod.php`
- `app/Models/OfferReservation.php`, `app/Models/OfferSale.php`, `app/Models/OfferInquiry.php`
- `app/Filament/Clusters/Offers/*` (entire Offers cluster)
- `app/Filament/Actions/Journal/*` (status transition actions)
- `app/Services/Offer/*` (OfferUpdateService, OfferPriceService, OfferEurocodeRelationService, events)
- `app/Jobs/Offer/*`
- `app/Filament/Enums/OfferStatus.php`, `OfferUsageStatus.php` ← must move to Domain/Enums

**V1 problems to fix — critical:**

1. `Offer::booted()` calls `WoocommerceApiService` directly → remove entirely; WooCommerce integration listens to `OfferUpdatedEvent`
2. `Offer::getReplicateTableAction()` — Filament UI method inside a Domain model → move to Filament resource
3. `canCreateOtomotoAdvert()`, `canBeWooSynch()`, `canBeEdited()` on model → extract to `Domain/Services/OfferValidationService`
4. Static aggregate queries (`getArchived()`, `getPendings()`) on model → move to `OfferRepository`
5. Enums in `App\Filament\Enums` → move to `App\Modules\Offers\Domain\Enums`
6. `OfferEventServiceProvider` is in `Services/Offer/` → move to `Application/Providers/`
7. Duplicate policies in `App\Policies\` and `App\Policies\OfferPolicies\` → consolidate to `Application/Policies/`

**Domain objects:**

- `Offer` (core), `OfferColor`, `OfferProducer`, `OfferType`, `OfferEquipment`, `ShippingMethod`
- `OfferReservation`, `OfferSale`, `OfferInquiry` (sub-entities)
- Enums: `OfferStatus`, `OfferUsageStatus`, `ResourceStatus`

**Commands (one per user intent / state transition):**

- `CreateOffer`, `UpdateOffer`, `DeleteOffer`
- `AcceptOffer`, `RejectOffer`, `ArchiveOffer`
- `ReserveOffer`, `UnreserveOffer`
- `MarkOfferSold`, `MarkOfferExpired`
- `ReplicateOffer`
- `CreateOfferInquiry`, `AcceptInquiry`, `RejectInquiry`
- `UpdateOfferPrice` (bulk price change)
- `LinkEurocodeToOffer`, `ReorderOfferEurocodes`

**Done when:**

- [ ] Domain models clean — no Filament imports, no WooCommerce/Otomoto references
- [ ] All enums in `Domain/Enums/`
- [ ] `OfferRepository` holds all query logic
- [ ] `OfferValidationService` holds all can\* checks
- [ ] All state transitions are Commands with Handlers
- [ ] `OfferEventServiceProvider` in `Application/Providers/`
- [ ] Domain events: `OfferCreated`, `OfferUpdated`, `OfferDeleted`, `OfferSold`, `OfferArchived`
- [ ] Filament cluster fully in `UserInterface/Filament/`
- [ ] No Filament page calls a repository directly
- [ ] Policies consolidated and registered

---

### 4. `Audit` (was: ChangesLog)

**Status:** ⬜ Not started

**V1 source:**

- `app/Modules/ChangesLog/*` (the only V1 module with a proper boundary)
- `app/Models/UserJournal.php`, `app/Models/ChangesLog.php`
- `app/Filament/Resources/UserJournalResource.php`

**Notes:**

- V1 had `HasChangesLog` trait and `LoggableModel` contract on the `Offer` model — good pattern, keep it
- `UserJournal` tracks per-user activity (admin-facing); `ChangesLog` tracks field-level diffs
- This module is consumed by other modules via traits, not commands — design accordingly

**Done when:**

- [ ] `ChangesLog`, `UserJournal` models in `Domain/Models/`
- [ ] `HasChangesLog` trait stays usable by other domain models
- [ ] `LoggableModel` contract in `Domain/Contracts/`
- [ ] `AuditRepository`
- [ ] Filament resources for viewing logs in `UserInterface/Filament/`
- [ ] Register a global exception listener in `bootstrap/app.php` → `withExceptions()` that routes caught exceptions to the audit store (replaces page-level try/catch with centralized logging while keeping Filament Notifications in pages)

---

### 5. `Settings`

**Status:** ⬜ Not started

**V1 source:**

- `app/Models/Setting.php`
- `app/Filament/Clusters/Settings/*`
- `app/Filament/Utils/SettingsSchemaBuilder.php`

**Notes:** Low complexity. Uses `spatie/laravel-settings` pattern. No CQRS needed for simple key-value reads; add Commands only for admin update actions.

**Done when:**

- [ ] `Setting` model or Spatie settings class in `Domain/`
- [ ] Filament settings pages in `UserInterface/Filament/`

---

### 6. `Submissions`

**Status:** ⬜ Not started

**V1 source:**

- `app/Models/Submission.php`
- `app/Filament/Resources/SubmissionResource.php`

**Notes:** Simple CRUD. One `CreateSubmission` command (triggered from public form). Admin views only.

**Done when:**

- [ ] `Submission` model, repository, `CreateSubmission` command
- [ ] Filament resource

---

## Integration map

These live under `app/Integrations/`. Scaffold with:

```bash
php artisan make:integration {Name}
```

### 1. `Otomoto`

**Status:** ⬜ Not started

**V1 source:**

- `app/Services/OtomotoService/*` (entire directory — API, Actions, Jobs, Listeners, Services, DTOs)
- `app/Models/OtomotoOffer.php`, `app/Models/OtomotoData.php`
- `app/Models/OtomotoModels/{DonorCar, DonorGeneration, DonorMake, DonorModel}.php`
- `app/Filament/Resources/OtomotoOfferResource.php` and all sub-components
- `app/Filament/Pages/UsersOtomotoOffers.php`
- `app/Console/Commands/FetchAllDonorGenerationsCommand.php`, `FetchDonorDataCommand.php`

**This integration:**

- Listens to: `OfferUpdatedEvent`, `OfferDeletedEvent`, `OfferArchivedEvent`, `OfferSoldEvent` → deactivates or updates the advert
- Has its own Commands: `CreateOtomotoAdvert`, `PublishAdvert`, `DeactivateAdvert`, `UpdateAdvert`, `GenerateAdvertDescription`
- Has its own models: `OtomotoOffer`, `DonorCar`, `DonorMake`, `DonorModel`, `DonorGeneration`
- Has full Filament UI with tabs, bulk actions, stats widget

**Dependency rule:** `Integrations\Otomoto` may import `Modules\Offers\Domain\Models\Offer`. `Modules\Offers` must never import from `Integrations\Otomoto`.

**Done when:**

- [ ] Domain models in `Domain/Models/`
- [ ] `OtomotoApiClient` in `Infrastructure/Api/`
- [ ] `OtomotoMapper` maps `Offer` → Otomoto API payload
- [ ] Listeners for Offer domain events in `Application/Listeners/`
- [ ] Commands for all advert lifecycle operations
- [ ] Donor car fetch jobs in `Application/Jobs/`
- [ ] Filament `OtomotoOfferResource` in `UserInterface/Filament/`
- [ ] Zero imports from `Modules\Offers` inside Offer domain itself

---

### 2. `WooCommerce`

**Status:** ⬜ Not started

**V1 source:**

- `app/Services/ApiService/*` (WoocommerceApiService, WordpressApiService, Actions, Jobs, DTOs)
- `app/Models/WooCommerceSync.php`, `app/Models/WordpressSync.php`
- `app/Jobs/Offer/BulkPriceUpdateSyncJob.php`, `SynchOemCodeFromWordpressJob.php`

**This integration:**

- Listens to: `OfferUpdatedEvent`, `OfferDeletedEvent` → syncs product to WooCommerce
- Has its own Commands: `SyncOfferToWooCommerce`, `BulkSyncPrices`, `FetchWooProducts`
- Has its own models: `WooCommerceSync`, `WordpressSync`
- V1 anti-pattern to fix: `Offer::booted()` called `WoocommerceApiService` directly → must become a Listener here

**Done when:**

- [ ] `WooCommerceApiClient`, `WordpressApiClient` in `Infrastructure/Api/`
- [ ] `WooProductMapper` in `Domain/Mappers/`
- [ ] Listeners for Offer events
- [ ] `WooCommerceSync` model and repository
- [ ] Fetch jobs for initial data sync
- [ ] No WooCommerce reference in `Modules\Offers`

---

## Refactor sequence

Follow this order. Each phase depends on the previous one being complete.

```
Phase 1 — Foundation (no domain logic yet)
  [1] Users module       ← already scaffolded, complete it
  [1a] Audit module       ← standalone, no cross-module deps
  [1b] Settings module    ← standalone
  [1c] Submissions module ← standalone

Phase 2 — Core domain
  [2a] Vehicles module    ← Cars + Eurocodes, no deps on Offers
  [2b] Offers module      ← depends on Vehicles (Eurocode FK)

Phase 3 — Integrations
  [3a] WooCommerce        ← depends on Offers events
  [3b] Otomoto            ← depends on Offers events + Vehicles (donor cars)
```

Do not start Phase 2 until Phase 1 modules pass their done checklists.
Do not start Phase 3 until `OfferUpdatedEvent`, `OfferDeletedEvent`, `OfferSoldEvent`, `OfferArchivedEvent` exist and fire.

---

## Cross-cutting concerns

These must be decided before or during Phase 1 — they affect every module.

### Policies

V1 has duplicate policies in `App\Policies\` and `App\Policies\OfferPolicies\`. In V2, policies live inside the module they protect:

```
app/Modules/Offers/Application/Policies/OfferPolicy.php
```

Register via `AuthServiceProvider` or, preferably, via `Gate::policy()` inside the module's `ModuleServiceProvider::boot()`.

### Media (Spatie MediaLibrary)

`Offer` uses `InteractsWithMedia`. Keep using it in V2 — it is infrastructure, not domain logic. The conversion definitions stay on the Eloquent model. What moves out is any media-handling code that was buried in Filament actions or model `booted()`.

### Versioning (Overtrue LaravelVersionable)

`Offer` uses `Versionable`. Keep it. The `$versionable` and `$dontVersionable` arrays stay on the model — they are config for the package, not business logic.

### SKU generation (BinaryCats SKU)

Keep `HasSku` on `Offer`. Same reasoning as above.

### Activity log (Spatie Activitylog)

V1 used `LogsActivity` on `Offer`. In V2, prefer `HasChangesLog` trait from the Audit module which gives richer, domain-aware logging. Remove `LogsActivity` from models that have `HasChangesLog`. Keep `LogsActivity` only where Audit module integration is not yet built.

---

## Common porting pitfalls

These mistakes are easy to make when porting V1 code. Agents should check for them after writing any class.

| Pitfall                                                                                                        | What to do instead                                                                         |
| -------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| Copying V1 Filament resource directly into `UserInterface/Filament/` without removing service/repository calls | Filament pages call command bus only — `$this->commandBus->send(...)`                      |
| Moving V1 enums to `Domain/Enums/` but leaving old `App\Filament\Enums` imports in models                      | Find all usages with grep, update all import paths                                         |
| Putting `OfferStatus` logic (color, label) as methods on the Enum                                              | Fine to keep — enum methods that return presentation values are acceptable in Domain enums |
| Calling `app(SomeService::class)` inside a model's `booted()`                                                  | Remove from model; use an Observer or a Domain Event + Listener instead                    |
| Creating a Command for every tiny operation                                                                    | Commands are for user intents. Internal sub-steps stay in the Handler or a Service         |
| Registering Filament resources in `AdminPanelProvider` manually                                                | Auto-discovery handles it; never add per-module resources to the panel config              |
| Copying V1 `static::updated(fn...)` hooks into V2 models                                                       | Use an Observer (`Domain/Observers/`) registered in the EventServiceProvider               |

---

## Milestone tracker

Update the status column as work progresses. An agent checking whether a module is safe to depend on should look here first.

| Module                    | Status                                | Done checklist                |
| ------------------------- | ------------------------------------- | ----------------------------- |
| Users                     | ✅ Done                               | See Users section above       |
| Vehicles                  | ⬜ Not started                        | See Vehicles section above    |
| Offers                    | ⬜ Not started                        | See Offers section above      |
| Audit                     | ⬜ Not started                        | See Audit section above       |
| Settings                  | ⬜ Not started                        | See Settings section above    |
| Submissions               | ⬜ Not started                        | See Submissions section above |
| Otomoto (integration)     | ⬜ Not started                        | See Otomoto section above     |
| WooCommerce (integration) | ⬜ Not started                        | See WooCommerce section above |

**Status key:**

- ⬜ Not started
- 🔶 In progress
- ✅ Done (all checklist items checked)
- 🚫 Blocked (note why)
