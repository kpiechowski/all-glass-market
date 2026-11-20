# 12 - Offers of a deactivated seller

Status: ready-for-agent
Blocked by: 08

Spec: FR-35, WF-09, §7 rows to Withdrawn by System. Decisions: D-50, D-66.

## What

- On Users' *User deactivated*: the seller's Active, Pending and Draft Offers → Withdrawn by the System ("seller deactivated"); Reserved stay; open Inquiries on the withdrawn Offers become Outdated; one Journal entry to the owner.
- *User reactivated* restores nothing.

## Done when

- Listener tested with a mix of statuses; `composer check` passes.
