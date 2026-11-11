# 06 - Glass model cards and the candidate search

Status: resolved
Blocked by: 04, 05

Spec: FR-04 (without the thumbnail, 07, and without the "N offers" link, 09), FR-05, WF-06 (search behaviour), §9 "Candidate search", §11 edge cases on codes and OEM. Decisions: D-14, D-30, D-41, D-49, D-53.

## What

- The Glass model list becomes **full-width cards**: main Eurocode, the other Eurocodes, OEM codes, position, colour, equipment, vehicle count, so results compare side by side (load `admin-ui-design`).
- **Search** by a fragment of any Eurocode, OEM code (RULE-04 comparison) or vehicle name/fields; filters position, colour, vehicle make; withdrawn models only on request.
- **Results order:** Eurocode hits, then OEM hits, then vehicle hits; each card says what it matched; a model matching by several routes appears once, under its best route. Choose minimum fragment length and result limit (V1: 3 characters, 20 results) and log the choice.
- Build the search once, so ticket 08 exposes the same behaviour to Offers.

## Done when

- FR-04 (within the limits above) and FR-05 behave as specified; the ordering, match labels, dedup and OEM comparison have tests; `composer check` passes.

### Worker — 2026-11-11
- **Built:** FR-22/D-49 candidate search once, no Filament: port `Domain/Contracts/CandidateSearch` (`search()` limited, `matchingIds()` unlimited for Offers' filters), DTOs `CandidateQuery` (text, position/colour/make filters, includeWithdrawn, limit; reads the text per route) and `Candidate` (model id, best `MatchRoute`, matched value), enum `MatchRoute` (Eurocode, OEM, Vehicle: order is result order), adapter `Infrastructure/Persistence/EloquentCandidateSearch` bound in the provider. Routes run in order, each skipping models already found (one result per model, best route) and asking only for what the limit still allows. OEM compared by RULE-04 (`OemCode::keyOf`, added). Vehicle route: every typed word must match name, model, generation, ARGIC code or make of one vehicle. FR-04/FR-05/D-53: the Glass model list is a full-width card list (`GlassModelCardColumn` + Blade + `components/glass-card.css`): main Eurocode, further Eurocodes, OEM codes, position, colour, equipment, vehicle count, state badge, and a "Eurokod/Kod OEM/Pojazd: value" label when searched. Table search is the candidate search (`GlassModelQueries::candidates()`); filters position, colour, make (`GlassModelsTableFilters`) and a "Pokaż także wycofane" toggle; withdrawn hidden unless on. Tests: unit (`CandidateQuery`, `OemCode::keyOf`), `EloquentCandidateSearchTest` (ordering, labels, dedup, OEM comparison, filters, withdrawn, limit), `GlassModelQueriesTest`, `ListGlassModelsTest`.
- **Deviations:** (1) Chosen and logged: minimum fragment 3 characters (trimmed text; each route also needs 3 after its normalisation), limit 20 across all routes (V1 values); a text under 3 characters finds nothing (an empty search box lists plainly). (2) `%` and `_` are stripped from search text (no LIKE escaping, portable across SQLite/MySQL). (3) Within a route models come in id order (no exact-match-first). (4) Withdrawn models now hidden from the list by default (FR-05); the existing list test now switches the toggle on. (5) UI cannot import Domain contracts (deptrac), so `GlassModelQueries::candidates()` takes plain arguments and builds `CandidateQuery` itself; it takes an optional base builder so the table's filters stay applied. (6) Single card column replaces the old columns and the Core `RecordCardColumn` (the card is responsive itself); the column header shows the plural label. (7) `GlassModelQueries` now has a constructor (the port); `GlassModelQueriesTest` uses the container. (8) `public/build` rebuilt with `npm run build` (git-ignored).
- **Seams left:** `GlassModelCardColumn::thumbnail(Closure)` (07) and `::offersLink(Closure)` (09), drawn only when they return something. Ticket 08 wraps `CandidateSearch` in a ModuleApi class (`search`, `matchingIds`); `MatchRoute` values are stable for Offers.
- **Open questions:** none
- **Check:** composer check green (1005 passed)

### Review — 2026-11-11
- **Verdict:** approved after 0 fix rounds
- **Notes:** the candidate search is a port `Domain/Contracts/CandidateSearch` (+ `CandidateQuery`, `Candidate`, `Enums/MatchRoute`) with the adapter `EloquentCandidateSearch`; 08 wraps `search()` and `matchingIds()`. Chosen: minimum fragment 3 characters, limit 20 across all routes (V1 values); `%` and `_` stripped. The admin list's search therefore shows at most 20 models too; widen through `CandidateQuery::$limit` if the admin needs more. Card slots `->thumbnail()` (07) and `->offersLink()` (09). New CSS `resources/css/admin/components/glass-card.css`: the main tree needs `npm run build`. Accepted deviations: `GlassModelQueries::candidates()` takes plain arguments (UI may not import Domain contracts); one card column replaces the old columns.
