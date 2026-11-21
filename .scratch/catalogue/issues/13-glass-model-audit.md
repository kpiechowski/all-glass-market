# 13 - Glass model under the Audit change log

Status: resolved

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

### Worker — proposal 2026-11-21

Phase 1, no code written. Read: `module-events`, `module-persistence`, `ChangeRecorder`, `EloquentRepository::syncRelation()`, `RecordChangeCoalescer`, Audit's `AuditableRecordType` / `TrackedRelation` / `ValuePresenter` / `ChangePresenter`, `VehicleObserver`, `DictionaryValueObserver`, the Vehicle and DictionaryValue registrations, and `EloquentGlassModelRepository`.

**How things work today**
- `GlassModelModel` has no observer. `syncParts()` writes Eurocode rows (delete / `firstOrNew`+save, `is_main` flips on the existing row), OEM rows (delete + create) and the two pivots with plain `sync()`, all inside `ReadOnlyModel::writing()`. `save()` then `touch()`es the parent when any part changed.
- `RecordChanged` already carries `relationChanges`: per relation name, raw `added` / `removed` lists of `int|string`. Audit shows any relation declared as a `TrackedRelation` (label + `ValuePresenter` per item), and `RecordChangeCoalescer` folds them into the record's other changes and cancels an add followed by a remove of the same item in one command. Items may be strings, so a Eurocode or OEM code can be the "id" of the item. No Audit change is needed to show them.
- The only public way in is `ChangeRecorder::recordRelations(Model $parent, array $diff)`. `syncRelation()` is built for pivots only (it calls `->sync()`), so it cannot serve child rows.

**Options for Eurocodes and OEM codes (children are written by the adapter, so their own observers are the wrong place)**

1. **Adapter reports the diff on the parent, as tracked relations (recommended).**
   `syncEurocodes()` / `syncOemCodes()` already know exactly which rows they delete and create. They collect the code strings and, after the sync, call `ChangeRecorder::recordRelations($parentModel, ['eurocodes' => [...], 'oem_codes' => [...]])` (ChangeRecorder injected into `EloquentGlassModelRepository`; nothing buffered when empty). `equipment` and `vehicles` go through `syncRelation()` as the issue says. Registered as `TrackedRelation`s `eurocodes`, `oem_codes`, `equipment`, `vehicles` (plain presenter for the two code lists, `reference(DictionaryValueModel)` for equipment, `reference(VehicleModel, 'name')` for vehicles).
   A change of the **main** Eurocode (the `is_main` flip on existing rows, which add/remove does not see) is reported as one more tracked relation `main_eurocode` with `removed: [old]`, `added: [new]`.
   Cost: `EloquentGlassModelRepository` (+ constructor arg), new `GlassModelObserver`, both providers, `audit.php`, `GlassModelResource` / `ViewGlassModel` wiring, tests. **No change in Core or Audit**, so `composer check:module catalogue,audit` stays the right check.
   Weakness: the main change reads as "added / removed", not "old -> new".

2. **Same as 1, plus a Core method for a virtual field change.**
   Add `ChangeRecorder::recordFields(Model, array $changes)` (field => old/new) in Core, so the main change is one field line `main_eurocode: old -> new`, same format as any other field (label in `fieldLabels`, plain presenter). Eurocode/OEM add/remove stay as in 1.
   Cost: everything in 1, plus one small public method and a unit test in Core. Touching `app/Core` makes the full `composer check` the required check (the issue names only `check:module`). Audit: none.
   Gain: nicer main line. I think it is not worth a Core API for one line.

3. **Observers on the child models** (`GlassModelEurocodeModel`, `GlassModelOemCodeModel`) that report to the parent. Not recommended: the skill says an observer only calls `record()` for its own model; the children have no parent relation or Audit registration; the `is_main` flips and delete+recreate of an OEM row would produce noisy pairs per row, and a model created in one command would emit child events before the parent has its `Created` frame. More code, more risk, same result as 1.

**Recommendation: option 1.** Option 2 is a clean later upgrade if the user dislikes the main-Eurocode line.

**How the Historia tab would look with option 1** (columns as in the Vehicle tab: when, who, type, details; Polish labels in `catalogue::audit.glass_model.*`)

| Change | Line in the entry details |
|---|---|
| Eurocode added | `Eurocody` — Dodano: `4321ACL` |
| Eurocode removed | `Eurocody` — Usunięto: `4321ACL` |
| Main Eurocode changed | `Główny Eurocode` — Dodano: `4321ACL` · Usunięto: `1234ACL` (option 2: `Główny Eurocode`: `1234ACL` -> `4321ACL`) |
| OEM code added / removed / edited | `Kody OEM` — Dodano: `8E0 845 297` / Usunięto: `8E0 845 297` / (edit = one removed + one added in one entry) |
| Equipment added | `Wyposażenie` — Dodano: `Czujnik deszczu` |
| Fitment added / removed | `Pojazdy` — Dodano: `Audi A4 B8` / Usunięto: `Audi A4 B7` |
| Withdrawn | `Wycofany`: `nie` -> `tak` (boolean presenter) |
| Position / colour changed | `Pozycja`: `Przednia` -> `Tylna`; `Kolor`: `Zielony` -> `Brak` (reference presenter) |
| Created | entry kind "Utworzono", with Eurocodes, OEM codes, equipment, fitment folded in as added items (coalescer folds the relation diffs into Created) |
A command that changes fields and parts (the edit form) gives **one** entry with all lines together.

