# 04 - "Historia zmian" section on record pages

Status: ready-for-agent
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
