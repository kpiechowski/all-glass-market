# 05 - "Dziennik audytu" page

Status: ready-for-agent
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
