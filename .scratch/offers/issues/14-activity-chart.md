# 14 - Activity chart

Status: ready-for-agent
Blocked by: 10

Spec: FR-36, FR-37, §13 ADD-05. Decisions: D-58. Skills: `admin-ui-design`.

## What

- One line chart, last 12 months, two lines: "Dodane do sprzedaży" (Offers first approved that month) and "Sprzedane" (Sales that month, returned ones excluded); no money; Trash excluded.
- Seller: own Offers, on "Moje oferty" under the stat cards, collapsible. Admin: all Offers above "Wszystkie oferty", filterable by seller.
- Zeros when there is no history.

## Done when

- Monthly counts tested (a returned Sale, a trashed Offer, a month with nothing); `composer check` passes.
