# 06 - Glass model cards and the candidate search

Status: ready-for-agent
Blocked by: 04, 05

Spec: FR-04 (without the thumbnail, 07, and without the "N offers" link, 09), FR-05, WF-06 (search behaviour), §9 "Candidate search", §11 edge cases on codes and OEM. Decisions: D-14, D-30, D-41, D-49, D-53.

## What

- The Glass model list becomes **full-width cards**: main Eurocode, the other Eurocodes, OEM codes, position, colour, equipment, vehicle count, so results compare side by side (load `admin-ui-design`).
- **Search** by a fragment of any Eurocode, OEM code (RULE-04 comparison) or vehicle name/fields; filters position, colour, vehicle make; withdrawn models only on request.
- **Results order:** Eurocode hits, then OEM hits, then vehicle hits; each card says what it matched; a model matching by several routes appears once, under its best route. Choose minimum fragment length and result limit (V1: 3 characters, 20 results) and log the choice.
- Build the search once, so ticket 08 exposes the same behaviour to Offers.

## Done when

- FR-04 (within the limits above) and FR-05 behave as specified; the ordering, match labels, dedup and OEM comparison have tests; `composer check` passes.
