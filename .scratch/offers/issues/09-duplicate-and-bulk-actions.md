# 09 - Duplicate and bulk actions

Status: ready-for-agent
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
