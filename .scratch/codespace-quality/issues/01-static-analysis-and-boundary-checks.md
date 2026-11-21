# 01 - Static analysis and boundary checks in composer check

Status: resolved

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

### Worker — 2026-11-21
- **Built:** Larastan `^3.14` (dev) installed with `composer require --dev` (no errors). `phpstan.neon` (level 5, `app/`, cache in `storage/phpstan`, ignored in `.gitignore`), `phpstan-baseline.neon` (361 errors; the `['colour_id'] === null` strict-comparison error in `CreateGlassModel` is in it, not fixed). `composer check` runs phpstan after deptrac; `tests/check-module.sh` runs it on the given modules. Deptrac: `DomainValueObject` layer split out of `Domain` (`Domain/ValueObjects/`); UI, Application, Infrastructure, Wiring and Panel may use it; UI still cannot use entities or repositories. Scanner rule: a class under `Application/ModuleApi/` imported from its own module is a violation, except from service providers (`*ServiceProvider.php`, `Application/Providers/`) and from ModuleApi classes themselves; two new scanner tests (red case + provider exception) written first. `module-testing` "Running" and the composer script descriptions name phpstan.
- **PHPStan times** (container, parallel, level 5): full cold 10.7 s, full warm 2.6 s; one module (`app/Modules/Catalogue`) cold 9.2 s, warm 2.6 s. Cold = `storage/phpstan` removed; module run is cold in the same cache, so the module saving is small, since PHPStan still has to parse the whole app for type info.
- **Deviations:** the module-boundaries baseline holds 45 entries over Audit, Catalogue, Offers, Preferences and Users (not only the Catalogue ones the issue listed): the rule also catches those modules' own use of their ModuleApi. Grouped by module with `// Module` comments. The first `--generate-baseline` run ended with an internal PHPStan error (child process exit 1, stack trace in ForkedProcess), the second identical run succeeded; not reproduced since.
- **Unmapped:** `phpstan.neon` / `phpstan-baseline.neon` at repo root (config files; followed `deptrac.yaml` + `deptrac-baseline.yaml` pattern). Scanner exemption path rules live in `tests/Architecture/ModuleBoundaryScanner.php` next to the existing public-surface list.
- **Seams left:** issue 12 fixes the CreateGlassModel error; later tickets remove baseline lines (phpstan and module-boundaries) one by one.
- **Open questions:** none. Note: the first full check had 4 failures only because the fresh worktree had no `public/build` (gitignored Vite output); I copied it from the main tree and it passed.
- **Check:** composer check green (1697 passed).

### Review — 2026-11-21
- **Verdict:** approved after 0 fix rounds
- **Notes:** manager re-ran the full `composer check` (green, 1697 passed, phpstan `[OK]`) and `composer check:module catalogue` (green, phpstan on module paths with the shared baseline reports no unmatched entries). The module-boundaries baseline covers Audit, Catalogue, Offers, Preferences and Users (45 entries): the rule from `module-layout` ("the module itself never calls its ModuleApi; providers wire registries") applies to every module, and the issue says existing violations go to the baseline. Several are registries the owner reads (Audit `AuditableRecords`, Users `RoleRegistry`) — whether those stay exempt is a user decision. PHPStan baseline: 361 errors (296 entries) incl. the `CreateGlassModel` `colour_id` one that issue 12 removes.
