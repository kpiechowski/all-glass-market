# 11 - Glass model list laid out like a product catalogue

Status: resolved
Blocked by: 06, 07, 10 (resolved; 10 gives the seeded data to check the screen against)

Spec: FR-04, FR-05 (search match label), FR-18 (thumbnail and pictograms). Decisions: D-53 (cards, compared side by side; "layout details at delivery"), D-57, D-37 (the "N offers" slot stays for 09). Skill: `admin-ui-design` (template A), `filament-module-wiring`.

## Why

The card list built in 06/07 works but reads badly (checked by the user on `/admin/glass-models` with `seed-katalog m` data, 2026-11-12):

- **Rows do not line up.** Every card is its own grid: the thumbnail column is `auto`, so "Kody OEM", "Rodzaj i kolor" and "Pasuje do pojazdów" start at a different x in every row. The eye cannot run down a column to compare models, which is the whole point of D-53.
- **Rows have different heights** (with or without pictograms, one or two lines of equipment), so the list looks ragged.
- **The thumbnail is small** (6.5rem, no frame) and the pictograms hang under it at varying widths.
- **"Aktywny" on every row** is noise: `admin-ui-design` says a state that marks only the exception is a flag. Only "Wycofany" should show.
- The facts are four equal label/value columns with no hierarchy: the main Eurocode, the thing people look for, is the same size as everything else.

## What

Rebuild the card (`GlassModelCardColumn`, `glass-model-card.blade.php`, `glass-thumbnail.blade.php`, `resources/css/admin/components/glass-card.css`) into a **product-catalogue row**: picture first, then one composed block of information. Same data, same search, filters, actions and row click; only the layout changes.

### Desktop (from `md`)

```
┌──────────────┬──────────────────────────────────────────────────────────┬───┐
│              │ 1049AGSV                      [Wycofany] [Eurokod: 1049…] │ ⋮ │
│   [ SVG ]    │ Przednia · Zielona                                        │   │
│              ├──────────────────┬──────────────────┬────────────────────┤   │
│              │ DALSZE EUROKODY  │ KODY OEM         │ POJAZDY (3)        │   │
│ (•) ▢ ◀)     │ 1049AGSV1        │ 198-028-198      │ Audi A4 B8 08–15   │   │
│              │ 1049AGSV2        │ B198-028-198     │ Audi A5 07–16 +1   │   │
│              ├──────────────────┴──────────────────┴────────────────────┤   │
│              │ [Czujnik światła] [Kamera]                    N ofert →   │   │
└──────────────┴──────────────────────────────────────────────────────────┴───┘
```

1. **Picture column, fixed width** (about 10–11rem, the same in every row): a framed box (theme surface tone, radius from the theme tokens) with the glass SVG centred and larger than now (about 8–9rem wide), and the equipment pictograms as one row at the bottom of the box. The box has a fixed height, so a model without pictograms has the same picture area as one with three.
2. **Information block**, one custom layout in three bands:
   - **Title band:** the main Eurocode as the record's title (larger and semibold, tabular figures), under it position · colour as the subtitle. On the right: the "Wycofany" flag only when withdrawn, and the search match badge (FR-05) when searching. No "Aktywny" badge.
   - **Facts band:** three columns with **fixed tracks that are identical in every row** (e.g. `grid-template-columns: 11rem 11rem minmax(0, 1fr)`, or a subgrid; the worker picks, the result must line up): further Eurocodes, OEM codes, vehicles. Labels as small uppercase captions (theme muted text). Each list shows at most 2 values, then "+N" (with a tooltip or title listing the rest), so a long list never makes a row taller than its neighbours. Vehicles show the count in the label and the first 2 vehicle names (the same name format the vehicle screens already use), eager-loaded so the list keeps a constant query count.
   - **Footer band:** equipment as small neutral chips (text, the pictograms stay in the picture), and on the right the `offersLink()` slot that 09 will fill (empty now, but the place is kept).
3. **Equal rows:** the picture box sets the minimum row height; the information block is laid out so that typical data (≤2 codes per list, ≤4 equipment chips) fits in that height. Rows are separated as now by the table's lines, no extra shadows.

### Mobile (below `md`)

The same card stacked: picture box smaller (about 6rem) on the left of the title band, facts as label/value rows below, chips at the bottom. Readable at 390px with no horizontal scroll. The row click and the "⋮" menu keep working (template A mobile rules).

### Rules that stay

