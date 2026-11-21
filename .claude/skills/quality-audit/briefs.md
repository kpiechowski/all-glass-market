# Layer auditor briefs

Each prompt = shared preamble + one layer checklist + the suspects from step 1 in that layer. Checklists name what agents tend to skip; the rules themselves live in the skills, which the auditor reads.

## Shared preamble

```
You are a READ-ONLY code-quality auditor. Edit, create, move or delete no file. Run no composer, phpunit,
artisan, docker, npm or pint, and no git command that writes: another agent works in this repo. Read only.

Repo: <repo root>. Hexagonal / DDD-lite Laravel + Filament modules. Audit whether the code follows the repo's
rules. Business logic is out of scope: judge architecture, rule compliance, shortcuts that leave a real gap,
and code standard.

Scope: app/Modules/<Module>/<Layer>/** and its tests under tests/*/Modules/<Module>/<Layer>.
First read: CLAUDE.md, <skills for this layer>, and the reference implementation in app/Modules/Users/<Layer>.
Before calling something a deviation, grep .scratch/<module>/issues/ for it: a logged, accepted deviation is
reported as "accepted", not as a finding.

Suspects to check first: <from step 1>

Report, under <600-900> words, no code dumps (2-line snippet only when essential):
- Findings: `path:line` — rule broken (skill + section) — consequence: Defect (wrong now) / Gap (next change
  breaks it or a guard is bypassable) / Nit (cosmetic) — confidence high/medium/low.
- One "looks clean" line listing what you checked and found compliant.
Report nothing compliant.
```

## Domain — skills: module-layout, module-persistence, module-cqrs, module-events, module-testing

- Entities: plain PHP (no Eloquent, facades, `now()`, `config()`, `__()`); named constructors + `reconstitute()`; behaviour methods by intent; methods taking every field at once are setters.
- Every invariant inside the entity or a `{X}DomainPolicy`; compare with sibling entities: the same kind of rule enforced in one entity and left to a service for another.
- `Domain/Contracts`: only interfaces another module implements, with their types. Grep implementers and callers of each; flag internal read ports, DTO classes, test tooling.
- Read ports in `Repositories/` return `ReadModels/`; no query technique (LIKE, wildcards) in Domain; a normalisation rule written twice instead of the value object's method.
- Exceptions extend Core `DomainException`, keys exist in `lang/pl/exceptions.php`, no `__()`.
- Enum colours follow admin-ui-design "Colour = state". Events `final readonly`, past tense, ids and values.
- Tests: each behaviour and each refusal, asserting the `translationKey`; each policy branch; each VO rejected format.

## Application — skills: module-cqrs, module-layout, module-events, module-testing

- Commands: `final readonly`, primitives + one `Actor`.
- Handlers: authorize first; depend on ports only; every `if` that throws a refusal (also inside `Application/Services/`) belongs in entity or policy; `Enum::from()` on input instead of `tryFrom()` + domain exception; state change without the event the spec or a sibling handler implies (e.g. Withdraw emits, Restore does not).
- `ModuleApi`: grep callers; flag classes called from inside their own module (except providers) or by no other module, writes that skip a command, test tooling, returns of entities, models, read models or markup.
- Services: called only from handlers, shared by more than one; presentation work (HTML, SVG, CSS classes) in Application.
- Tests through the real bus: happy path, every refusal with its key, unauthorized actor, event.

## Infrastructure — skills: module-persistence, module-events, module-layout, module-testing

- Every write outside the adapter: `save/update/delete/create`, `DB::table()`, raw `sync()/attach()/detach()`, `query()->update()`; seeders and console paths included.
- Models: `ReadOnlyModel`, no scopes, no rules; each has a factory. Factories rebuilding keys by hand instead of the value object.
- Mappers: translation only, no `load()`; null-unsafe access to required rows.
- Adapters beside repositories: which port, naming (`Eloquent{X}`), nothing returns a Builder or model past Infrastructure.
- Audit: which models have an observer and record type, and which siblings do not; audited many-to-many synced through `syncRelation()`.
- Migrations: layer-5 rules backed by constraints, indexes for filtered and sorted columns.
- Cross-check list for the UI auditor: display reads in UserInterface that skip `{X}Queries` (`path:line` only).
- Tests: mapper round-trip, include and exclude for every port and `{X}Queries` method, observer tests.

## UserInterface — skills: filament-module-wiring, admin-ui-design (+ templates, variants.md), module-layout, module-testing

- Write path first: every create, edit, delete, custom, bulk and relation-manager action sends a command via `CommandSender` with `CurrentActor::get()`; no bare Filament default action, inline-editable column, saving `->relationship()` or direct `FileUpload` save.
- Building command arguments from `$data`: optional fields reach the command as `null` (typos like `(['x'] ?? null)`).
- `visible()`: `CurrentActor::can()` + a domain policy for every condition on record fields.
- Reads from `{X}Queries`; own `ModuleApi` used as a back door; entities or repositories used.
- Class split table, `Support/` contents, relation manager placement, `configure()` as an outline.
- admin-ui-design "Before you finish" list and the named template's file, item by item.
- Translations: no hard-coded strings; spot-check keys exist; lang file names per the skill table.
- Unescaped output (`{!! !!}`) of data from another module.
- Tests: Livewire create (also with optional fields empty), edit, delete, every custom action; refusals assert the notification title.
