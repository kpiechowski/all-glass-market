# 04 - History tab and "Zmiany od ostatniego zatwierdzenia"

Status: resolved
Blocked by: 03

Spec: FR-12, §9 Audit. Decisions: D-20, D-39.

## What

- Admin's offer view: a **History** tab with the Offer's Change history (Audit module interface).
- On a Pending Offer that was approved before: a block "Zmiany od ostatniego zatwierdzenia" listing each changed field (and photo/Glass model change) with old → new value, from the Change history since the last approval. Nothing for an Offer never approved.
- Sellers see no history.

## Done when

- The block shows exactly the changes after the last approval; absent for a new Offer; hidden from sellers.
- Tests through the Audit module interface and a Livewire test; `composer check` passes.

## Comments

### Worker — 2026-11-20
- **Built:** FR-12. Audit ModuleApi `RecordChangesAfter::lastChangeOf(type, id, field)` (port `RecordChangeReader` in Domain, `EloquentRecordChangeReader` in Infrastructure): readable lines (old -> new, relation add/remove) of the Changed entries after the latest entry that changed `field`; null when none ever did. `PresentedLine`/`PresentedValue` moved from `Queries/PresentChange` into `ModuleApi` (they are now part of the interface; the module-boundary test requires it). New `ValuePresenter::resolved(Closure)` (kind `Resolved`); Offers registers it for `glass_model_id`, showing the Glass model's main Eurocode via Catalogue's `GlassModelSummaries` (raw id when unknown). Offers: History tab (`RecordHistoryRelationManager` in `OfferResource::getRelations()`, Audit's RecordHistory permission, so admins only) and the section "Zmiany od ostatniego zatwierdzenia" on the view page (visible to ManageAll, status Pending, `last_approved_at` set; empty note when nothing changed). `OfferModel::AUDIT_KEY` holds the type key (UI cannot depend on the provider). Tests: `RecordChangesAfterTest`, `ChangePresenterTest` (resolved), `ViewOfferHistoryTest` (real approve + seller edit via commands).
- **Deviations:** the Audit read cuts at "the latest entry that changed field F" (for Offers `last_approved_at`) instead of "since time T": the approval's own entry is stamped after `last_approved_at`, so a time cut would include the approval's status change. Photos use the existing `fileCount` presenter (already registered in 02). Worktree needed `public/build` copied from the main checkout (untracked/ignored) for the Styleguide tests.
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green (see final run)

### Worker — fix 1 — 2026-11-20
- **Built:** `RecordChangesAfter::lastChangeOf()` now nets per field (one line, old = first entry's old, new = latest; fields with equal net old/new dropped). New optional `$excludeFields` parameter keeps Audit generic; Offers passes `['status']`. Relation changes stay one line per entry. Tests: two edits -> one A->C line, change and revert -> none, excluded field; `ViewOfferHistoryTest` asserts no status line.
- **Deviations:** none
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green (1308 tests)

### Review — 2026-11-21
- **Verdict:** approved after 1 fix round (block listed one line per entry instead of a net change per field, plus the status line); the manager fixed one edge case inline (a field that started empty kept the middle value as "old"; `array_key_exists` instead of `??`, test added).
- **Notes:** Audit ModuleApi `RecordChangesAfter::lastChangeOf(type, id, field, excludeFields)` nets changes per field after the latest entry that changed `field` (Offers passes `last_approved_at`, excludes `status`). `PresentedLine`/`PresentedValue` moved to Audit's ModuleApi; `ValuePresenter::resolved(Closure)` shows `glass_model_id` as the main Eurocode. History tab = Audit's `RecordHistoryRelationManager` (RecordHistory permission). `OfferModel::AUDIT_KEY` holds the type key.
