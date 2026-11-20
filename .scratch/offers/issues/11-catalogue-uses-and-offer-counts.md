# 11 - Glass models and producers in use; "N ofert" on Glass model cards

Status: ready-for-agent
Blocked by: 06

Spec: FR-25 (uses), FR-33, §9 Catalogue (consumer). Catalogue FR-24, D-37. Decisions: D-43, D-48.

## What

- Offers registers with Catalogue's seams (e.g. `GlassModelUsages`, `DictionaryValueUsages`) that a Glass model or a producer is **in use** while any Offer refers to it, Trash included; Catalogue then refuses to delete it (it may be withdrawn). Catalogue keeps knowing nothing of Offers.
- The **"N ofert"** count on Catalogue's Glass model cards: Offers outside the Trash, every status; links to "Wszystkie oferty" filtered by that model (10's filter; until 10, the plain list with the filter parameter). Mechanism through a Catalogue seam, decided at triage with the architecture skills.

## Done when

- Deleting a Glass model or producer used by a trashed Offer is refused; the count excludes the Trash; deptrac shows no Catalogue → Offers dependency; `composer check` passes.
