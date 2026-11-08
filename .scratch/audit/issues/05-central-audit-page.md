# 05 - "Dziennik audytu" page

Status: resolved
Blocked by: 04

Spec: FR-12, §11 deleted-record case. Decisions: D-12, D-20, D-38.

## What

- A panel page **Dziennik audytu** listing every Change entry, newest first. It reuses the table and detail view from ticket 04, and adds a record-type column.
- Filters:
  - record type: the currently registered types;
  - one record;
  - Actor: a User, or System;
  - kind;
  - date range.
- Each row opens the entry detail and links to the record. There is no link when the record no longer exists (the deleted entry shows its last title).
- Only "dziennik audytu" (Super Admin by default). No export, no edit, no delete.

## Done when

- Tests cover every filter, the link and its absence for a removed record, and access refused without the Permission.
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** "Dziennik audytu" page (FR-12, D-12, D-20): `AuditLog` page in `Audit/UserInterface/Filament/Pages/AuditLog/` with `AuditLogTable` + `AuditLogTableFilters`. Reuses ticket 04's `RecordHistoryTable::columns()` and `detailsAction()` (made public) and the `change-details` view; adds record-type and record columns. Filters: record type (registered types), one record (id), Actor (any User who acted, or System), kind, date range (from/until, whole days). Row opens details; the record column links through the registration's `url` closure only when the record still exists (soft-deleted or gone: no link, shows the entry's stored last title, else `#id`). Access and navigation gated by `CurrentActor::can(AuditPermission::AuditLog)` in `canAccess()`. No export, header, bulk, edit or delete actions. `ChangeQueries` gained `allActors()` / `anySystemEntries()`. Tests: `AuditLogTest` (every filter, link and its absence, details, read-only, access refused, extra permission) and a query test.
- **Deviations:** (1) "One specific record" filter is a record-id input, combined with the record-type filter to pick the type (smallest reading; no per-type record search exists). (2) The record column shows the live title when the record exists, otherwise the stored title (only Deleted entries carry one) or `#id`. (3) Page access is tested through Livewire (403) rather than HTTP, because HTTP renders need the Vite manifest, absent in tests.
- **Seams left:** ticket 06 may add Restore beside "Szczegóły" via `detailsAction()`'s record actions.
- **Open questions:** none
- **Check:** composer check green


### Review — 2026-11-05
- **Verdict:** approved after 0 fix rounds
- **Notes:** Reuses `RecordHistoryTable::columns()`/`detailsAction()` and `change-details`. "One record" is an id input combined with the type filter (no per-type record search exists). Record column shows the live title and links via the registration's `url` only while the record exists; otherwise the stored title or `#id`, no link. Each row loads its record twice (title, url) — fine at page size, worth memoising if the page grows heavy.
