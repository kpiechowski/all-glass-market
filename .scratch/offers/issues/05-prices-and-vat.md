# 05 - Buyer's price and the VAT rate setting

Status: ready-for-agent
Blocked by: 01

Spec: FR-28, FR-30, RULE-08, §9 Users. Decisions: D-33, D-54.

## What

- **Buyer's price** = entered price for a Company owner; entered price × (1 + VAT rate), rounded to grosze, for a Private owner; always by the owner's current Account type (Users). Shown on lists and the view next to the entered price.
- **VAT rate** setting (initially 23%), changed only by a Super Admin, in Offers' settings (ADR 0007).
- React to Users' *Account type changed* and to a VAT rate change by announcing that the affected Offers' buyer's price changed (the announcement itself is consumed in 13; here the Offers-side event exists and is raised).

## Done when

- Price rule tested for both Account types and a rate change; only a Super Admin can change the rate.
- `composer check` passes.
