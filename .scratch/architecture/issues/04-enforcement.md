# 04 - deptrac, test-presence check and composer check

Status: resolved
Blocked by: 02

Turn the rules into failing checks, so agents verify their own work.

## Scope

- Add `deptrac/deptrac` to `require-dev` (approved). Layers by path regex per module: Domain, Application, Infrastructure, UserInterface, plus Core and Integrations. Rules:
  - Domain → Core contracts/value objects only, plus the allowed Filament enum contracts (ADR 0003).
  - Application → Domain.
  - Infrastructure → Domain.
  - UserInterface → Application, `Domain/Policies`, `Domain/Enums`, `Infrastructure/Persistence/Models`, `Infrastructure/Persistence/Queries`; never repositories, mappers or adapters.
  - Module A → module B only through `Application/ModuleApi`, `Domain/Events`, `Domain/Contracts`, `Domain/Enums`, `Infrastructure/Persistence/Models`.
  - Core → no module.
  - UserInterface → never `Illuminate\Support\Facades\DB` or `Illuminate\Database\*` query classes. `ReadOnlyModel` guards Eloquent model and builder writes, but `DB::table()` bypasses it.
- Baseline for the known violations in Users and Audit, removed in tickets 05 and 06.
- Test-presence check (a PHPUnit test or a small script): every `*CommandHandler`, entity, `*DomainPolicy`, `*Mapper`, `*Queries` and `Application/Listeners/*` class has a `{Class}Test.php` under the mirrored `tests/` path.
- `pint --test` fails today on `app/Providers/Filament/AdminPanelProvider.php` and `config/auth.php` (pre-existing). Fix them so `composer check` starts green.
- `composer check` script: `pint --test`, deptrac, test-presence, `phpunit`.

## Done when

`composer check` passes on the current tree. A deliberate violation (UI importing a repository, a handler without a test) fails it, and each is shown in the ticket comments.

## Answer

Implemented by a subagent, verified 2026-10-31. `composer check` = pint --test → deptrac → phpunit (test presence and module boundaries run inside phpunit, `tests/Unit/Architecture`).

- Deptrac layers are per module by path regex. UserInterface may use only `ApplicationEntry` (Commands, Queries, ModuleApi), and never the `DB` facade or raw query classes. `Illuminate\Http\Client` is allowed in Infrastructure only.
- The cross-module rule is a PHPUnit scanner (`tests/Architecture/ModuleBoundaryScanner.php`). Deptrac cannot express "own module free, other module only through allowed parts".
- Baselines: `deptrac-baseline.yaml` (35), `tests/Architecture/baselines/{module-boundaries,test-presence}.php`. A stale baseline entry fails its test. Core's `CreateRootCommand` importing Users is baselined too and must go when it moves.
- Verified probes: UI importing a repository fails, UI importing an `Application/Services` class fails, UI using `DB::table()` fails, Domain importing a Filament component fails, Domain using the HTTP client fails, a handler without a test fails.
