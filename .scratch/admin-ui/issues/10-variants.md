# 10 - Component variants and variants.md

Status: ready-for-agent
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
