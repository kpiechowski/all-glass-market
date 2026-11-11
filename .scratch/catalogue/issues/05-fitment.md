# 05 - Fitment of vehicles to Glass models

Status: resolved
Blocked by: 02, 03

Spec: FR-09 (count of Glass models a vehicle fits), FR-10, FR-11 (warning and fitment removal), WF-05, RULE-10, §9 event fan-out for vehicle changes. Decisions: D-11, D-15, D-44, D-51. GLOSSARY: Fitment.

## What

- An admin fits vehicles to a Glass model and removes fitments, from the Glass model. A fitment records no reason or source.
- The vehicle list shows how many Glass models each vehicle fits.
- Deleting a vehicle that fits models: warning with the count, then the vehicle and its fitments go (WF-05).
- *Glass model changed* for the model on fitment changes, and for every model a vehicle fits when that vehicle is changed or deleted.

## Done when

- FR-10, FR-11 and WF-05 behave as specified, with handler tests for the fan-out; Livewire tests for the fitment editing and the delete warning; `composer check` passes.

## Comments

### Worker — 2026-11-11
- **Built:** FR-10: fitment held in the GlassModel aggregate (`vehicleIds`, `fitVehicles`, `removeFitment`), table `catalogue_glass_model_vehicles` (FK cascade both ways), commands FitVehicles / RemoveFitment, edited from a "Dopasowanie" relation-manager tab on the Glass model view (writes are commands). FR-09: vehicle list column "Pasuje do modeli szkła" (`withCount`), plus a read-only "Pasujące modele szkła" tab on the vehicle (the seam in `getRelations()`). FR-11/WF-05: delete modal warns with the count; DeleteVehicle collects fitting model ids before removal (cascade removes fitments) and announces each as Glass model changed. UpdateVehicle announces every fitted model when the vehicle really changed (`Vehicle::change` now returns bool). Fit/Remove announce the model (no-op fit announces nothing).
- **Deviations:** vehicle update with no real change announces nothing (spec silent; mirrors rename behaviour). Fitment UI is a relation-manager tab rather than a view-page action, so it scales to hundreds of vehicles. `GlassModel::reconstitute` got a trailing optional `$vehicleIds = []`.
- **Seams left:** fitment is not part of the audit trail of the Glass model (no Glass model audit record exists yet).
- **Open questions:** none
- **Check:** composer check green (977 passed)

### Review — 2026-11-11
- **Verdict:** approved after 0 fix rounds
- **Notes:** fitment is part of the GlassModel aggregate (`vehicleIds`, `fitVehicles`, `removeFitment`), stored in `catalogue_glass_model_vehicles` with cascading keys. `DeleteVehicle` collects `idsFittingVehicle` before the delete and announces each model; `UpdateVehicle` announces fitted models only on a real change. Fitment is synced with plain `sync()` because Glass models are not audited; switch to `syncRelation()` if Glass model audit is added. Accepted deviations: an unchanged vehicle update announces nothing; fitment edited in a relation-manager tab whose actions send commands (scales to many vehicles).
