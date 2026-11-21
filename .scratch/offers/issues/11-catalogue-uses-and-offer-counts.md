# 11 - Glass models and producers in use; "N ofert" on Glass model cards

Status: resolved
Blocked by: 06

Spec: FR-25 (uses), FR-33, §9 Catalogue (consumer). Catalogue FR-24, D-37. Decisions: D-43, D-48.

## What

- Offers registers with Catalogue's seams (e.g. `GlassModelUsages`, `DictionaryValueUsages`) that a Glass model or a producer is **in use** while any Offer refers to it, Trash included; Catalogue then refuses to delete it (it may be withdrawn). Catalogue keeps knowing nothing of Offers.
- The **"N ofert"** count on Catalogue's Glass model cards: Offers outside the Trash, every status; links to "Wszystkie oferty" filtered by that model (10's filter; until 10, the plain list with the filter parameter). Mechanism through a Catalogue seam, decided at triage with the architecture skills.

## Done when

- Deleting a Glass model or producer used by a trashed Offer is refused; the count excludes the Trash; deptrac shows no Catalogue → Offers dependency; `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** FR-25 uses: Offers registers `OfferGlassModelUsage` and `OfferProducerUsage` (count every Offer, Trash included) with Catalogue's `GlassModelUsages` / `DictionaryValueUsages` from `OffersModuleServiceProvider`; delete is refused, withdraw still works (tests). "N ofert" on the Glass model card: new Catalogue seam `Domain/Contracts/GlassModelLinkProvider` (+ `GlassModelLink`) registered with `Application/ModuleApi/GlassModelLinks` (DTO `GlassModelCardLink` for the UI); Offers implements it in `Infrastructure/Persistence/OfferGlassModelLinks`: one grouped query per page (`OfferQueries::countByGlassModel`), Offers visible to the viewer (seller: own), outside Trash, every status; links to `OfferResource` index with `?filters[glass_model][glass_model_id]=<id>`. The card's existing `offersLink` slot draws it; Catalogue never imports Offers.
- **Deviations:** (1) Seam is a generic link provider, not a bare counter: Offers returns label and URL, so Catalogue holds no Offers wording. (2) The registry answers for a whole page in one call (`ofMany`); the card column memoizes it per table component (one render), so no query per card. (3) 0 offers: no link drawn (the slot's CSS already hides an empty slot; "0 ofert" is noise). (4) The Glass model picker (embedded in Offers' forms) shows no link (`columns(withLinks: false)`), since a card click there marks a choice. (5) The provider lives in Infrastructure and builds the URL from the Filament route name, because deptrac forbids UserInterface from using Domain contracts and Infrastructure from using UserInterface. (6) Link carries `x-on:click.stop` so the row click does not swallow it; not verified in a browser (no browser QA).
- **Seams left:** other modules can add card links through `GlassModelLinkProvider`.
- **Open questions:** none
- **Check:** composer check:module offers,catalogue green (887 tests, deptrac clean)

### Worker — fix 1 — 2026-11-21
- **Built:** "N ofert" label now uses `trans_choice` with Polish plural forms (`{1} oferta|[2,4] oferty|[5,*] ofert`), tested for 1, 3 and 5. Link seam moved: `GlassModelLinkProvider` now sits in Catalogue `Application/ModuleApi/` beside `GlassModelLinks`, with the single DTO `GlassModelCardLink` (the `Domain/Contracts` pair is gone). Offers' implementation moved to `UserInterface/Filament/Resources/Offers/Support/OfferGlassModelLinks`: URL from `OfferResource::getUrl('index', filters)`, viewer through `CurrentActor`; the count stays in `OfferQueries::countByGlassModel`. Provider registered from `OffersModuleServiceProvider`. Docblocks and tests updated (test moved to `tests/Feature/Modules/Offers/UserInterface/Filament/Resources/Offers/Support/`); the one-count-query and seller-scope tests kept.
- **Deviations:** none
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check:module offers,catalogue green (888 tests)

### Review — 2026-11-21
- **Verdict:** approved after 1 fix round. Round 1 changed two things:
  - the label now uses the Polish plural forms;
  - the link provider moved out of Infrastructure. Its contract now sits in Catalogue's `Application/ModuleApi/GlassModelLinkProvider`, with one DTO, `GlassModelCardLink`. The implementation is in Offers' `UserInterface/.../Support/OfferGlassModelLinks`, and it builds its URL with `OfferResource::getUrl`.
- **Notes:**
  - **Uses:** `OfferGlassModelUsage` and `OfferProducerUsage` count every Offer, Trash included. They are registered from `OffersModuleServiceProvider`. Deptrac shows no Catalogue → Offers dependency.
  - **"N ofert":** one grouped count per page, through `OfferQueries::countByGlassModel`. It counts outside the Trash, in every status.
  - **Accepted deviations:**
    - The count covers the Offers the viewer may see. An admin gets the full count, a seller gets their own, consistent with where the link leads. Flagged to the user.
    - Zero draws no link.
    - The picker shows no links.
    - Catalogue's `GlassModelUsagesTest` uses a fresh registry, because the app's registry now holds Offers' usage.
  - **Check:** `composer check:module offers,catalogue` green, 888 tests.
