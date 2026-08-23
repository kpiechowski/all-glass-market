# Refactor Plan — AllGlass V1 → V2

**Goal:** Port V1 monolith at `/home/kp/laravel/projekty/AllGlass/` to V2 DDD+CQRS architecture.
**Rules:** See the architecture skills in `.claude/skills/` (`module-layout` first). Never copy V1 code verbatim — understand it, then rewrite.

> **Archive.** This file and `.ai/modules/` are input for a future grilling session that will rework the port into tickets — including where V1 should be redesigned rather than ported. The five-step workflow below is superseded by the skills above; the phase order and boundary decisions still hold.

---

## Agent workflow — read this first

Every module follows a five-step sequence. Never skip steps.

1. **SCAN** — deep V1 scan → write `.ai/modules/{name}/scan.md`
2. **HEALTH REVIEW** — if `scan.md` has ⚠️ concerns → **ask the user** before planning
3. **PLAN** — design V2 structure → write `.ai/modules/{name}/plan.md`
4. **IMPLEMENT** — read `architecture.md` + `plan.md` only; do NOT reload this file
5. **VERIFY** — run grep suite + `dartisan migrate` + `pint --dirty`; update status below

Templates: `.ai/modules/_template/scan.md` and `.ai/modules/_template/plan.md`

---

## Phase sequence

| Phase | Module | Status | Detail |
|---|---|---|---|
| 1 | Users | ✅ Done | — |
| 1a | Audit | ✅ Done | `.ai/modules/audit/` |
| 1b | Settings | ⬜ Not started | `.ai/modules/settings/` |
| 1c | Submissions | ⬜ Not started | `.ai/modules/submissions/` |
| 2a | Vehicles | ⬜ Not started | `.ai/modules/vehicles/` |
| 2b | Offers | ⬜ Not started | `.ai/modules/offers/` |
| 3a | WooCommerce | ⬜ Not started | `.ai/modules/woocommerce/` |
| 3b | Otomoto | ⬜ Not started | `.ai/modules/otomoto/` |

Do not start Phase 2 until all Phase 1 modules are ✅.
Do not start Phase 3 until `OfferUpdatedEvent`, `OfferDeletedEvent`, `OfferSoldEvent`, `OfferArchivedEvent` exist and fire.

**Commit discipline:** Each module is its own branch + PR. Never mix module work.

---

## Module boundary decisions

| Decision | Rationale |
|---|---|
| Otomoto and WooCommerce are `Integrations/`, not `Modules/` | External platform dependency; removal must not touch domain |
| Vehicles (Car, Eurocode) is its own module | Eurocodes and cars exist independently of any offer |
| Reservations and Sales live inside Offers | No existence outside an offer |
| OfferInquiry lives in Offers | An inquiry is an offer lifecycle event |
| ChangesLog → Audit module | Cross-cutting concern; any module can use it |
| Settings is a single flat module | Simple key-value config, no complex domain logic |
| Submissions is its own module | Unrelated to offers; clean boundary |

---

## Common porting pitfalls

| Pitfall | What to do instead |
|---|---|
| Copying V1 Filament resource directly without removing service/repo calls | Filament pages dispatch command bus only |
| Moving V1 enums to `Domain/Enums/` but leaving old import paths in models | Grep all usages, update all imports |
| Calling `app(SomeService::class)` inside model `booted()` | Use Observer or Domain Event + Listener |
| Creating a Command for every tiny sub-step | Commands map to user intents only; sub-steps stay in Handler |
| Registering Filament resources manually in `AdminPanelProvider` | Auto-discovery handles it |
| Copying V1 `static::updated(fn...)` hooks into V2 models | Use Observer in `Domain/Observers/` |
| Forgetting `UserInterface/resources/lang/pl/resource.php` | Every module needs translation files |
