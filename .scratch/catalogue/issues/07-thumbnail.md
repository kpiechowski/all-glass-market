# 07 - Glass model thumbnail and catalogue settings

Status: resolved
Blocked by: 06

Spec: FR-15, FR-16, FR-17, FR-18, RULE-12, §13 ADD-06, FR-04 thumbnail column. Decisions: D-54, D-55, D-56, D-57. ADR 0007 (settings belong to the module).

## What

- A shipped set of glass shapes (windscreen, rear, side drop, side fixed/body, roof, accessory, unknown) as SVG, and a shipped set of equipment pictograms plus a universal one.
- **Catalogue settings** page (admins): position → shape + mirrored; colour → fill colour + opacity 0–100%; equipment → pictogram. No upload of custom shapes. A mapping change changes no Glass model and raises no event.
- The thumbnail drawn from those mappings, with the equipment pictograms in a row, each with a tooltip of the full value name; defaults for anything unmapped (FR-18).
- Shown on the Glass model cards of 06.

## Done when

- FR-15–FR-18 behave as specified, the defaults included; tests for the drawing rules and the settings page; `composer check` passes.

## Comments

### Worker — 2026-11-11
- **Built:** FR-15–FR-18, RULE-12, D-54–D-57. Drawing rules in one pure place: `Domain/Policies/ThumbnailDrawingPolicy::draw()` (position shape + mirrored, colour fill + opacity, pictogram per equipment, all FR-18 defaults: unknown shape, clear glass `#cfe6ee` at 45%, universal pictogram), on `ThumbnailSettings` and `ThumbnailDrawing` value objects; shipped sets are enums `GlassShape` (7) and `Pictogram` (universal + 7), `FillColour` (#rrggbb only). `Application/Services/ThumbnailSvg` writes the markup; `Application/ModuleApi/GlassModelThumbnails::for(positionId, colourId, [valueId => name])` returns a `Thumbnail` (svg + pictograms with labels), the seam for ticket 08. Storage: `catalogue_thumbnail_mappings` (one row per mapped value), entity `ThumbnailMapping`, port `ThumbnailMappingRepository`, mapper, model, factory, `ThumbnailMappingQueries`. `SaveThumbnailSettingsCommand` + handler (authorize, entity guards dictionary and opacity 0–100, empty entry removes the mapping, no event, no Glass model touched). Page `CatalogueSettings` (template D, one form, three sections, sticky Zapisz, "Ustawienia katalogu" in the Katalog group of the sidebar, Admin only). Cards of 06 now fill the `->thumbnail()` slot: SVG glass plus a row of pictograms with a tooltip of the full value name. CSS in `glass-card.css`; `npm run build` needed in the main tree.
- **Deviations:** (1) Settings are one table keyed by dictionary value id, not a generic key/value one (ADR 0007). (2) A colour's mapping is on/off through a "Własne wypełnienie" toggle (the colour picker cannot be cleared); off removes the mapping. A blank opacity in the command means 100. (3) Clear-glass default value (`#cfe6ee`, 45%) and the SVG artwork are my choice. (4) `GlassModelThumbnails` is scoped and reads the settings once per request. (5) Settings changes are not audited (no spec requirement).
- **Seams left:** ticket 08 includes `GlassModelThumbnails::for()` in the Glass model summary; ticket 09 the `->offersLink()` slot.
- **Open questions:** none
- **Check:** composer check green (1039 passed)

### Review — 2026-11-11
- **Verdict:** approved after 0 fix rounds
- **Notes:** drawing rules in `Domain/Policies/ThumbnailDrawingPolicy::draw()` (pure; FR-18 defaults: unknown shape, clear glass `#cfe6ee` at 45%, universal pictogram); shipped sets are the enums `GlassShape` and `Pictogram`; markup in `Application/Services/ThumbnailSvg`. `Application/ModuleApi/GlassModelThumbnails` already returns a `Thumbnail` for 08 to put in the summary. Settings: `catalogue_thumbnail_mappings`, one row per mapped dictionary value, cascading away with the value (a mapping is not a use, FR-24); written by `SaveThumbnailSettingsCommand`, no event, no Glass model touched (RULE-12). Accepted deviations: typed per-value rows rather than key/value (fits ADR 0007); colour fill switched by a "Własne wypełnienie" toggle, blank opacity = 100; clear-glass default and SVG artwork chosen by the worker; settings not audited. The main tree needs `npm run build` (glass-card.css).
