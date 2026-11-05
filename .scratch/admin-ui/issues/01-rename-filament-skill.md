# 01 - Rename filament-module-ui to filament-module-wiring

Status: resolved

Spec: D-01, D-03.

## What

- Rename `.claude/skills/filament-module-ui/` to `.claude/skills/filament-module-wiring/`. Update the `name:` and the description so it reads as the mechanics of Filament in a module: commands from the UI, visibility, reading, class split, namespaces and translations.
- Move the rules about how a screen looks out of it, for example "one action stands alone, 2+ go into `ActionGroup`". Leave a one-line pointer to `admin-ui-design`. The class-split rules ("6+ columns → `{X}TableColumns`") stay, because they are code organisation.
- Its "Before you finish" gets one item: "the `admin-ui-design` checklist holds."
- Update every reference: `CLAUDE.md` (skill table, plus a new row for `admin-ui-design` on "Screen layout, actions, tables, theme CSS"), `.claude/agents/module-worker.md` (load `admin-ui-design` for any UI task), `.claude/skills/module-cqrs/SKILL.md`. Leave `docs/archive/` untouched.

## Done when

- `grep -rn "filament-module-ui" --exclude-dir=docs/archive .claude CLAUDE.md` returns nothing outside history.
- The moved rules are kept in a scratch note for issue 02, so nothing is lost.

## Comments

### Worker — 2026-11-05
- **Done:** `git mv .claude/skills/filament-module-ui .claude/skills/filament-module-wiring`; new `name:`/description (mechanics only); one-line pointer to `admin-ui-design` under the title; "Before you finish" gains "The `admin-ui-design` checklist holds."; the discovery line now says sidebar entries are the panel provider's one manual list (D-25). References updated in `CLAUDE.md` (wiring row reworded + new `admin-ui-design` row), `.claude/agents/module-worker.md` (load `admin-ui-design` for any UI task), `.claude/skills/module-cqrs/SKILL.md` (2 mentions). `docs/archive/` untouched; old `.scratch/*` history mentions left as history.
- **Moved rules (note for 02):** split-table row "Record actions: one visible action stands alone; two or more go into one `ActionGroup` (the three-dot menu)" and checklist item "Every table with two or more record actions groups them in one `ActionGroup`". Both now live in `admin-ui-design` (SKILL.md action hierarchy + A-list). Class-split rules (6+ columns → `{X}TableColumns` etc.) stay.
- **Check:** `grep -rn "filament-module-ui" .claude CLAUDE.md` returns nothing.
