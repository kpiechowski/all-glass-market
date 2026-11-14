# Handoff: admin panel UI polish (Catalogue issue 11 and beyond)

Written 2026-11-14 by the manager session for the next agent who makes UI fixes in the admin panel.

## How you work (the user's rules, not negotiable)

- **Focus on the admin panel UI**: layout, spacing, CSS, Blade views and Filament presentation. Do not start domain, handler or persistence work, and do not refactor anything that is not on screen.
- **Do the changes yourself.** No subagents, no worktrees, no `module-delivery` loop. The user found that loop far too slow.
- **Do not run tests or `composer check` until the user says the work is finished.** Per round:
  1. If the user asks for a proposal first, say briefly what you will change and wait for the go. If they say "od razu wprowadzaj" (apply right away), just do it.
  2. Edit in the main tree, on branch `catalog`.
  3. Run `npm run build`. After a Blade change, also run `php artisan view:cache && php artisan view:clear` as a syntax check only.
  4. Report in Polish what changed and what to look at. Keep it short.
  5. The user checks the screen by hand and sends screenshots.
- **No browser.** The user clicks the panel. Never open or drive one yourself.
- **Find the real cause before you change CSS.** Read the Filament vendor CSS and Blade (`vendor/filament/tables/resources/css/*.css`, `vendor/filament/tables/resources/views/index.blade.php`) and the theme under `resources/css/admin/`. Guessing produced one round the user called a mess.
- **Tokens only.** Use the `--ag-*` tokens in `resources/css/admin/tokens/`. No hex colours and no inline styles. Every user-facing string comes from the module's translation files (`catalogue::…`). Load the `admin-ui-design` skill (and `filament-module-wiring` for Filament PHP) before editing. Read `.ai/rules/index.md` for the files you touch.
- **Commands run in the container as uid 1000:** `docker exec -u laradock -w /var/www/all-glass-market laradock8_5-workspace-1 sh -c '...'`. Inside `sh` there is no `dartisan`: use `php artisan`.
- **Never commit** `zweryfikwoania_klientem.md` (the user's file).

## Where things stand

Issue: `.scratch/catalogue/issues/11-glass-model-list-as-catalogue-cards.md`. Its Comments hold the original spec, the first build, the user's round-1 review with reference screenshots (`11-assets/`), and the layout target of fix round 1.

Already committed on `catalog`:
- `af59b66`: first build of the card.
- `3bc9bbd`: fix round 1.
- `607f300`: shared fix in `resources/css/admin/filament/infolists/entry.css`. The first row of an `infolist--sheet` draws no hairline, so no double line shows under a section header. Two-column sheets at xl drop it on child 2 too, unless child 1 spans the full width.

**Rounds 2–3, done by the manager without a subagent, committed 2026-11-15 but not yet accepted by the user (`ListGlassModelsTest` fails until the tests are updated after acceptance):**

| File | What |
|---|---|
| `app/Filament/Tables/TableDefaults.php` | `->striped()` on every table (the user's global request) |
| `resources/css/admin/tokens/tables.css` | `--ag-table-row-striped: var(--gray-50)` |
| `resources/css/admin/filament/tables/table.css` | striped background placed before hover/selected; selected now wins on striped rows too |
| `resources/css/admin/filament/tables/mobile.css` | card tables below md (`.ta-card-cell`): no stripes (cards are already apart) |
| `.../GlassModels/Tables/GlassModelsTable.php` | row-actions "⋮" menu removed; the row click opens the view page, where withdraw/restore/delete live |
| `.../views/filament/tables/glass-model-card.blade.php` | card as fixed-height lines: title, 3 facts, side column with 3 vehicles; the offers slot (09) on the vehicles caption line |
| `.../views/filament/tables/glass-card-values.blade.php` | `$itemClass` per value (code badge or chip), `—` as `.glass-card__empty` |
| `resources/css/admin/components/glass-card.css` | full rewrite of the card part (see its header comment); thumbnail part mostly unchanged (shape max 8.5rem) |

### Card design as it stands (the user's decisions so far)

- **Card width:** the card fills the cell (`flex: 1 1 auto; width: 100%`). Filament's `.fi-ta-col` is a flex row, so without this the middle column shrank to its content and the side column moved from row to row. That was the root cause of the "uneven columns".
- **Desktop columns:** picture 10rem, main `1fr`, side 17rem with a border-left. Lines are 2.5rem high with a faint `--ag-divider` hairline between them. The main Eurocode sits in the label column, so position · colour starts at the value x.
- **Badges:**
  - main Eurocode: soft primary, `text-sm`, semibold;
  - further Eurocodes: soft **info** tint;
  - OEM codes: soft gray rectangles;
  - equipment: white pills with a gray border.
- **List limits:** 2 values then "+N" for Eurocodes and OEM codes, 3 for equipment, 3 for vehicles. The "+N" carries a tooltip with the rest. Only "Wycofany" flags a model (no "Aktywny"). The search match badge sits on the title line.
- **No frame on the picture.** The user disliked the grey rounded box. If they still see too much white, offer a square background.
- **Mobile (< md):** the picture sits in the label column (7rem) beside the title, so the title aligns with the values. Facts and vehicles run below as label | value lines, labels may wrap, the card has 12px padding.

## When the user says the work is finished

1. Run `composer check` in the container. Expect `ListGlassModelsTest` to need updates:
   - `test_a_model_is_withdrawn_from_the_row_menu` (around line 53) calls the removed row action. Move what it proves to the view page test, or drop it if `ViewGlassModel` tests already cover withdraw.
   - The card order / "+N" / vehicles assertions may need new expected text: 3 vehicles now, equipment limit 3, the offers slot moved.
   - Striping may change markup assertions elsewhere (class `fi-striped`). Fix the tests, not the striping.
2. Run `vendor/bin/pint --dirty --format agent` in the container.
3. In the issue file: set `Status: resolved` and append a review entry (rounds 2–3 done inline by the manager and the UI agent, the user's acceptance date, the striping and the removed row menu as accepted changes).
4. Commit on `catalog`, in two commits with GLOSSARY terms:
   - `feat(catalogue): 11 …` for the card files;
   - `feat(admin-ui): striped rows in every table` for the four table files.
5. Update `.scratch/catalogue/delivery.md`: issue 11 row → `resolved (<sha>)`, next action. Remove the stale worktree with `.claude/skills/module-delivery/worktree.sh remove catalogue 11`. Its branch `catalogue/11` is already merged.
6. No migrations in this work.

## Open notes for later (not UI polish, do not fix unasked)

- The Core Authorizer runs one `users` role query per row action check. The `ListGlassModelsTest` query-count test filters it out. Caching the role per request would fix it, but nobody has decided to.
- Issue 09 (Offers-facing pieces) will fill the `offersLink()` slot and reuse the card in a picker. Keep the card markup independent of the table.
