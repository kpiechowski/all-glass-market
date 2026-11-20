# 10 - Test seeding of the Catalogue and the `seed-katalog` command

Status: resolved
Blocked by: 01, 02, 03, 04, 05, 07 (all resolved and merged into `catalog`)

Spec: §2 Domain concepts, §8 Data requirements, §5 Business rules (the data must satisfy them). Decisions: ADR 0008 (several Eurocodes per Glass model), ADR 0011 (the six dictionaries share one table). No new FR: this is developer tooling for checking the panel by hand.

## Why

The Catalogue module (issues 01–08) is built, but the database is empty, so `/admin/glass-models`, vehicles, dictionaries, fitment, search (06) and thumbnails (07) cannot be checked by hand. We need realistic test data that can be wiped and loaded again with one command.

## What

### 1. Verify first, then build only what is missing

Before writing any seeding, check that the module has everything seeding needs, and record the result in your log entry:

- a factory in `Infrastructure/Factories/` for every Catalogue model (`DictionaryValueModel`, `VehicleModel`, `GlassModelModel`, `GlassModelEurocodeModel`, `GlassModelOemCodeModel`, `ThumbnailMappingModel`), extending the Core base factory;
- factory states for what realistic data needs: each of the six dictionary kinds, a withdrawn entry, a main and an additional Eurocode, fitment to vehicles (the `catalogue_glass_model_vehicles` pivot);
- data that satisfies the spec's rules (§5, §8): Eurocode format and uniqueness, exactly one main Eurocode per Glass model, dictionary references valid per kind.

**If something is missing**, add it following `module-persistence` (factory + states) and test it. **If everything is there**, this part is only the seeding itself.

### 2. A Catalogue seeder

- Seeds all Catalogue data: the six dictionaries (realistic values, e.g. producers Pilkington, Saint-Gobain Sekurit, AGC, Fuyao, XYG), vehicles (makes/models/years), Glass models with main + additional Eurocodes and OEM codes, fitment, thumbnail mappings, and a few withdrawn entries so the restore/withdraw paths can be seen.
- The data should be varied enough that the search from 06 returns meaningful results (shared prefixes, several vehicles per model).
- The size is a parameter (see below). The approximate counts are a guide, not a contract:

| Size | Glass models | Vehicles | Dictionary values |
|---|---|---|---|
| `s` | ~20 | ~15 | full fixed set |
| `m` (default) | ~200 | ~80 | full fixed set |
| `l` | ~2000 | ~400 | full fixed set |

### 3. The `seed-katalog` console command

- Signature: `dartisan seed-katalog {size=m}`, where `size` is `s`, `m` or `l`; default `m`. Any other value is refused with a clear message and a non-zero exit code.
- **Fresh:** it first wipes **only** the Catalogue data (every `catalogue_*` table, the fitment pivot included, in an order the foreign keys allow, plus Catalogue media/thumbnail files if any), then seeds from scratch. Tables of other modules (users, audit, settings…) stay untouched.
- Refuses to run in the `production` environment. Asks for confirmation before wiping; `--force` skips the question.
- Prints a short summary: the size used and how many rows of each kind were created.
- Lives in `app/Modules/Catalogue/UserInterface/Console/`, registered by `CatalogueModuleServiceProvider`, following the existing console commands (e.g. `Users/UserInterface/Console`).
- User-facing strings come from the module's translation files (golden rule 6).

### Architecture note (decide and log)

Golden rule 1 says every write is a command, and `deptrac` limits what `UserInterface` may use from `Infrastructure`. Seeding is development tooling, not a business write. The expected solution: the seeding uses the module's factories (the Core base factory opens the write scope), sits where deptrac allows it, and the console command calls it without touching Eloquent directly. If the layer rules force a different placement, choose the smallest one that keeps `composer check` green, and log it under **Deviations**. Do not relax deptrac rules or the read-only model guard.

