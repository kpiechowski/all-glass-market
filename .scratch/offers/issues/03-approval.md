# 03 - Approval: send, approve, reject, re-approval

Status: ready-for-agent
Blocked by: 02, catalogue 09 (in-place Glass model creation)

Spec: FR-06 (sending), FR-07, FR-08, FR-09, FR-10, FR-11, FR-13, RULE-03, RULE-04, RULE-14 (bulk approve), §7 rows Draft/Pending/Rejected/Active, FR-34 (approve, reject, admin change). Decisions: D-19, D-20, D-31, D-37, D-40, D-42, D-44, D-45, D-49.

## What

- Statuses **Draft, Pending approval, Rejected, Active** and only the §7 transitions between them (later issues add the others).
- **Send for approval** (seller): requires a Glass model or hint, entered price ≥ 1, condition, rating for Używana/Outlet, at least one public photo; refusal names the missing fields.
- **Approve** (admin, Pending or Rejected → Active, only with a Glass model) and **reject** (Pending or Active → Rejected, reason required). Bulk approve skips Offers without a Glass model and reports counts.
- In approval the admin opens the catalogue slide-over **prefilled from the hint** and may create a Glass model in place (catalogue 09); it is selected for the Offer.
- **Admin's own Offer:** Active on sending when it has a Glass model, else Pending.
- **Re-approval rule:** a seller's change of entered price, private notes or private photos keeps the status; any other change to an Active, Rejected (or later Withdrawn) Offer → Pending. An admin's edit never changes the status, in any status.
- Record first and last approval dates.
- **Journal** to the owner: approved, rejected (with reason), an admin changed their Offer.
- Pending tab with its counter (the queue); no bell to all admins.

## Done when

- Every transition and refusal above behaves as specified (approve without Glass model, reject without reason, seller price-only edit stays Active, photo edit → Pending).
- Test-first entity (status machine) and handler tests, Livewire tests for the actions; `composer check` passes.