- Everything in theme tokens (`--ag-*`) and in `glass-card.css`; no inline styles, no new CSS file, no new dependency.
- Every user-facing string from `catalogue::` translation files (the "+N" text and the vehicles label included).
- The card stays a read of the model and `{X}Queries`; no new writes. The thumbnail still comes from `GlassModelThumbnails` (07).
- The picker modal of 09 will reuse this card later: keep the card's markup independent of the table (data in, HTML out) so it can be rendered outside the table.

## Done when

- On `/admin/glass-models` with `dartisan seed-katalog m` data, the facts columns start at the same x in every row, and rows without pictograms or with short lists have the same height as their neighbours.
- Only withdrawn models show a state flag; the search match badge still shows when searching.
- Lists longer than 2 values collapse to "+N" with the rest available on hover.
- The list query count does not grow with the number of rows (test, like the one from 06).
- Checked by screenshots at desktop width and at 390px, light theme and dark theme; attach what you checked in the log entry.
- Existing card and search tests from 06/07 pass, updated only where the markup intentionally changed; `composer check` passes; `npm run build` done.

## Comments

### Worker — 2026-11-12
- **Built:** FR-04/FR-05/FR-18/D-53 card rebuilt as a catalogue row. One grid per card with named areas (picture, title, facts, footer). Desktop: picture column 10.5rem and a 9rem-high framed box (surface-hover tone, container radius) holding the glass and the pictogram row (the `<ul>` is always drawn so a model without pictograms keeps the same picture area); facts on fixed tracks `11rem 11rem 1fr`, identical in every row; each list shows 2 values then "+N" (tooltip with the rest; key `card.more`); vehicles label "Pasuje do pojazdów (N)" with the first 2 names. Title: main Eurocode (lg, semibold, tabular), position · colour as subtitle, "Wycofany" flag only when withdrawn (no "Aktywny"), search match badge kept. Footer: equipment chips (4 then "+N") and the empty `offersLink()` slot. Mobile: 6rem x 6.5rem picture beside the title, facts as label/value rows, chips below. Vehicles eager-loaded in `GlassModelQueries::listing()` (one window-function query, first 10 by name per model, only id+name). Card markup is data-in/HTML-out (`glass-model-card`, new partial `glass-card-values`). Tests: card order test updated, new tests for withdrawn-only flag, "+N" collapse, constant query count. `npm run build` done.
- **Deviations:** (1) Vehicle name format = the `name` column (as the Vehicle screens show). (2) Vehicle tooltip lists at most the 10 loaded vehicles (bounds the eager load); "+N" count is the real total. (3) Query-count test ignores the `from "users"` role lookup: the Core Authorizer runs one per row action, grows with rows, and predates this ticket (candidate for a Core fix: cache the role per request). (4) Removed unused translation key `card.parameters`. (5) Screenshots not taken (instructed); see check list.
- **Seams left:** `offersLink()` slot (09) in `.glass-card__offers`; the card view is reusable in 09's picker.
- **Open questions:** Authorizer per-check role query (see deviation 3).
- **Check:** composer check green (1066 tests); `npm run build` done.

**For the user to check by hand (`/admin/glass-models`, `dartisan seed-katalog m`), light and dark:**
- Desktop (>=768px): the three captions DALSZE EUROKODY / KODY OEM / POJAZDY start at the same x in every row; every picture box is the same size (frame visible against the row in both themes), glass centred, pictograms in one row at the bottom, also for models with none; rows with short lists are as high as their neighbours; Eurocode large and bold, "Wycofany" only on withdrawn (toggle "Pokaż także wycofane"), no "Aktywny"; with a search (3+ chars) the match badge shows at the top right; "+N" shows on lists over 2 and its tooltip lists the rest; the "⋮" menu and row click still work.
- 390px: picture (6rem) left of the Eurocode and subtitle, facts below as label/value rows, chips at the bottom, no horizontal scroll, "⋮" and row click work; chips frame/text contrast readable in dark.

### Review — 2026-11-12
- **Verdict:** approved after 0 fix rounds (technical review; the user checks the screen by hand after the merge)
- **Notes:** accepted deviations: vehicle names are the `name` column; the eager load caps at 10 vehicles per model (tooltip lists at most those, "+N" uses the real count); the query-count test leaves out the Core Authorizer's per-row `users` role lookup, which predates this ticket and is a candidate for a Core fix (cache the role per request). The manager fixed the indentation in `glass-thumbnail.blade.php`. Seams: `offersLink()` slot in the footer (09); the card view is data in, HTML out, for 09's picker.