## Done when

- The verification from part 1 is recorded in the log, and anything that was missing has been added with tests.
- `dartisan seed-katalog` (no argument) seeds the `m` size; `s` and `l` work; an invalid size is refused.
- Running it twice in a row ends with the same counts (the wipe is complete) and with no duplicate-key errors.
- Data from other modules (e.g. users) survives the command.
- Feature tests cover: each size seeds within its range, rerunning is fresh, an invalid size is refused, the production environment is refused, other modules' tables are untouched. Use `s` in tests to keep them fast.
- After `dartisan seed-katalog`, `/admin/glass-models` shows the Glass models with Eurocodes, vehicles and thumbnails.
- `composer check` (pint, deptrac, phpunit) passes.

## Comments

### Worker — 2026-11-12

- **Built:** `dartisan seed-katalog {size=m} {--force}` (`UserInterface/Console/SeedCatalogueConsoleCommand`, registered in `CatalogueModuleServiceProvider`, strings in `lang/pl/console.php`): refuses production and unknown sizes (non-zero exit), asks for confirmation unless `--force`, wipes then seeds, prints per-kind counts. Seeder `Infrastructure/Seeders/CatalogueTestDataSeeder` (deterministic, built on the model factories): six dictionaries (+ withdrawn entries in each), vehicles, Glass models with a main + 0-2 additional Eurocodes, OEM codes, colour/equipment, fitment (1-4 vehicles), some withdrawn Glass models, thumbnail mappings for all positions/colours/equipment. Sizes s/m/l = 20/200/2000 models, 15/80/400 vehicles. Eurocodes `{family}{variant}V` give shared prefixes (RULE-03 format, unique, one main each). Verification of part 1: all six model factories exist, extend Core `ModelFactory`, and already had states for the six dictionary kinds (`of`), withdrawn, main Eurocode (`withMainEurocode`), fitment (`fitting`), colour/equipment, thumbnail mappings. Missing and added: `GlassModelEurocodeModelFactory::forGlassModel/withCode/main` (exercised by the seeder tests).
- **Deviations:** (1) Placement: deptrac forbids UserInterface -> Infrastructure factories and Application -> Infrastructure, so the seeder is `Infrastructure/Seeders/CatalogueTestDataSeeder` implementing a new Domain port `Domain/Contracts/CatalogueTestDataSeeding`, bound in the provider; the command reaches it through a new `Application/ModuleApi/CatalogueTestData::reload()`; size is the new Domain enum `CatalogueSeedSize`. deptrac and the read-only guard untouched. (2) No Catalogue files exist to wipe (thumbnails are SVG drawn from mappings, D-57), so the wipe only deletes the 8 `catalogue_*` tables (explicit FK-safe list `CatalogueTestDataSeeder::TABLES`; a test fails if a new `catalogue_*` table is not in it). (3) Observers still record seeded dictionary values and vehicles in Audit under Actor `factory`; the wipe leaves those entries alone (Audit is another module).
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green (pint, deptrac 0 violations, 1063 tests). The worktree needed the gitignored public/build copied from the main checkout for the Styleguide tests (environmental, not part of the diff).

### Review — 2026-11-12

- **Verdict:** approved after 0 fix rounds
- **Notes:** Placement accepted: seeder `Infrastructure/Seeders/CatalogueTestDataSeeder` behind the Domain port `CatalogueTestDataSeeding`, reached via `Application/ModuleApi/CatalogueTestData::reload()`; deptrac and the read-only guard unchanged. Data is deterministic (s/m/l = 20/200/2000 models, 15/80/400 vehicles; vehicle pool caps at 400). Eurocodes `{1000+n}{variant}V` plus `…1`/`…2` additional ones satisfy RULE-01–03. The wipe list `TABLES` is guarded by a test against new `catalogue_*` tables. Audit rows written by observers during seeding survive the wipe (Audit is another module).
