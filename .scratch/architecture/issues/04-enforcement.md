# 04 - deptrac, test-presence check and composer check

Status: ready-for-agent
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
