# 02 - Vehicles

Status: resolved
Blocked by: 01

Spec: FR-07, FR-08, FR-09 (list and search; the "Glass models it fits" count comes with 05), FR-11 (delete; the fitment warning comes with 05), RULE-09, RULE-11, §8 Vehicle. Decisions: D-31, D-34, D-44.

## What

- **Vehicle** with name, make (make dictionary, required), model, body (body dictionary), years from–to (to may be open, from ≤ to), generation, ARGIC vehicle code (four digits). No slug.
- **Name:** required, unique ignoring case, editable; for a new vehicle the system proposes it from the fields in the FR-08 format, and the admin may change it. Never rewritten afterwards.
- **List and search** by name and fields; filters by make and body.
- **Delete:** allowed (no fitment exists yet); 05 adds the warning and fitment removal.
- Register the vehicle's use of make and body values with ticket 01's delete seam.

## Done when

- FR-07, FR-08, FR-09 (without the count) and FR-11 (without fitment) behave as specified; RULE-09 refusals tested.
- Handler tests, a Livewire test per page, `composer check` passes.

## Comments

### Worker — 2026-11-11
- **Built:** `Vehicle` entity (name, make, model, body, years, generation, ARGIC code; FR-07, RULE-09) with CreateVehicle, UpdateVehicle, DeleteVehicle commands, each authorised on `catalogue.manage` first. `VehicleNameDomainPolicy::propose()` builds the FR-08 name (`MAKE | MODEL | BODY | GENERATION FROM-TO | ARGIC`, empty fields left out, open end `FROM-`). Name unique ignoring case (`name_key` unique index), never rewritten by a handler; the create form fills it live from the fields until the admin types their own, the edit form never touches it. Refusals: name blank, too long or taken, years from after to, year not 4 digits, ARGIC not 4 digits, make/body of the wrong dictionary, withdrawn make/body newly chosen; a withdrawn make/body already on the vehicle survives a save (entity compares ids). Pickers use `VehicleQueries::offeredMakes/offeredBodies` (active plus the current value); the make/body filters list every value. `VehicleDictionaryUsage` is registered with ticket 01's seam (make, body; key `catalogue::uses.vehicles`). Resource "Pojazdy" in the Katalog group: list with search over name and all fields, make and body filters (FR-09), create, edit, view with audit history (vehicle registered as an auditable record), delete from row menu and view page (FR-11).
- **Deviations:** (1) No per-field Filament uniqueness rule: the handler's name-taken refusal is shown on the name field via `CommandSender` field errors. (2) Year bounds are four digits (1000-9999), my reading of "years". (3) Added `Vehicle` observer and audit type, not named in the issue, so the view page has the same history tab as dictionaries. (4) The create command requires a name (the form proposes it); there is no handler-side proposal.
- **Seams left:** `DeleteVehicleCommandHandler` comment and `DeleteVehicleAction` docblock mark where 05 adds the fitment count warning, fitment removal and "Glass model changed"; `VehicleResource::getRelations()` for 05's fitted-models tab; FR-09 count column comes with 05. Shared edits for the merge with 03: provider (bindings, `registerDictionaryUsages`, vehicle audit type), `CatalogueEventServiceProvider` observers, `PanelNavigation` (VehicleResource before DictionaryValueResource), lang files `audit`, `exceptions`, `resources` (appended `vehicle` blocks) and new `uses.php` (03 adds its own keys there).
- **Open questions:** none
- **Check:** composer check green (842 tests). Also edited `AdminPanelProviderTest` (Katalog group now lists Pojazdy before Słowniki).

### Review — 2026-11-11
- **Verdict:** approved after 0 fix rounds
- **Notes:** `Vehicle` entity checks the picked make and body (right dictionary; withdrawn only when already on the vehicle). `VehicleNameDomainPolicy::propose()` holds the FR-08 format; the form proposes, the command always carries the name. `VehicleDictionaryUsage` registers make and body uses with the 01 seam. Seams for 05: comments in `DeleteVehicleCommandHandler` and `DeleteVehicleAction`; `VehicleResource::getRelations()` for a fitted-models tab. Accepted deviations: years limited to four digits (1000–9999); vehicle observer and audit record type added for a history tab; the taken-name refusal comes from the handler, not a Filament unique rule.
