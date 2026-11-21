# 10 - Offer lists: tabs, filters, stat cards

Status: resolved
Blocked by: 08, 09

Spec: FR-31, FR-32. Decisions: D-06, D-47. Skill: `admin-ui-design`.

## What

- Admin "Wszystkie oferty": tabs Pending (counter), Active, Reserved, Sold, Withdrawn, Rejected, Drafts, Trash; filters Eurocode/OEM/vehicle text, position, colour (through Catalogue's `matchingIds`), "without Glass model", seller, producer, condition, rating, price range, Glass model (target of the "N ofert" link); bulk actions from 03, 06, 09.
- Seller "Moje oferty": the same tabs except Trash; bulk change price and withdraw.
- Above "Moje oferty": **custom stat cards with an icon** (not stock Filament widgets): own Active, Pending, Reserved, sold this month.
- Lists show the Glass model summary (main Eurocode, position, thumbnail) and buyer's price.

## Done when

- Tabs, counters and filters tested (Livewire); a seller never sees others' Offers; `composer check` passes.

### Worker — 2026-11-21
- **Built:** FR-31/FR-32. Tabs (all, pending, active, reserved, sold, withdrawn, rejected, drafts; admin also trash) on one `ListOffers`; each but Trash via `OfferQueries::outsideTrash`/`onlyStatus`; Pending counter admin-only. Filters (`OffersTableFilters`): Eurocode/OEM/vehicle text, position, colour (all through `GlassModelCandidates::matchingIds`, withdrawn models included), without Glass model, Glass model (URL: `?filters[glass_model][glass_model_id]=<id>`, tested), seller (admin), producer, condition, rating (a new piece counts as 5 stars, D-46), price range on the buyer's price. Buyer's price in SQL (`OfferQueries::buyersPriceSql`, remainder form for exact integer division, portable across MySQL and SQLite) tested against `BuyersPricePolicy` on 15 prices x 7 rates x both Account types; the column sorts by it. Custom stat cards (`OfferStatCards` widget, own Blade view + `stat-card.css`, not discovered on the dashboard): own Active, Pending, Reserved, Sold this month (own Sales by `sold_at`, returned excluded); seller only. Glass column (`offer-glass-cell`: thumbnail, main Eurocode, position and colour, or the hint) read per page through `OfferListGlass` (scoped, primed in `getTableRecords`). Bulk actions hidden per tab by `OfferActionVisibility::bulkApplies` (Trash: none; status tabs: only where the handler's rule can apply).
- **Deviations:** Catalogue needed no change: `GlassModelSummaries::ofMany` (with its own test of constant query cost) already exists, so the "smallest batch read" is that one. Price range filters on the buyer's price (what buyers see), not the entered one. "Pending" counter and seller tabs: sellers get no counter. Producer, condition and rating columns are toggleable and hidden by default (template A: at most 6 visible). The Glass model filter takes an id in a number field (target is the link); its chip shows the main Eurocode. Stat cards link to the matching tab (`?tab=`). Filter with fewer than 3 characters of text finds nothing (Catalogue's rule); helper text says so. The old test "a seller has no tabs" is replaced by one for no counter.
- **Seams left:** issue 11's "N ofert" link targets `?filters[glass_model][glass_model_id]=<id>` on the admin list.
- **Open questions:** none.
- **Check:** composer check:module offers,catalogue green (879 tests in the two modules, deptrac whole app clean). Worktree needed the ignored `public/build` copied in.

### Review — 2026-11-21
- **Verdict:** approved after 0 fix rounds. The manager made one change inline: `OfferQueries::buyersPriceSql` takes the Company value from `OwnerAccountType` instead of a string literal. The affected tests were re-run and are green.
- **Notes:**
  - **Buyer's price in SQL:** `buyersPriceSql` uses the remainder form, so it is exact on MySQL and SQLite. It is tested against `BuyersPricePolicy` on 15 prices × 7 rates × both Account types. It reads `users.account_type` through a correlated subquery (read-only, the same footing as the `owner` relation). The column sorts by it, and the price range filters on it.
  - **Accepted deviations:**
    - Catalogue is unchanged, because `GlassModelSummaries::ofMany` already existed. `OfferListGlass` (scoped) primes it once per page.
    - Producer, condition and rating columns are toggleable and hidden by default.
    - Sellers get no Pending counter.
    - Stat cards link to their tab.
    - The Glass model filter is a number field (the "N ofert" link is its real entry point).
  - **Seam for 11:** `?filters[glass_model][glass_model_id]=<id>` on the Offers list.
  - **Check:** the diff touches `resources/css/admin`, so the full `composer check` ran in the worktree: green, 1604 tests.
