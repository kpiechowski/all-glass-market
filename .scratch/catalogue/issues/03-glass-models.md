# 03 - Glass models: create, edit, withdraw, delete

Status: resolved
Blocked by: 01

Spec: FR-01, FR-02, FR-06, FR-14 (fan-out to Glass models), FR-24 (Glass models, uses inside Catalogue), FR-25 (Glass model view), WF-01, WF-03, WF-04 (Glass models), RULE-01, RULE-02, RULE-03, RULE-04, RULE-05, RULE-06, §7, §8 Glass model, §9 events. Decisions: D-01, D-03, D-04, D-07, D-09, D-14, D-35, D-39, D-41, D-43, D-44, D-51. ADR 0008.

## What

- **Glass model** with its main Eurocode (normalised per RULE-03, unique across the catalogue including withdrawn models), position (required), colour (optional), equipment (set), OEM codes (RULE-04: stored uppercase and trimmed, compared ignoring spaces, dashes and dots, once per model) and the withdrawn flag.
- **Create** (WF-01) and **edit** (WF-03), also while withdrawn; a withdrawn colour or equipment value already on the model survives a save but cannot be newly chosen.
- **Withdraw, restore, delete-unused** (WF-04). Nothing outside Catalogue uses a model yet; leave the seam that 09 fills with the offers' use.
- **Events** *Glass model changed* (on every change, not on creation), *withdrawn*, *restored*; and the fan-out of FR-14: renaming a position, colour or equipment value announces every Glass model using it.
- Register the model's use of position, colour and equipment with ticket 01's delete seam.
- **A plain list** of Glass models (main Eurocode, position, colour) and a view with the FR-25 extension point. Cards, search and filters come with 06. Only the main Eurocode is entered here; ticket 04 adds further Eurocodes.

## Done when

- WF-01, WF-03, WF-04 and their refusals behave as specified (taken Eurocode names the model holding it; empty or over-long code; missing position; delete while in use).
- Test-first entity and handler tests, mapper and repository tests, a Livewire test per page, `composer check` passes.

## Comments

### Worker — 2026-11-11
- **Built:** `GlassModel` aggregate (FR-01, FR-02, FR-06, RULE-01..06): Eurocodes held as a list with one main (so 04 adds, moves and removes without reshaping; `Eurocode` VO normalises per RULE-03, `EurocodeHolder` carries the holder so the refusal names the model; unique across the catalogue by a DB unique index and a handler fact, withdrawn models included), position required, colour optional, equipment set, OEM codes (`OemCode` VO, RULE-04: uppercase, trimmed, kept once by the space/dash/dot comparison). Commands create, update, withdraw, restore, delete (WF-01, WF-03, WF-04), each authorised first. A withdrawn colour, equipment or position already on the model survives a save but cannot be newly chosen (`GlassModelChoices`). Events *Glass model changed* (update only when something changed; never on creation), *withdrawn*, *restored*; FR-14 fan-out in `RenameDictionaryValueCommandHandler` announces every model using the renamed position, colour or equipment value. Glass models register their use of position, colour, equipment with ticket 01's `DictionaryValueUsages` (`catalogue::uses.glass_models`). Filament resource "Modele szkła" in the Katalog group: plain list (main Eurocode, position, colour, state), create and edit pages (form: Eurocode, position, colour, equipment, OEM tags), view with withdraw/restore/delete and the FR-25 seam `GlassModelViewSections`.
- **Deviations:** (1) Changed event only when the update changes something (same reading as `ProducerChanged`). (2) Blank OEM entries are dropped; two OEM codes equal by RULE-04 are silently de-duplicated (first kept), max 255 characters. (3) No audit record type or history tab for Glass models (the issue does not ask; the dictionary one exists from 01). (4) `AdminPanelProviderTest` katalog assertion changed from exact list to "contains" for both resources. (5) Edit page shows the main Eurocode locked; Eurocodes are managed by 04. (6) Edited ticket 01 pieces additively: `DictionaryValueQueries::choosable()`, rename handler (fan-out + `GlassModelRepository` dependency), `DictionaryValueUsagesTest` now uses RefreshDatabase because the registered usage queries the DB.
- **Seams left:** `GlassModelUsages::register()` + `Domain/Contracts/GlassModelUsage` (09: offers' use; reuses `DictionaryUse`); `GlassModelViewSections::register()` (integrations); `GlassModel::create()/reconstitute()` take the Eurocode list (04); mapper `eurocodeRows()`, adapter `syncEurocodes()` already handle several Eurocodes; `GlassModelRepository::holderOfEurocode($code, $exceptId)` for 04's add/move; `GlassModelQueries::listing()` for 06's cards and search.
- **Open questions:** merge hotspots with ticket 02: `CatalogueModuleServiceProvider` (imports, bindings, register, boot), `PanelNavigation` (Katalog group items), `resources.php`/`exceptions.php` (my blocks sit at the top), `AdminPanelProviderTest`, and a new `uses.php` that 02 likely creates too.
- **Check:** composer check green (855 tests)

### Review — 2026-11-11
- **Verdict:** approved after 0 fix rounds; the manager rebased onto 02 and resolved the merge by hand.
- **Notes:** `GlassModel` holds `eurocodes` as a list plus `mainEurocode`, ready for 04. Choosability of position, colour and equipment (right dictionary, not withdrawn unless already held) lives in `Application/Services/GlassModelChoices`, shared by create and update. FR-14 fan-out is in `RenameDictionaryValueCommandHandler` via `GlassModelRepository::idsUsing()`. Seams: `GlassModelUsages` + `Domain/Contracts/GlassModelUsage` for 09; `GlassModelViewSections` (FR-25). Accepted deviations: *Glass model changed* only when an update changes something; blank OEM entries dropped and RULE-04 duplicates silently folded (first kept); no audit history for Glass models (spec §9 leaves audit to the Audit module); Eurocode read-only on edit until 04. Merge: migration renamed to `2026_11_11_220000_…` (it shared 02's timestamp); Katalog order Modele szkła, Pojazdy, Słowniki; `uses.php` labels lower-case, as they sit inside the refusal sentence.
