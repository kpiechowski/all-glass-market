# 11 - Glass model list laid out like a product catalogue

Status: ready-for-agent
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

### User review, round 1 — 2026-11-14
Screenshots in `11-assets/`: `round1-current.png` (what af59b66 looks like), `ref-allegro-list.png` and `ref-mediaexpert.png` (the readability and layout the user had in mind; not to copy 1:1). User's points:
- Still uneven and not "catalogue" enough. The picture does not need to be big (not MediaExpert size), but the row should be divided better.
- Spacing and padding are wrong around the title (Eurocode) and in the bottom part: the picture box touches the row lines, the equipment chips fall *under* the picture box as an extra row (rows get ragged), and there is a line under the subtitle that splits nothing. The right half of the row is empty.
- Not sure the rounded grey frame suits the pictures.
- The main Eurocode, one of the most important facts, is not a badge (blue). Further Eurocodes could be badges too, in another but still informative colour (lighter primary, or a secondary).

### Manager — target layout for fix round 1 (one series of changes, then the user reviews again)
Model: Allegro's list row. Three fixed column tracks, identical in every row, with no extra band below them:

```
┌──────────┬────────────────────────────────────────────────┬─────────────────────┬───┐
│          │ [1049AGSV]  Przednia · Zielona   [Wycofany] [Eurokod: …]          │ ⋮ │
│  [SVG]   │ Dalsze eurokody  [1049AGSV1] [1049AGSV2] +1    │ POJAZDY (3)         │   │
│          │ Kody OEM         198-028-198 · B198-028-198     │ Ford Focus I        │   │
│ (•) ▢ ◀) │ Wyposażenie      (Antena) (Kamera) +1           │ Skoda Kodiaq I  +1  │   │
│          │                                                 │ (N ofert → slot 09) │   │
└──────────┴────────────────────────────────────────────────┴─────────────────────┴───┘
  picture       main column                                    side column (border-left)
```

1. **Picture column** (~8rem fixed): no grey box, no radius, no background — the glass SVG on the row background, like a product photo on Allegro, centred, with the pictograms as one small row under it. Fixed height, so every row's picture area is the same.
2. **Title line:** the main Eurocode as a **solid primary badge** (filled primary, white/contrasting text, a step larger than other badges, semibold, tabular figures) — the strongest element of the row. Next to it position · colour as plain muted text. Flags on the right of the same line: "Wycofany" only when withdrawn, the search match badge when searching. No divider line under it.
3. **Fact lines** under the title, MediaExpert style "label: value", a **fixed label width** (e.g. 8rem, muted, normal case — not uppercase captions) so values start at the same x in every row. Always all three lines (with "—" when empty) so every row has the same height:
   - Dalsze eurokody — Filament **soft primary** badges (the default soft `primary` badge = lighter primary): informative and blue, but clearly below the solid main one. Max 2 then "+N" with tooltip. No new global colour.
   - Kody OEM — plain text (tabular figures), max 2 then "+N".
   - Wyposażenie — the neutral chips, one line, max 3 then "+N"; never wraps to a second line.
4. **Side column** (~15rem fixed, a thin border-left divider like Allegro's price column): "Pasuje do pojazdów (N)" caption, the first 2 vehicle names, "+N". At the bottom the `offersLink()` slot (09), empty now.
5. **Spacing:** one vertical rhythm from the theme spacing tokens: equal padding above and below the row content (the picture must not touch the row lines), a consistent gap between the title line and the fact lines and between the lines themselves, and a clear gap between the three columns. Rows end at the same height; no shadows, separation stays the table's lines.
6. **Mobile (< md):** picture ~5rem left of the title line; fact lines below at full width (label above or beside value); the side column becomes a normal fact line ("Pasuje do pojazdów (N): …") with no border; no horizontal scroll at 390px; row click and "⋮" keep working.

Everything else from the "What" and "Rules that stay" sections holds (tokens, `glass-card.css`, `catalogue::` strings, card markup independent of the table, constant query count).

### Worker — fix round 1 — 2026-11-14
- **Built:** the manager's target layout. Three fixed tracks (picture 8rem | main 1fr | side 15rem with border-left); nothing renders below them (the footer band is gone, equipment is now the third fact line). Picture: no box, radius or background, fixed 7.5rem area, glass centred with the pictogram row under it. Title line: main Eurocode as a solid primary badge (`--ag-accent`, white text, semibold, tabular, a step above other badges), position · colour as muted text, flags right ("Wycofany", search match); no divider. Fact lines "label: value" on an 8rem label (muted, normal case), always all three with "—" when empty: Dalsze eurokody = default soft primary Filament badges (2, then "+N" tooltip), Kody OEM = plain text (2, then "+N"), Wyposażenie = neutral chips on one line (3, then "+N" tooltip, nowrap). Side: "Pasuje do pojazdów (N)", 2 names, "+N", `offersLink()` slot at the bottom (hidden while empty). Spacing: Filament's `.fi-ta-cell` is p-0 vertically at every width (horizontal padding only: px-4 below sm, ps-3 from sm), so the card now owns `padding-block: space-4` at all widths and no inline padding of its own (md+: small end padding next to the "⋮" column); one gap token (space-2) between lines, space-5 between columns. Mobile (<md): picture 5.5rem beside the title line, facts stack full width (label above value), side becomes one more fact line without the border; title keeps space for the floating "⋮" below sm.
- **Deviations:** (1) Previous mobile padding was 16px all round on top of the cell's px-4 (double horizontal padding); now the cell supplies horizontal padding, the card only vertical. (2) Equipment chips limit lowered from 4 to 3 (per target layout). (3) Only the card order test changed (equipment line now precedes the side column).
- **Seams left:** `offersLink()` slot (09) in `.glass-card__offers` inside the side column.
- **Open questions:** none
- **Check:** composer check green (1066 tests); `npm run build` done. Test change: card order test now expects equipment before vehicles (markup order changed).

**For the user to check by hand (`/admin/glass-models`, light and dark):**
- Desktop: every row the same height; the picture (no frame) never touches the row lines (equal space above and below); label column (Dalsze eurokody / Kody OEM / Wyposażenie) and values start at the same x in every row; equipment chips on a single line, none under the picture; main Eurocode is a solid blue badge, further Eurocodes lighter blue badges; side column divider and vehicles list; models with empty lists still show three lines with "—"; "⋮" and row click work; search match badge shows at the title line right.
- 390px: picture left of the title line, fact lines full width below, vehicles as a normal line, no horizontal scroll, no overlap of flags with "⋮".

### Review — fix round 1 — 2026-11-14
- **Verdict:** technically approved; the manager replaced a hard-coded `#fff` on the main Eurocode badge with `--ag-control-mark` (white mark on an accent fill). Waits for the user's screen review.
