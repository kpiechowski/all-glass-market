# 10 - Component variants and variants.md

Status: resolved
Blocked by: 09

Spec: D-35, D-36, D-27.

## What

- Build in the component files:
  - `section--compact`, `section--flat`;
  - `tabs--underline`, `tabs--pills`;
  - `infolist--sheet`.
- Show each variant on the Styleguide next to the default.
- Once the user accepts them, write `.claude/skills/admin-ui-design/variants.md`: per variant, when to use it, when not, and the exact `extraAttributes` call. Replace the "see variants (pending)" notes in the templates.

## Done when

- The variants are accepted on screenshots and documented.
- `composer check` passes.

## Comments
- 2026-11-06 (manager): `infolist--sheet` (and the new `infolist--identity`) were built and accepted in 07 (D-10a). Here they are only documented in `variants.md`.
- 2026-11-07 (agent): Round 1, CSS and Styleguide only; `variants.md` waits for acceptance.
  - `section--compact` (`filament/schema/section.css`): one step tighter than a default nested section. Header 8 / 12 px, content 12 px, footer 8 / 12 px, 16 px gap between fields (12 px below md), title 14 px semibold. On the canvas it keeps the card surface; nested, only the ring.
  - `section--flat`: no frame or surface. Title 14 px semibold navy, 8 px above one gray-200 hairline, content 16 px below it, no inline padding (aligned with the parent content), footer 12 px below without a divider.
  - `tabs--underline` (`filament/schema/tabs.css`): the existing page-tab look (glow + 3 px navy indicator) is now the variant. Page and record tabs still get it without the class (no change on existing screens). The class names the intent and forces the look inside a section or a contained card. The Styleguide area tabs and record tabs now carry it explicitly.
  - `tabs--pills` (uncontained Tabs inside a section): a gray-100 track with a gray-200 hairline and 3 px inner padding. Pills are 32 px high with a 6 px radius and grey labels at weight 500. Hover adds a half-white fill. The active pill is white with the surface ring, a faint navy lift, a navy label (hairline text-shadow for weight, so widths stay put) and a royal icon. Panel 16 px below the track. Pills are excluded from the automatic page-tab look.
  - Styleguide, "Sekcje i układ": new blocks "Warianty sekcji" (`StyleguideSectionVariants`) and "Warianty zakładek" (`StyleguideTabVariants`). StyleguideTest checks they render.
- 2026-11-07 (agent): Round 2, after the user accepted the look. Wrote `.claude/skills/admin-ui-design/variants.md` covering all six variants: when to use each, when not, and the `extraAttributes` call on its component, plus the constraints (pills only on uncontained horizontal Tabs; page and record tabs get the underline look without the class). The "see variants (pending)" notes in `SKILL.md` (styling ladder) and `templates/C-record-view.md` now link to it, and the `SKILL.md` "Before you finish" list gained a variant check. The stale tabs.css comment was already replaced in round 1.
- 2026-11-07 (manager, closing): accepted by the user as built ("nothing spectacular, but I didn't specify what I really wanted"). `variants.md` documents the six variants. Left for later: richer section variants once the user specifies what they want, decided on a real module (D-35: more variants only when a module needs one).
