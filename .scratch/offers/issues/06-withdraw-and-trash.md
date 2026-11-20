# 06 - Withdraw, resume, delete, Trash, restore, delete permanently

Status: ready-for-agent
Blocked by: 03

Spec: FR-24, FR-25, FR-26, §7 rows Withdrawn and Trash, FR-34 (withdrawn or deleted by an admin), RULE-06. Decisions: D-30, D-42, D-43, D-67.

## What

- Status **Withdrawn**: owner or admin withdraws a Pending, Rejected or Active Offer. "Wznów sprzedaż" → Active when approved before and unchanged since except entered price and private material; an admin's own Offer resumes whenever it has a Glass model; otherwise editing sends it to Pending (03's rule extended to Withdrawn).
- **Delete** (admin only, any status except Reserved — the guard is completed in 07): the Offer goes to the **Trash**, keeps its status, leaves every list except the admin's Trash tab. **Restore** returns it with its status.
- **Delete permanently**, only from the Trash: removes the Offer with its photos and everything belonging to it (07 and 08 add Reservations, Sales, Inquiries to the cascade).
- Journal to the owner when an admin withdraws or deletes their Offer.

## Done when

- Transitions and refusals tested (resume of a changed Offer refused; seller delete refused; permanent delete outside the Trash refused).
- `composer check` passes.
