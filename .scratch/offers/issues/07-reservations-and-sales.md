# 07 - Reservations and Sales

Status: ready-for-agent
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
