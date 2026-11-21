# 13 - Glass model under the Audit change log

Status: ready-for-agent

Spec: X-06 left "what of catalogue changes is recorded" to the Audit module. Decision (user, 2026-11-21): the Glass
model is recorded, like Vehicle and DictionaryValue already are.

## Why

Dictionary values and vehicles have a change history; the Glass model — the central aggregate of the module — has
none. Its relations are synced with plain `sync()` (`EloquentGlassModelRepository.php:96-98`), which the issue
logs of 03 and 05 accepted only "until Glass model audit is added".

## What

- `GlassModelObserver` (thin, only `ChangeRecorder`), registered in `CatalogueEventServiceProvider::$observers`.
- A Glass model `AuditableRecordType` registered in `CatalogueModuleServiceProvider`, next to the vehicle and
  dictionary value ones (record type `catalogue.glass_model`), with its labels in `lang/pl/audit.php`.
- `equipment` and `vehicles` (fitment) synced through `EloquentRepository::syncRelation()` so they appear as
  relation changes of the Glass model.
- Eurocodes and OEM codes are child rows of the aggregate. Their changes must show up in the history of the
  **Glass model** (not as separate records). Propose the way in the issue log before building it; if
  `module-events` offers no clean way, stop and ask.
- Withdraw / restore appear as field changes.
- "Historia" tab on the Glass model view page via `RecordHistoryRelationManager`, as on `VehicleResource`, with the
  view page following template C (combined relation-manager tabs, content tab label) like `ViewVehicle`.

## Done when

- Tests: each observer event records a `RecordChanged` for the Glass model; a fitment change and an equipment change
  record relation changes; adding/removing a Eurocode records a change on the Glass model; a save with no change
  records nothing.
- `composer check:module catalogue,audit` passes.

## Comments
