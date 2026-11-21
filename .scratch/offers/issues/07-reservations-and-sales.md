# 07 - Reservations and Sales

Status: resolved
Blocked by: 05, 06

Spec: FR-16, FR-17, FR-18, FR-19, FR-20, RULE-05, RULE-06, RULE-09, RULE-10, §7 rows Reserved/Sold, §8 Reservation and Sale, FR-34 (Reservation, Sale by someone else), FR-32 page "Rezerwacje". Decisions: D-02, D-21, D-33, D-34, D-42, D-52.

## What

- **Reserve** an Active Offer (admin or owner) with a free-text "for whom" note; at most one current Reservation; shows author and time; no expiry.
- **Release** or **sell** a Reservation: an admin always; the owner unless an admin made it (visible but locked).
- **Sale** from Active or Reserved (admin; owner not over an admin's Reservation): channel (panel/direct, WooCommerce, OLX, other), sale price prefilled with the buyer's price and editable; records date, channel, entered price, VAT rate and buyer's price at that moment, and who marked it. **Return** (admin only, reason) → Active, the Sale stays marked returned.
- A Reserved Offer cannot be edited by its seller, withdrawn or deleted.
- Each step is indivisible and recorded in the Change history.
- Seller page "Rezerwacje"; Sales of own Offers on the view; permanent delete removes Reservations and Sales.
- Journal to the owner when someone else reserves, releases, sells or returns.

## Done when

- The seller-vs-admin Reservation lock, the one-Reservation rule, the recorded prices and the return are tested.
- `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** FR-16..FR-19, RULE-04/05/06/09/10, §7 Reserved/Sold. Status machine stays `Offer` (`reserve`, `release`, `sell`, `returnSale`, rules in `OfferDomainPolicy`: `canBeReserved/Released/Sold/Returned`, `reservationCanBeHandledBy`, `canBeEditedBySeller`; `canBeTrashed` now refuses Reserved, `canBeWithdrawn` already did). Reservation and Sale are their own entities with own ports/repositories/mappers/models/factories (they are ended/returned on their own and kept as history, so not inside the Offer aggregate, module-persistence); each handler (`ReserveOffer`, `ReleaseReservation`, `SellOffer`, `ReturnSale`) changes Offer plus Reservation/Sale in one command transaction (RULE-10), and Reservation/Sale are registered with Audit (observers, labels, `ValuePresenter`s) so the Change history records each step. RULE-05: unique `(offer_id, is_current)` index (`is_current` null once ended) plus handler fact; an admin's Reservation (`made_by_admin` kept as at making) locks the seller out of release and sell. Sale records sale price (editable, prefilled with the buyer's price), entered price, `VatRateSetting::current()` and the buyer's price via `BuyersPricePolicy` (RULE-08/09). A seller no longer edits a Reserved or Sold Offer (entity, `OfferResource::canEdit`); `DeleteOfferPermanently` removes Reservations and Sales first. Journal via `OwnerJournal` (reserved/released/sold/returned, never for own action). UI: view-page and row actions Zarezerwuj / Zwolnij rezerwację / Sprzedaj (gray) and Zwróć sprzedaż (in "⋮"); infolist sections "Rezerwacja" (author, time, note, lock notice) and "Sprzedaże" (seller: date, channel, price, returned; admin also recorded entered price, VAT rate, buyer's price, who marked, return reason); FR-32 page "Rezerwacje" (`ReservationResource`, read-only list of current Reservations on own Offers with author, admin sees all; in the Oferty sidebar group).
- **Deviations:** (1) A Sale stores a **sale price** (what the buyer paid, FR-18/FR-20) in addition to the §8 entered price, VAT rate and buyer's price snapshot; §8 lists no separate sale price but FR-18 and FR-20 need one. (2) Sale date is the moment the step is made (`sold_at`), not a date field in the form. (3) The Reservation note is required (max 500). (4) "Rezerwacje" lists current Reservations only (ended ones live in the Change history) and skips Offers in the Trash; the row opens the Offer, where release/sell happen. (5) Sold tab / list filters and stat cards are 10; the Sales show on the view page only. (6) A seller cannot edit a Sold Offer either (§3 lists Reserved or Sold). (7) Inquiry link on Reservation (FR-22) is not built: no column until 08. (8) Copied gitignored `public/build` into the worktree. (9) Existing tests changed: Reserved is excluded from the "any status goes to the Trash" tests (RULE-06).
- **Seams left:** 08: accepting an Inquiry creates a Reservation in the admin's name (`Reservation::make(... madeByAdmin: true ...)`, needs an `inquiry_id` column) and Inquiries close on reserve/sell/release (hook into the four handlers; `DeleteOfferPermanently` cascade); 09 bulk actions skip Reserved/Sold; 10 Sold/Reserved tabs and counts, stat cards "Reserved" and "sold this month" via `SaleModel` (`sold_at`, `returned_at`); 13 events `OfferReserved/Sold/...` (none emitted yet); 14 chart reads Sales not returned.
- **Open questions:** none
- **Check:** composer check green (1479 tests)

### Review — 2026-11-22
- **Verdict:** approved after 0 fix rounds
- **Notes:**
  - **Aggregates:** Reservation and Sale are their own aggregates; the `Offer` entity stays the status machine (`reserve`, `release`, `sell`, `returnSale`).
  - **Atomicity:** each handler changes both inside the command transaction (`WrapCommandInTransaction`).
  - **One current Reservation (RULE-05):** backed by a unique index on `(offer_id, is_current)`, where `is_current` is 1 or null.
  - **Seller lock:** `made_by_admin` is a snapshot taken when the Reservation is made, and it drives the lock.
  - **Accepted deviations:**
    - the Sale stores its own sale price (FR-18, FR-20 need it);
    - the sale date is the moment of the step;
    - the Reservation note is required, up to 500 characters;
    - "Rezerwacje" lists current Reservations only, outside the Trash.
  - **Change history:** Reservation and Sale are their own Audit types, with presenters and links to the Offer. The Offer's History tab shows only the status change; the Reservation and Sale rows appear in the global Change history.
  - **Seams:**
    - 08 links a Reservation to its Inquiry and adds Inquiries to the permanent-delete cascade;
    - 10 adds the Sold tab and the stat cards;
    - `OfferModel::currentReservation` and `sales` relations are ready for reads.
