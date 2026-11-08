# 06 - Form fields: input, select, checkbox, toggle, radio, date, textarea, rich editor, file upload

Status: resolved
Blocked by: 05

Spec: D-07, D-31, D-27, D-29, D-37.

## What

- Style the native components in `resources/css/admin/filament/forms/`, one file per component, on the tokens from 04.
- Base look only. Variants come in issue 10.
- Work against the Styleguide. Report what changed per file so the user can review.

## Done when

- The user has accepted the look on screenshots. Note each round under Comments.
- `npm run build` and `composer check` pass.

## Comments

- 2026-11-06 (agent): round 1, base look of every native form field. CSS only on `fi-*` (no vendor view), plus a new Styleguide board.
  - New `tokens/fields.css`: every field value lives there. Field border `#c2c6cd` (between gray-300 and gray-400), hover gray-400, focus royal edge + 4 px royal halo at 16 %, invalid danger-500 edge (danger halo on focus), disabled gray-100 fill / gray-200 edge / gray-600 text, readonly gray-50 fill, affix cells gray-50. Small controls: gray-400 edge, royal checked, navy checked hover, gray-300 toggle off. All transitions use `--ag-duration-fast` / `--ag-duration-base` and `--ag-ease-out`, so reduced motion stops them.
  - `filament/forms/input.css`: the shared frame `.fi-input-wrp` (text, select, date, textarea, rich editor). The edge is a 1 px shadow ring plus the hairline `xs`, so fields stay 36 px like the md button; inputs pin `min-height: var(--ag-field-height)`, padding 12 px, 14 px text, gray-400 placeholder. Text affixes ("zł", "https://") are gray-50 cells behind a divider; inline affixes and icons sit in the field, the prefix icon darkens on focus. Radius = control radius (6 px).
  - `filament/forms/field-wrapper.css`: label 14 px Manrope 500 gray-800; required asterisk muted grey (`--ag-text-muted`) at weight 400; label, field, helper and error 6 px apart; helper 13 px muted; error 13 px danger-700 weight 500 with a small alert icon (lists keep bullets).
  - `filament/forms/select.css`: native and JS select at 36 px (multi-select too); disabled without the vendor 70 % fade; options hover/keyboard gray-100, the chosen option royal semibold.
  - `filament/forms/checkbox.css`: the box is owned unlayered: gray-400 edge, royal fill with the white tick, navy on hover, royal indeterminate bar, invalid in danger, disabled gray-100 (checked: gray-400 + white mark), keyboard focus = royal edge + halo like the inputs. Tick cause: the vendor tick is a white SVG `background-image` (not `currentColor`) inside `@layer components`; any unlayered theme rule wins over it, so a `background` shorthand in the theme (as in the first theme) wiped the tick. The fix restates tick and bar with size, position and repeat, and sets the fill only with `background-color`. In the current build the vendor tick was present; the new rules make it independent of it. Option labels regular weight.
  - `filament/forms/radio.css`: same states, white dot on royal.
  - `filament/forms/toggle.css`: off gray-300 (hover gray-400), on royal (hover navy), knob slides on the base token, focus = white gap + royal ring + halo, disabled grey track without fade. Other `onColor()`s untouched.
  - `filament/forms/textarea.css`: 8/12 px padding, 22 px line.
  - `filament/forms/date-time-picker.css`: display input 36 px; calendar panel container radius + `lg` shadow + gray-200 edge; month navy semibold; day hover gray-100, today royal semibold, chosen day royal fill with white text.
  - `filament/forms/rich-editor.css`: toolbar gray-50 band, tools hover gray-100, active tool royal on primary-50, content 12/16 px; disabled loses the band.
  - `filament/forms/file-upload.css`: dashed field edge drop zone; hover/drag royal dashed on primary-50; focus halo; invalid danger edge; disabled gray-100; "Przeglądaj" royal semibold; file panel gray-700.
  - Styleguide "Formularze": new block "Stany pól" (`StyleguideFieldStates`, state path `fieldStates`) under the form: required vs optional labels and a long helper; prefixes/suffixes (text, icon, inline, revealable password); checkbox, checkbox list with descriptions, radio with description, toggles; JS date, JS date-time and native date; readonly and disabled fields of every kind; validation errors on text, select, radio, checkbox and textarea, added on mount and after Anuluj (`Styleguide::showDemoErrors()`).
  - pint, `npm run build` and `composer check` pass (640 tests).
- 2026-11-06 (user, via manager): round 1 accepted overall ("okej, czytelnie"). Round 2: uploaded images show as previews in a responsive grid, 3 / 2 / 1 per row.
- 2026-11-06 (agent): round 2.
  - Styleguide `StyleguideForm::attachmentsSection()` (Załączniki): native API only: `->multiple()->image()->panelLayout('grid')->itemPanelAspectRatio(0.75)->maxFiles(6)`. FilePond reads the item ratio as height / width, so 0.75 gives 4:3 landscape tiles (grid defaults to square). `image()` accepts `image/*`.
  - `filament/forms/file-upload.css`: FilePond positions grid items absolutely and reads each item's width to fit them per row; Filament sets that width on `.filepond--root[data-style-panel-layout='grid'] .filepond--item` (50 %, 33 % at lg). We set it through `--ag-upload-grid-item-width`: `calc(100% - 0.5rem)` below 48rem, `calc(50% - 0.5rem)` from 48rem (md), `calc(33.333% - 0.5rem)` from 64rem (lg); the 0.5rem is FilePond's 0.25em item margin on each side. Previews: item panel and preview wrapper at the control radius (6 px), letterbox gray-100 instead of #222, no shadow. Round 1 drop zone unchanged.
  - pint, `npm run build` and `composer check` pass (640 tests). Deptrac needed `TMPDIR=/tmp/laradock-tmp` because `/tmp/cache` in the container is root-owned from earlier root runs.
- 2026-11-06 (manager): closed. The user accepted rounds 1 and 2: field frame `#c2c6cd` with royal focus halo, 36 px fields, labels 500 with a muted asterisk, brand checkbox/radio/toggle with the restored tick, error/disabled/readonly states, and the 3/2/1 image preview grid in the file upload. `/tmp/cache` in the container was re-owned to `laradock`, so `composer check` runs as uid 1000 without the TMPDIR workaround. Left for later: dropdown panel and multi-select badges (09), variants (10).
