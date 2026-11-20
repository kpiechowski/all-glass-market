# 04 - History tab and "Zmiany od ostatniego zatwierdzenia"

Status: ready-for-agent
Blocked by: 03

Spec: FR-12, §9 Audit. Decisions: D-20, D-39.

## What

- Admin's offer view: a **History** tab with the Offer's Change history (Audit module interface).
- On a Pending Offer that was approved before: a block "Zmiany od ostatniego zatwierdzenia" listing each changed field (and photo/Glass model change) with old → new value, from the Change history since the last approval. Nothing for an Offer never approved.
- Sellers see no history.

## Done when

- The block shows exactly the changes after the last approval; absent for a new Offer; hidden from sellers.
- Tests through the Audit module interface and a Livewire test; `composer check` passes.
