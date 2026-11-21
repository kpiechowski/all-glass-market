# 09 - Duplicate and bulk actions

Status: resolved
Blocked by: 07

Spec: FR-14, FR-27, FR-29, RULE-07, RULE-14, FR-34 (admin changed the Glass model). Decisions: D-31, D-32, D-44, D-47, D-53.

## What

- **Duplicate** (owner or admin): a new Draft owned by **whoever duplicates** — an admin duplicating a seller's Offer owns the copy (V1 bug kept the old owner) — with the same Glass model or hint, description and entered price; no photos, private material, producer, year, condition or rating.
- **Bulk change price:** amount ± zł, percent ± %, fixed ≥ 1; skips Offers whose price would be ≤ 0 and Reserved/Sold ones; no re-approval; a seller only on own Offers.
- **Change Glass model** (admin), singly and in bulk, through the catalogue slide-over; status unchanged; Journal to each owner.
- **Bulk withdraw** (seller own, admin all) and **bulk delete** (admin) under the single-action rules.
- Every bulk action reports done and skipped counts.

## Done when

- Test: an admin duplicates a seller's Offer → the admin owns the copy. Bulk skips and counts tested.
- `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** FR-27/RULE-07 `DuplicateOffer` (copy is a Draft owned by whoever duplicates, admin included; same Glass model or hint, description, price; nothing else), tested for the admin case. FR-29/RULE-14 `ChangePriceOfOffers` (amount ± zł, percent ± %, fixed ≥ 1 zł via `PriceChange` VO; skips Reserved, Sold, trashed, unpriced, foreign and ≤ 0 results; never re-approval; admin change of another's Offer goes to the owner's Journal). FR-14 `ChangeGlassModelOfOffers` (admin; one command for the single and bulk action; re-checks the id through `GlassModelSummaries`; status unchanged; Journal `glass_model_changed` via `OwnerJournal`, none for own Offer). `WithdrawOffers` (seller own, admin all; each leaving Active fires 08's reaction, test shows Inquiries Outdated) and `DeleteOffers` (admin). All bulk handlers return `{done, skipped}`. UI: `DuplicateOfferAction`, `ChangeGlassModelAction` (row and view page), bulk actions for price, Glass model (picker slide-over), withdraw, delete, with a shared `BulkActionResult` notification; seller sees only price and withdraw.
- **Deviations:** Bulk results use `done`/`skipped` keys (not `approved` as in 04). Price change is its own entity method `Offer::changePrice` (not `edit()`), since `edit()` would send a never-approved Withdrawn Offer to Pending. `Offer::changeGlassModel` also is its own method. Duplicating an Offer whose Glass model was withdrawn since is refused (glass_model_cannot_be_picked); duplicating a trashed Offer is refused. A Glass-model change to the model the Offer already has counts as skipped. Percent travels in the command as hundredths of a percent, amounts in grosze.
- **Seams left:** filters ("Glass model" from the "N ofert" link), seller tabs and stat cards (FR-31/32) are other tickets; the bulk actions already sit in the shared table.
- **Open questions:** none. No new audited field, so no new `ValuePresenter`.
- **Check:** composer check green (1586 tests). Note: the worktree had no public/build, so four Vite-manifest tests failed until I copied the main checkout's ignored `public/build` into it.

### Review — 2026-11-22
- **Verdict:** approved after 0 fix rounds
- **Notes:**
  - **Duplicate:** the copy's owner is whoever duplicates, an admin included (user decision, D-32, tested). It copies no photos, so no file is shared between Offers.
  - **Entity methods:** `Offer::changePrice` and `changeGlassModel` are their own methods. They do not go through `edit()`, because a price change never re-approves (FR-29) and an admin's Glass model change keeps the status (FR-14).
  - **Bulk handlers:** each one returns `['done', 'skipped']` and reuses the single-action entity methods. A refused Offer, a foreign Offer and a no-op count as skipped. Bulk withdraw outdates Inquiries through 08's reaction (tested).
  - **Accepted deviations:**
    - duplicating a trashed Offer, or one whose Glass model was withdrawn since, is refused;
    - choosing the same model counts as skipped;
    - a percent travels in hundredths of a percent;
    - approve keeps its own result keys from 04.
