# 07 - Infolists: entries, empty values, the label–value sheet

Status: resolved
Blocked by: 06

Spec: D-10, D-31, D-27, D-29, D-37.

## What

- Style the native components in `resources/css/admin/filament/infolists/`, one file per component, on the tokens from 04.
- Base look only. Variants come in issue 10.
- Work against the Styleguide. Report what changed per file so the user can review.

## Done when

- The user has accepted the look on screenshots. Note each round under Comments.
- `npm run build` and `composer check` pass.

## Comments

- 2026-11-06 (agent): round 1, base look of every native infolist entry. CSS only on `fi-*` (no vendor view, no sheet variant), plus a fuller Styleguide record.
  - New `tokens/entries.css`: every entry value lives there. Label 13 px weight 500 gray-600 (`--ag-text-secondary`); value 14 px weight 500 gray-900; label, value and helper 4 px apart; helper 13 px gray-500; "—" placeholder gray-400 at value size; links royal with a 30 % royal underline, navy + full underline on hover; copy icon gray-400, royal on hover; boolean icons 20 px; boxes (key-value, repeatable items, images) with a 1 px gray-200 edge, gray-50 heads. Transitions on `--ag-duration-fast` / `--ag-ease-out`.
  - `filament/infolists/entry.css`: label, gaps, helper text and hint, the "—" placeholder, and a whole-entry link or action (`->url()` on the entry) as a royal link with a focus outline. No grid columns set, so `inlineLabel()` and the issue 10 `infolist--sheet` can rearrange label and value freely.
  - `filament/infolists/text-entry.css`: value colour and weight (an explicit `->weight()` / `->color()` still wins), long values wrap, links in values, copyable values get a small copy icon after the text (always visible, royal on hover; badges excluded), values with a tooltip (`dateTooltip()`) get a dotted underline and the help cursor, grey bullets and 2 px between list lines, "and N more" message muted.
  - `filament/infolists/icon-entry.css`: the row is one value line (20 px) tall; the default `lg` icon steps down to 20 px; colours stay Filament's (a non-error "false" uses `->falseColor('gray')`, D-21).
  - `filament/infolists/image-entry.css`: 6 px radius (circles stay circles), 1 px gray-200 edge unless `->ring()` is set, 8 px gap, "+N" counter as a gray-100 chip.
  - `filament/infolists/key-value-entry.css`: body font instead of monospace, 6 px radius with the gray-200 edge, gray-50 head in 13 px semibold gray-600, 8/12 px cells, key grey medium, value in the entry colour, gray-200 row and gray-100 column dividers, centred "—" when empty.
  - `filament/infolists/repeatable-entry.css`: contained items are white cards with the gray-200 edge, 10 px radius, 16 px padding, 12 px apart, 16 px between entries inside; the table layout gets the same edge, gray-50 head 13 px semibold gray-600, gray-200 dividers.
  - Styleguide "Karta rekordu" (`StyleguideInfolist`): avatar (circular image), copyable e-mail and NIP, empty phone / address / credit limit / courier note / extra fields ("—"), state badge with helper, neutral badges and a badge list, booleans true (success) and false (grey), date and relative date with tooltip, e-mail for invoices as a `mailto:` link, website as a link, bulleted list, limited image list (+1), "Rozliczenia" as a 1/2/4 grid of metrics, delivery addresses as a contained repeatable (2 per row from lg), contacts as a table repeatable, key-value filled and empty, long notes. Images are inline SVG data URIs. New test `test_the_record_sheet_renders_every_entry_type_locally`.
  - pint, `npm run build` and `composer check` pass (641 tests).
