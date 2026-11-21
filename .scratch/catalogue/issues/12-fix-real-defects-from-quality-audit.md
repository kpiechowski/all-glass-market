# 12 - Fix real defects found by the quality audit

Status: resolved

Source: quality audit of the Catalogue module, 2026-11-21. Only defects that produce a wrong result or a crash; style findings are out of scope.

## What

1. **Glass model without a colour cannot be created from the Create page.**
   `UserInterface/Filament/Resources/GlassModels/Pages/CreateGlassModel.php:29` reads
   `(['colour_id'] ?? null) === null` — `$data` is missing, so the left side is an array literal and never null.
   With the colour left empty the command carries `colourId: 0`, `GlassModelChoices` finds no value 0 and refuses
   with "value does not exist". `CreateGlassModelInPlaceAction` builds the same argument correctly; compare with it.

2. **Unknown enum values in commands crash with a raw `ValueError` (HTTP 500) instead of a refusal.**
   - `Application/Commands/SaveThumbnailSettings/SaveThumbnailSettingsCommandHandler.php:41,53` — `GlassShape::from()`, `Pictogram::from()`
   - `Application/Commands/CreateDictionaryValue/CreateDictionaryValueCommandHandler.php:28` — `Dictionary::from()`
   The handler guards the system (golden rule 4): an unknown value must end in a `DomainException` with a
   `catalogue::exceptions.*` key, shown as a danger notification.

3. **`GlassModelMapper` can fail with a TypeError on an incomplete row.**
   `Infrastructure/Persistence/Mappers/GlassModelMapper.php:27-38`: `firstWhere('is_main', true)->code` is null-unsafe,
   and `OemCode::tryFrom()` may put `null` into a typed list. A Glass model with no main Eurocode or with an invalid
   stored OEM code should fail with a clear exception naming the record id, not a TypeError.

4. **The glass thumbnail crosses into Offers as ready-made HTML, printed unescaped.**
   `Application/Services/ThumbnailSvg` builds an `<svg class="glass-thumbnail__shape">…` string in the Application
   layer; `ModuleApi/GlassModelThumbnails` puts it into the `Thumbnail` DTO inside `GlassModelSummary`, and Offers prints
   it with `{!! $summary->thumbnail->svg !!}` (`Offers/UserInterface/resources/views/filament/tables/offer-glass-cell.blade.php:10`,
   `Offers/UserInterface/resources/views/filament/glass-model-summary.blade.php:11`). Offers depends on Catalogue's markup
   and CSS classes, presentation sits in Application, and the unescaped output is safe only while every value in it is
   validated.
   Target (rule in `module-layout`, "Shared appearance"):
   - `Thumbnail` carries values only: shape, mirrored, fill, opacity, and the pictograms with their labels.
   - Catalogue publishes an anonymous Blade component in its UserInterface (for example `<x-catalogue::glass-thumbnail :thumbnail="…" />`)
     that draws the SVG; the SVG building moves out of `Application/Services` into UserInterface.
   - Catalogue's own cards and both Offers views render the component; no `{!! !!}` remains for the thumbnail.
   - The "Blade components … the owner publishes for others" row of `module-layout` ("Crossing a module boundary")
     already allows it; name the component in that row as its example.

## Done when

- Each defect first has a test that fails for the right reason (red), then the fix (green):
  - Livewire test: create a Glass model from the Create page with every optional field empty (no colour, no position,
    no equipment, no OEM codes) — the record is created and `colour_id` is null.
  - Handler tests: an unknown shape, pictogram and dictionary each end in a `DomainException` whose `translationKey`
    is asserted (not only the class).
  - Mapper test: a row without a main Eurocode raises the clear exception.
  - Thumbnail: a test of `GlassModelThumbnails` asserts the DTO values; a render test of the component asserts the
    SVG for a mapped and an unmapped (default) case; the Offers views still show the thumbnail.
- New exception keys exist in `UserInterface/resources/lang/pl/exceptions.php`.
- The diff touches Offers views and `module-layout`, so the full `composer check` closes this issue.

## Comments