**Other points not clean under the skills / to decide**
- **Check scope:** only options 2 and 3 push the diff outside module folders; option 1 does not.
- **"Each observer event":** the model has no soft deletes, so there is no `restored`; Withdraw / Restore are ordinary `updated` field changes (`withdrawn`), as the issue says. The observer has `created`, `updated`, `deleted` only, and the test list should not expect a Restored kind.
- **Record title:** the Glass model has no name column. Title should be the main Eurocode (`mainEurocode->code`), the same label RULE-02 shows. `RecordChangeCommandHandler` loads the record only for `Deleted` entries, by `find()` after the row is gone (hard delete, children cascaded), so the title is `null` and a deleted Glass model's entry will show without a Eurocode. Same limitation exists for Vehicle today. Fix would need Audit/Core (carry the title in the event) — I propose leaving it, noted as a known gap; say if you want it handled.
- **Touch noise:** `save()` still `touch()`es the parent after part changes. That produces an `updated` with only `updated_at`, which Audit drops (technical field) and the coalescer folds away. With relations now reported, I would keep the touch (list ordering by `updated_at`), no entry is created from it.
- **Mixed types:** a Eurocode or OEM code consisting only of digits comes out of PHP array keys as an `int`; the coalescer compares with `in_array(..., true)`. Codes must be cast to `string` before recording, or an add/remove pair of the same code would not cancel.
- **Unmapped (will be logged):** reporting a child-row diff on the parent aggregate through `ChangeRecorder::recordRelations()` from the adapter. No skill rule covers child rows (`syncRelation()` is pivot-only); I follow the same "adapter reports a relation diff" pattern.
- **Record-type key** `catalogue.glass_model`; the registration is the third in `CatalogueModuleServiceProvider`, which already has two near-identical `register*` methods — I would add a third method in the same style (keeping that file growing; a small extraction would be a separate cleanup).
- Issue text says "lang/pl/audit.php": the real file is `app/Modules/Catalogue/UserInterface/resources/lang/pl/audit.php`.
- Issue text says the observer is registered in `CatalogueEventServiceProvider::$observers`: the class is in `Application/Providers/`; fine, just the path.
- `ViewGlassModel` has no template-C overrides yet; `GlassModelResource::getRelations()` already holds `FittedVehiclesRelationManager`, so the "Historia" tab is added next to it and `hasCombinedRelationManagerTabsWithContent()` / `getContentTabLabel()` (new key `catalogue::resources.glass_model.tabs.details`) are copied from `ViewVehicle`.

**Decision needed from the user:** option 1 (recommended), 2 or 3.

### Worker — 2026-11-21 (phase 2)
- **Built:** Glass model is audited as `catalogue.glass_model`. `GlassModelObserver` (created, updated, deleted) registered in `CatalogueEventServiceProvider`. `AuditableRecordType` in `CatalogueModuleServiceProvider` (title: main Eurocode; fields position, colour, withdrawn; tracked relations `eurocodes`, `main_eurocode`, `oem_codes`, `equipment`, `vehicles`); Polish labels in `UserInterface/resources/lang/pl/audit.php`. `EloquentGlassModelRepository` reports the Eurocode and OEM code diffs (codes cast to string) through `ChangeRecorder::recordRelations()` on the Glass model, reports a main Eurocode change as `main_eurocode` (added new, removed old), and syncs `equipment` and `vehicles` through `syncRelation()`; the parent is still touched when a part changed. "Historia" tab (`RecordHistoryRelationManager`) on `GlassModelResource`; `ViewGlassModel` follows template C like `ViewVehicle`. Tests: `GlassModelObserverTest` (created, field change, withdraw/restore as `withdrawn` changes, deleted, fitment, equipment, Eurocode add/remove, main change, OEM change with digits-only code, save with no change records nothing) and a view-page test for the tabs.
- **User decisions (2026-11-21):** option 1 (adapter reports child diffs, no Core or Audit change); the missing title of a hard-deleted record stays as it is (not fixed, see Open questions); no Restored kind, withdraw and restore are `withdrawn` field changes.
- **Deviations:** none. `phpstan-baseline.neon` has per-count ignores for the child models' `$code`; I read the code with `getAttribute('code')` in the new lines so no baseline change was needed.
- **Unmapped:** the child-row diff reported on the parent aggregate by the repository adapter through `ChangeRecorder::recordRelations()` (`EloquentGlassModelRepository::syncEurocodes/syncOemCodes`). No skill rule covers child rows (`syncRelation()` is pivot-only); followed the same "adapter reports a relation diff inside the command" pattern.
- **Seams left:** none.
- **Open questions:** a hard-deleted Glass model (like a Vehicle) gets a Deleted entry without a title, because Audit loads the record after the row is gone. Left as is, per the user.
- **Check:** composer check:module catalogue,audit green (549 tests).

### Review — 2026-11-21
- **Verdict:** approved after 0 fix rounds (phase 1 proposal → user decision → phase 2 build).
- **Notes:** user chose option 1 (adapter reports Eurocode/OEM code diffs through `ChangeRecorder::recordRelations()`; main Eurocode as relation `main_eurocode`; no Core/Audit change). Manager re-ran `composer check:module catalogue,audit` (green, 549 passed, phpstan OK). Labels follow the glossary and the resource labels (Eurokody, Eurokod główny). `pivotChanged()` costs two extra queries per save because `syncRelation()` returns void; acceptable, a bool return would touch Core. Open (user, left as for Vehicle): a hard-deleted Glass model's entry has no title. Unmapped (for the user): child-row diff reported on the parent aggregate by the repository adapter.