- 2026-11-06 (user, via manager): round 1 entries accepted ("bardzo czytelne i miłe dla oka"), but the record wastes space. Decided D-10a (A–E) and D-07a: the sheet variant moves into 07, two sheet columns from xl, layout (b), an identity header, booleans with "Tak" / "Nie".
- 2026-11-06 (agent): round 2.
  - A. Sheet variant (`filament/infolists/entry.css`, end of file). Pure CSS on `.infolist--sheet`, no `inlineLabel()`. Filament's inline label is a 1/3 : 2/3 split on the viewport `sm` breakpoint, so it would need overriding anyway, it cannot react to a narrow side column, and it would be a second PHP switch on every section next to the class. How it works:
    - Rows: every entry of the section is a row with 12 px padding above and below and a gray-200 hairline on top (`--ag-sheet-rule` = `--ag-border`).
    - Each row is pulled up 1 px, so the first hairline lies on the section divider and is never doubled. Hairlines sit on top only, so no orphan line is left under a short last row.
    - The section content padding is 4 px vertically.
    - Label | value: from md, and when the row is at least 20 rem wide (each row is a size container), the label column is `clamp(7rem, 36%, 11.25rem)`: 180 px, narrowing to at least 112 px in narrow rows. The gap to the value is 16 px.
    - Label and value sit on one baseline (`align-items: baseline`, both on a 20 px line). Images align to the top.
    - Narrower rows stack label above value, and below md rows always stack.
    - An entry with a hidden label takes the whole row. An empty label column is dropped in the base too.
  - B. "Firma" runs `['default' => 1, 'xl' => 2]` with a 32 px column gap. Pairs: Nazwa firmy + NIP, E-mail do faktur + Telefon, Strona internetowa + Kategorie produktów. Adres, Obsługiwane marki and Zdjęcia punktu span the full width. Grid rows keep both columns aligned. "Integracja" (only key-value tables) stays one column. "Rozliczenia" moves to `sm` 2 / `2xl` 4 and the address cards to `2xl` 2, because the main column is narrower now.
  - C. Layout (b) uses `Grid` lg 3 + `Group` / `columnOrder`, as in B-form. The side column is "Konto": status badge + helper, Typ konta, Rola, E-mail potwierdzony, Zgoda marketingowa, Data dołączenia, Ostatnia zmiana. It has no special class. Its narrow rows stack or narrow the label through the container query.
    - Measured, sidebar 16 rem: at a 1280 px viewport the side card's rows are about 250 px wide, so they stack. From about 1500 px they go label | value with a narrower label.
    - The main column's two sheet columns stack each entry until a row is about 320 px wide (viewport about 1450 px); wider screens get label | value in both columns.
  - D. Identity header: a heading-less `Section` with `infolist--identity` around a native `Flex` (verticallyAlignCenter). Inside: a 56 px circular `ImageEntry` (`grow(false)`) and a `Group` of name (`TextSize::Large`, bold) and copyable e-mail, all with hidden labels. CSS (end of `entry.css`): 20 px vertical padding, 16 px between avatar and text, 2 px between lines, name 18 px navy with tight tracking, e-mail gray-600. New tokens `--ag-identity-*`.
  - E. Boolean = `TextEntry` + `formatStateUsing` (Filament's own `filament-infolists::components.entries.icon.true/false` = "Tak" / "Nie") + `icon` (solid CheckCircle / XCircle) + `iconColor` (success / gray). `IconEntry` cannot show text. In the Styleguide it is the helper `booleanEntry()`, also put into template C. `text-entry.css` centres an icon next to text on the 20 px line. `icon-entry.css` now describes icon-only values.
  - Tokens (`tokens/entries.css`): `--ag-sheet-label-width`, `--ag-sheet-label-gap`, `--ag-sheet-row-padding-y`, `--ag-sheet-rule`, `--ag-sheet-column-gap`, `--ag-identity-*`. The 20 rem container threshold is written in the query, because CSS cannot read variables there.
  - Skill: `templates/C-record-view.md` gets a new skeleton (identity header, layout (b), sheet with xl 2 columns, full-span long values, `booleanEntry()`), rules and Before-you-finish lines. In `SKILL.md`, "one field per row" now applies to form fields, and infolists point to template C. The Styleguide tab description is updated.
  - pint, `npm run build` and `composer check` pass (641 tests).
- 2026-11-06 (user, via manager): round 2 accepted ("teraz jest dobrze"). One fix by the manager: a sheet row whose value is a link gets 4 px more bottom padding (`entry.css`), because the link underline read as touching the next full-width hairline (Strona internetowa above Adres). Two sheet columns stay at `xl`.
- 2026-11-06 (manager): closed. Accepted: entry base look, the `infolist--sheet` variant (moved here from 10), two sheet columns from xl, layout (b) with the side "Konto" sheet, identity header (`infolist--identity`), Tak/Nie booleans. Recorded as D-10a / D-07a; template C and SKILL.md updated. Informative variation added to spec and template C: the (b) grid may hold only the top sections, with long sections full width below. Issue 10 no longer builds `infolist--sheet`; it only documents it in `variants.md`.
