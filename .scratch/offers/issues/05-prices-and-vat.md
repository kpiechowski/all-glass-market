# 05 - Buyer's price and the VAT rate setting

Status: resolved
Blocked by: 01

Spec: FR-28, FR-30, RULE-08, §9 Users. Decisions: D-33, D-54.

## What

- **Buyer's price** = entered price for a Company owner; entered price × (1 + VAT rate), rounded to grosze, for a Private owner; always by the owner's current Account type (Users). Shown on lists and the view next to the entered price.
- **VAT rate** setting (initially 23%), changed only by a Super Admin, in Offers' settings (ADR 0007).
- React to Users' *Account type changed* and to a VAT rate change by announcing that the affected Offers' buyer's price changed (the announcement itself is consumed in 13; here the Offers-side event exists and is raised).

## Done when

- Price rule tested for both Account types and a rate change; only a Super Admin can change the rate.
- `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** FR-28, RULE-08: `BuyersPricePolicy::buyersPriceGrosze` (Domain, the one place; integer arithmetic, half-up to grosze, Company = entered price) over `VatRate` (basis points, 0..100%, initial 23%) and Offers' own `OwnerAccountType`; tested for Company, Private, rounding, fractional rate and a rate change. Buyer's price is derived, never stored: `BuyersPrices` ModuleApi (`forOffer`, batch `prime(ownerIds)`; scoped), fed by new Users ModuleApi `AccountTypes::of / ofMany` (one read for many ids, port method `UserRepository::accountTypesOf`). Shown on the list (column "Cena dla kupującego" and card meta; `ListOffers::getTableRecords` primes once per page) and on the view page. FR-30: VAT rate as a typed Offers setting (ADR 0007): table `offer_settings` (one row, no row = 23%), `VatRateSetting` port + adapter, `ChangeVatRateCommand` guarded by new `OffersPermission::ChangeVatRate` (granted to no Role, so only Super Admin's wildcard holds it), settings page "Ustawienia ofert" (Template D, Super Admin only, sidebar under Oferty). Domain event `OfferBuyersPriceChanged(offerId, previous, new, changedAt)`, raised by `ChangeVatRate` (all priced Offers whose owner is Private) and by `AnnounceBuyersPriceChanges` command, sent by listener `AnnounceBuyersPricesOnAccountTypeChange` on Users' `AccountTypeChanged`.
- **Deviations:** (1) Event is raised for Offers in any status that have an entered price; the consumer in 13 filters candidates (Offers has no Reserved yet). (2) The listener sends a command as `Actor::system(...)` that only emits events (rule: every event comes from a handler). (3) The VAT rate change is not registered with Audit (no history of the rate) and has no own event; add if the user wants it audited. (4) Saving the same rate is a no-op. (5) The Offer's Filament form still shows only the entered price. (6) Copied gitignored `public/build` into the worktree.
- **Seams left:** 13 consumes `OfferBuyersPriceChanged` and reads `BuyersPrices::forOffer`; Sale (RULE-09) can record `VatRateSetting::current()` and the buyer's price at sale; filters/sorting by buyer's price (10) would need a SQL expression, not built.
- **Open questions:** should a VAT rate change be audited (Change history for the setting)?
- **Check:** composer check green (1337 tests)

### Review — 2026-11-21
- **Verdict:** approved after 0 fix rounds
- **Notes:** RULE-08 lives only in `BuyersPricePolicy::buyersPriceGrosze` (integer, half-up, `VatRate` in basis points). Buyer's price is derived: `BuyersPrices` ModuleApi (scoped) with `prime(ownerIds)` once per list page over Users' new `AccountTypes::ofMany`. VAT rate in `offer_settings` (no row = 23%), changed by `ChangeVatRateCommand` under `OffersPermission::ChangeVatRate` (no Role holds it → Super Admin only), page "Ustawienia ofert". `OfferBuyersPriceChanged` is raised for priced Offers in **any** status — 13 must filter to candidates. Open for the user: should a VAT rate change be audited? Not built.
