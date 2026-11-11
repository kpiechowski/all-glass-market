# Roadmap: modules of AllGlass Market V2

<!-- Moved out of docs/archive/README.md and docs/archive/v1-port/refactor-plan.md on 2026-11-08 and revised with the user.
     This is the live order of work. Each module runs: module-discovery → agreed functional-spec.md → issues → delivery → admin-ui check. -->

- **Updated:** 2026-11-11
- **How a module is built:**
  1. `module-discovery` (V1 mapping, grilling with the user);
  2. `.scratch/<module>/functional-spec.md` agreed;
  3. issues in `.scratch/<module>/issues/`;
  4. delivery, one worker at a time;
  5. screens follow `admin-ui-design`.
- No V2 code for a module before its spec exists (CLAUDE.md).

## Order

| # | Module | State | Next step |
|---|---|---|---|
| 1 | Users | done (ports & adapters, retrofitted to admin-ui) | — |
| 2 | Audit | done (incl. "Historia i logi", D-24a) | — |
| 3 | Preferences | done (issue admin-ui 13, ADR 0006) | grows with real needs |
| 4 | Settings | decided 2026-11-08: **no module for now** (ADR 0007; map in `.scratch/settings/decision.md`) | — (revisit when the ADR 0007 signals appear) |
| 5 | **Catalogue** (was "Vehicles"; D-23) | **spec agreed 2026-11-11** — `.scratch/catalogue/functional-spec.md` (D-29–D-66), issues 01–09 in `.scratch/catalogue/issues/` (09 waits for Offers). Data migration: `.scratch/data-migration/catalogue/`. | **Delivery** (`/module-delivery`, issues 01–08). |
| 6 | Offers | discovery done 2026-11-09 (`.scratch/offers/discovery/`, r01–r06). Holds Offer, Approval, Reservation, Sale, Inquiry (D-19–D-22); refers to Catalogue through its `ModuleApi` (D-24). | Module grilling **after Catalogue delivery** (Catalogue D-66) → `functional-spec.md`; then Catalogue issue 09 is triaged. |
| 6a | Domain grilling: Glass model vs Offer vs catalogue | **done 2026-11-09** — `.scratch/domain/glass-offer-model.md`, GLOSSARY, ADR 0008–0010 | — |
| 7 | ~~Submissions~~ | **dropped** (D-20): the approval queue is the Offers waiting for approval; what a seller changed comes from the Change history | — |
| 8 | WooCommerce | integration (`app/Integrations/`), scope list only. Publishes only, never imports (ADR 0010); channel mappings live in the integration (D-26). | after Offers |
| 9 | ~~Otomoto~~ → **OLX** | Otomoto dropped from V2 (2026-11-08); OLX Adverts replace it | at the very end |
| — | Data migration | tickets parked in `.scratch/data-migration/` (users, audit). From the domain grilling: an admin review list before migration (parameter conflicts within a eurocode, D-09; suffix-different eurocode pairs, D-13), alternative eurocodes joined into Glass models (D-13), the 16 offers with quantity > 1 split (D-02), `car_offer` folded into Glass model fitment (D-15). | scheduled by the user |

## Decisions carried over (still valid)

- Otomoto (now OLX) and WooCommerce are `app/Integrations/`, not modules; removing an integration never touches the domain.
- ~~Vehicles (Car, Eurocode) is its own module~~ — replaced 2026-11-09 by D-23: one Catalogue module (Glass models, Eurocodes, vehicles, dictionaries); vehicles split out only when they get a life of their own (VIN provider, reference vehicle tree).
- V2 DB schemas need not match V1 1:1, but V1 data must be migratable into them.
- Policies live in the module they concern. Packages for offers (`spatie/medialibrary`, `overtrue/laravel-versionable`, `binarycats/laravel-sku`) are decided in Offers discovery, not assumed.

## Dropped (2026-11-08)

- **`spatie/activitylog`:** not needed. Change history is the Audit module.
- **Filament Shield / `spatie/laravel-permission`:** not needed. Roles and permissions are our own `Authorizer` with `RoleRegistry` in Users (ADR 0004).
- **The archived workflow** (SCAN → PLAN → IMPLEMENT), replaced by `module-discovery` and tickets. The archived scans are input to discovery, never instructions.
