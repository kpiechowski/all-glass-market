# Roadmap: modules of AllGlass Market V2

<!-- Moved out of docs/archive/README.md and docs/archive/v1-port/refactor-plan.md on 2026-11-08 and revised with the user.
     This is the live order of work. Each module runs: module-discovery → agreed functional-spec.md → issues → delivery → admin-ui check. -->

- **Updated:** 2026-11-08
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
| 5 | **Vehicles** (working name) | scope list only (`docs/archive/v1-port/modules/vehicles/scan.md`): Car, CarBrand, Eurocode, the Eurocode–Offer pivot | Discovery and grilling, **including the module name** ("Vehicles" vs a catalogue or reference-data name). It exists independently of offers and unblocks Offers. |
| 6 | Offers | scope list only, the largest module. Reservations, Sales and OfferInquiry live here. | discovery after Vehicles |
| 7 | Submissions | V1 scan (`docs/archive/v1-port/modules/submissions/scan.md`) | **After Offers:** in V1 every submission belongs to an offer (`offer_id`, cascade). Grill whether it stays a module or becomes part of Offers (OfferInquiry). |
| 8 | WooCommerce | integration (`app/Integrations/`), scope list only | after Offers |
| 9 | ~~Otomoto~~ → new integration (TBD) | Otomoto dropped from V2 (2026-11-08); a new integration replaces it | at the very end |
| — | Data migration | tickets parked in `.scratch/data-migration/` (users, audit) | scheduled by the user |

## Decisions carried over (still valid)

- Otomoto and WooCommerce are `app/Integrations/`, not modules; removing an integration never touches the domain.
- Vehicles (Car, Eurocode) is its own module, because cars exist independently of offers.
- V2 DB schemas need not match V1 1:1, but V1 data must be migratable into them.
- Policies live in the module they concern. Packages for offers (`spatie/medialibrary`, `overtrue/laravel-versionable`, `binarycats/laravel-sku`) are decided in Offers discovery, not assumed.

## Dropped (2026-11-08)

- **`spatie/activitylog`:** not needed. Change history is the Audit module.
- **Filament Shield / `spatie/laravel-permission`:** not needed. Roles and permissions are our own `Authorizer` with `RoleRegistry` in Users (ADR 0004).
- **The archived workflow** (SCAN → PLAN → IMPLEMENT), replaced by `module-discovery` and tickets. The archived scans are input to discovery, never instructions.
