# 08 - Inquiries

Status: ready-for-agent
Blocked by: 07

Spec: FR-21, FR-21a, FR-22, FR-23, §7 Inquiry states, §8 Inquiry, FR-34 (Inquiry, answer, Outdated), FR-32 page "Zapytania". Decisions: D-22, D-35, D-41, D-65, D-66.

## What

- An admin sends an Inquiry about an **Active** Offer: optional channel, note; the sending admin is recorded; before sending the admin sees how many are open and from whom. Any number may be open.
- States Sent → Read (owner opens) → Accepted / Rejected; Sent or Read → **Outdated**.
- **Accept** (owner, only while Active): creates the Reservation with the note, in the name of the sending admin (so locked for the seller), linked to the Inquiry. **Reject**.
- Whenever an Offer stops being Active by any route (reserved, sold, withdrawn, deleted, rejected, sent back to Pending), its open Inquiries become Outdated.
- Journal: to the owner on a new Inquiry; to the sending admin on accept, reject, Outdated.
- Seller page "Zapytania"; admin list of all Inquiries; permanent delete removes them.

## Done when

- Accept creates an admin-locked Reservation; a second open Inquiry becomes Outdated; every exit from Active closes open Inquiries.
- `composer check` passes.
