# 01 - Static analysis and boundary checks in composer check

Status: ready-for-agent

Source: quality audit of the Catalogue module, 2026-11-21. Decisions by the user on that date: add Larastan; own UI may
use own Domain value objects; ModuleApi is called only from outside its module (Open Host Service).

Run only when no delivery agent is working in the main tree: this changes `composer.json`, `deptrac.yaml` and the
check scripts, and closes with the full `composer check`.

## What

1. **Larastan** (`larastan/larastan`, dev dependency) with `phpstan.neon` at the root:
   - level 5, paths `app/`, a generated baseline (`phpstan-baseline.neon`) for today's errors so nothing blocks;
   - result cache in `storage/` (ignored by git), so warm runs re-analyse only changed files;
   - `composer check`: add `vendor/bin/phpstan analyse --no-progress` after deptrac;
   - `tests/check-module.sh`: add `vendor/bin/phpstan analyse --no-progress <module paths>` for the given modules;
   - measure and write into the issue log: cold and warm time of the full analysis and of one module.
   The audit's motivating case: `(['colour_id'] ?? null) === null` in `CreateGlassModel.php` (strict comparison always
   false) passed review and tests.
2. **Deptrac:** a `DomainValueObject` layer (`Domain/ValueObjects/`) split out of `Domain` like `DomainPolicy`, and
   `UserInterface` allowed to use it (pure formatting and normalisation, e.g. `Eurocode::normalise()`).
3. **Architecture test** in `tests/Architecture/ModuleBoundaryScanner.php`: a class under `{Module}/Application/ModuleApi/`
   is never imported from the same module, except by the module's service providers (registries are wired there).
   Existing violations go to the baseline with the module named, so they can be removed one by one
   (today: Catalogue `EurocodeSuggestions` and `GlassModelThumbnails` used by Catalogue UI, `CatalogueTestData` used by
   Catalogue console, `DictionaryValueUsages`/`GlassModelUsages` used by Catalogue delete handlers).
4. Update `module-testing` ("Running") and the `composer.json` script descriptions to name phpstan in both checks.

## Done when

- Both checks run phpstan; deptrac allows UI → Domain value objects and still refuses UI → entities and repositories;
  the scanner test has a red case for the new rule.
- Times measured and logged.
- Full `composer check` passes.

## Comments