### Worker — 2026-11-21
- **Built:** (1) `CreateGlassModel` now reads `$data['colour_id'] ?? null`; Livewire test with every optional field empty (red before: "value does not exist" refusal, colourId 0). (2) `SaveThumbnailSettingsCommandHandler` and `CreateDictionaryValueCommandHandler` use `tryFrom() ?? throw` with new `ThumbnailMappingRefused::unknownShape/unknownPictogram` and `DictionaryValueRefused::unknownDictionary`; keys in `pl/exceptions.php`; tests assert `translationKey` (red before: raw `ValueError`). (3) `GlassModelMapper` throws `UnexpectedValueException` naming `Glass model #id` for no main Eurocode or an invalid OEM code (red before: TypeError / null dereference). (4) `Thumbnail` DTO holds shape, mirrored, fill, opacity, pictograms (enum + label); `ThumbnailSvg` and its unit test removed; anonymous components `catalogue::glass-thumbnail`, `glass-shape`, `glass-pictogram` in `UserInterface/resources/views/components/` (auto-resolved by the module's view namespace, no registration needed); Catalogue cards, both Offers views and the settings-page previews use them; no `{!! !!}` for the thumbnail (the pictogram markup is shipped enum code only). `module-layout` row names the component as example. Baseline: removed both `CreateGlassModel.php` entries (strict comparison and `??` not nullable) and the `GlassModelThumbnails` entry of `CatalogueSettingsForm` in `tests/Architecture/baselines/module-boundaries.php` (no longer a ModuleApi call); no entries added.
- **Deviations:** the mapper exception is a plain `UnexpectedValueException` (data corruption, not a user refusal, so no translation key). `GlassModelThumbnails::shapeSvg()/pictogramSvg()` removed; the settings form renders the components directly. The Offers views pass `:pictograms="false"` to keep their previous look (shape only).
- **Unmapped:** Blade components `glass-shape` / `glass-pictogram` (private building blocks of the published `glass-thumbnail`, also used by Catalogue's settings page): placed next to it in `UserInterface/resources/views/components/`.
- **Seams left:** none
- **Open questions:** `composer check:module` first failed once with "Failed to load dumped Ecotone container" in the PHPStan workers (stale cache after deleting classes); clearing `storage/framework/cache/data/ecotone` fixed it. Pint `--dirty` does not work in the worktree container (no git there), so only the check's pint ran.
- **Check:** composer check:module catalogue,offers green (the full `composer check` is left to the manager, as instructed; the issue text asks for it)

### Worker — fix 1
- Added Offers assertions that the thumbnail SVG is drawn (`glass-thumbnail__shape` plus the default shape outline): `ListOffersTabsAndFiltersTest::test_the_list_row_draws_the_thumbnail_of_its_glass_model` (list cell) and `ViewOfferTest::test_the_glass_model_summary_draws_the_thumbnail` (summary). Check: composer check:module catalogue,offers green (980 tests; pint import order fixed once).

### Review — 2026-11-21
- **Verdict:** approved after 1 fix round (round 1: Offers tests asserting the thumbnail SVG in the list cell and on the view page).
- **Notes:** manager re-ran `composer check:module catalogue,offers` (green, 980 passed, phpstan OK); the full `composer check` runs once at the end of the run (user, 2026-11-21), not per ticket. Accepted deviations: the mapper's corrupt-data exception is a plain `UnexpectedValueException` naming the record (not a user refusal); `GlassModelThumbnails::shapeSvg()/pictogramSvg()` removed; Offers keeps the shape-only look (`:pictograms="false"`). `Thumbnail` now carries Catalogue's `GlassShape`/`Pictogram` enums (Domain/Enums are public surface). Baselines: both `CreateGlassModel.php` phpstan entries and the `CatalogueSettingsForm => GlassModelThumbnails` boundary entry removed. Unmapped (for the user): private sub-components `glass-shape`/`glass-pictogram` next to the published `glass-thumbnail`. Observed: PHPStan (Larastan boots the app) failed once on a stale Ecotone container cache after classes were deleted; clearing `storage/framework/cache/data/ecotone` fixed it.
