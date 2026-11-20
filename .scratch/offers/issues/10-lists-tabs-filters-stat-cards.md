# 10 - Offer lists: tabs, filters, stat cards

Status: ready-for-agent
Blocked by: 08, 09

Spec: FR-31, FR-32. Decisions: D-06, D-47. Skill: `admin-ui-design`.

## What

- Admin "Wszystkie oferty": tabs Pending (counter), Active, Reserved, Sold, Withdrawn, Rejected, Drafts, Trash; filters Eurocode/OEM/vehicle text, position, colour (through Catalogue's `matchingIds`), "without Glass model", seller, producer, condition, rating, price range, Glass model (target of the "N ofert" link); bulk actions from 03, 06, 09.
- Seller "Moje oferty": the same tabs except Trash; bulk change price and withdraw.
- Above "Moje oferty": **custom stat cards with an icon** (not stock Filament widgets): own Active, Pending, Reserved, sold this month.
- Lists show the Glass model summary (main Eurocode, position, thumbnail) and buyer's price.

## Done when

- Tabs, counters and filters tested (Livewire); a seller never sees others' Offers; `composer check` passes.
