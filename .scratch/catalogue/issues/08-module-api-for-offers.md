# 08 - Catalogue's module interface for Offers

Status: resolved
Blocked by: 06, 07

Spec: FR-21, FR-22, FR-23, §9 (Offers), §10. Decisions: D-24, D-48, D-49, D-50, D-51, D-52, D-57. ADR 0008.

## What

- **Glass model summary** for one or many ids: main Eurocode, all Eurocodes, OEM codes, position, colour (or none), equipment and fitted vehicles as names, withdrawn flag, thumbnail and pictograms.
- **Candidate search** with the behaviour built in 06, returning candidates with their match label, and the matching model ids for filtering Offer lists.
- **Dictionary lists** of producers, positions and colours for picking and filtering; withdrawn values not offered, but their names readable by id.
- Check that the events of 01, 03, 04 and 05 are public and documented for listeners (integrations, Offers). Catalogue still knows nothing of Offers.

## Done when

- Each method has tests through the module interface only; `composer check` (deptrac included) passes.

### Worker — 2026-11-11
- **Built:** ModuleApi `GlassModelSummaries` (`of`, `ofMany`, FR-21, six reads for any number of ids, plain `GlassModelSummary` DTO with `Thumbnail`), `GlassModelCandidates` (`search`, `matchingIds`, FR-22, wraps 06's port, returns `Candidate` DTOs), `CatalogueDictionaries` (`producers`, `positions`, `colours` active only; `namesOf` reads withdrawn too, FR-23). Domain ports `GlassModelFactsReader`, `DictionaryReader` with Eloquent adapters bound in the provider. Public events (Domain/Events, id + occurredAt) documented in the `GlassModelSummaries` docblock; Catalogue knows nothing of Offers.
- **Deviations:** the dictionary lists return the Domain contract DTOs `DictionaryEntry` and `Candidate` directly (plain readonly values, like 06's `Candidate`) instead of duplicating them in ModuleApi. `ofMany` skips unknown ids; `of` returns null for one.
- **Seams left:** ticket 09 (Offers) consumes these classes and the events.
- **Open questions:** none
- **Check:** composer check green (1050 tests)

### Worker — fix 1
- **Built:** `matchingIds` with an unsearchable text and at least one filter (position, colour, make) now returns every model passing the filters and the withdrawn rule (FR-22, spec section 9); no text and no filter still returns []. Rule lives in `EloquentCandidateSearch::matchingIds` with `CandidateQuery::hasFilter()`; `search()` unchanged. Tests in `GlassModelCandidatesTest`.
- **Deviations:** none
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-11
- **Verdict:** approved after 1 fix round (fresh worker): `matchingIds` returned nothing for a filter-only query, so Offers could not filter its lists by position or colour alone (§9). Now an unsearchable text with a filter returns every model passing the filters; `search()` unchanged.
- **Notes:** ModuleApi: `GlassModelSummaries` (`of`, `ofMany`; constant query count), `GlassModelCandidates` (`search`, `matchingIds`), `CatalogueDictionaries` (`producers`, `positions`, `colours` active only; `namesOf` reads withdrawn names), `GlassModelThumbnails` from 07. New Domain ports `GlassModelFactsReader`, `DictionaryReader` with Eloquent adapters. Public events documented in the `GlassModelSummaries` docblock. Accepted deviations: `Candidate` and `DictionaryEntry` are Domain contract DTOs returned as they are (Domain/Contracts is shared); `ofMany` skips unknown ids, `of` returns null.
