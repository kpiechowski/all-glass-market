# 04 - "Historia zmian" section on record pages

Status: resolved
Blocked by: 02, 03

Spec: FR-09, FR-10, FR-11, RULE-06, §11 deleted record / renamed reference cases. Decisions: D-12, D-19, D-21, D-35, D-38, D-40.

## What

- A reusable **Historia zmian** section (a tab) that a resource of any module mounts on its record's view page. It shows that record's entries, newest first, in a plain table:
  - when;
  - Actor: the User's name, or "System: {powód}";
  - kind.

  Filters by Actor and by kind. No changed fields in the table (D-19).
- Opening an entry shows its details: date, Actor, kind, then one line per changed field or relation with label, old value and new value, from ticket 02's presenter.
- Visible only with "historia rekordu" (Admin and above). Read-only: no create, edit or delete.
- Mount it on the Users user detail (FR-11). The V1 "Rejestr zmian użytkownika" is not rebuilt (D-21).
- **Seam:** other modules' UserInterface needs to mount an Audit UI piece, and `module-layout` lists no such crossing. Add the smallest seam and record it in `module-layout`'s crossing table. Preferred: Audit's UserInterface exposes the section, and hosting resources list it. Stop and ask if that is not workable.

## Done when

- Tests cover:
  - the list, newest first, scoped to the record;
  - both filters;
  - the detail lines, including a removed reference;
  - hidden without the Permission;
  - a seller (owner) cannot see it;
  - no edit or delete actions.
- The user detail shows the section.
- `module-layout` documents the seam.
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** "Historia zmian" tab (FR-09, FR-10, FR-11, RULE-06): `RecordHistoryRelationManager` in `Audit/UserInterface/Filament/RelationManagers`, mounted by a resource as `RecordHistoryRelationManager::make(['recordType' => 'users.user'])` (done in `UserResource::getRelations()`). Table (`RecordHistoryTable`): when, Actor (name or "System: {powód}" via `ChangePresenter::actorReason()`), kind; newest first; filters by Actor (users who acted + System) and kind (`RecordHistoryTableFilters`); no changed fields in the table (D-19). Row click or "Szczegóły" opens a modal (view `audit::change-details`) with date, Actor, kind and one line per field/relation from `ChangePresenter`, removed references marked "usunięto". Read-only (`isReadOnly`, no header/bulk/edit/delete actions). Visible on view pages only, via `CurrentActor::can(AuditPermission::RecordHistory)` (no role name; a seller cannot see it). `ChangeQueries` gained `actorsOf()` and `hasSystemEntries()` for the filter. Seam documented in `module-layout`'s crossing table; `ModuleBoundaryScanner` public surface extended with `UserInterface\Filament\RelationManagers\` (and its test). Tests: `tests/Feature/Modules/Audit/UserInterface/RecordHistoryTest.php`.
- **Deviations:** (1) The relation manager has no Eloquent relationship: `getRelationship()` wraps `ChangeQueries::forRecord()` in a `HasMany` because Filament only accepts a Relation there. (2) The tab shows on the view page only, not on edit. (3) Deptrac needed no change (UserInterface may already use UserInterface); the project's own boundary scanner test did, hence the scanner edit. (4) Removed-reference rendering is tested on the Blade view with a `PresentedValue(removed: true)`; the presenter's reference cases are covered by ticket 02. (5) Modal tests use `assertMountedActionModalSee` since `assertSee` does not include the modal partial.
- **Seams left:** other resources mount the same relation manager with their type key; ticket 05 (global log) reuses `ChangeQueries::all()` and the `change-details` view; ticket 06 may add a Restore action next to "Szczegóły".
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 0 fix rounds
- **Notes:** Seam: Audit's `RecordHistoryRelationManager::make(['recordType' => '<key>'])` listed in a hosting resource's `getRelations()`; recorded in `module-layout`'s crossing table and in `ModuleBoundaryScanner`'s public surface. `getRelationship()` wraps `ChangeQueries::forRecord()` in a `HasMany` because Filament requires a Relation. Visible on view pages with "historia rekordu". `ChangeQueries::actorsOf()`/`hasSystemEntries()` feed the Actor filter; ticket 05 reuses `RecordHistoryTable` and the details view. Note: `ListUsersTest` failed once in a full run and passed on rerun (flaky, Users, outside this issue).
