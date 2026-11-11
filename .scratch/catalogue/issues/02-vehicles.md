# 02 - Vehicles

Status: ready-for-agent
Blocked by: 01

Spec: FR-07, FR-08, FR-09 (list and search; the "Glass models it fits" count comes with 05), FR-11 (delete; the fitment warning comes with 05), RULE-09, RULE-11, §8 Vehicle. Decisions: D-31, D-34, D-44.

## What

- **Vehicle** with name, make (make dictionary, required), model, body (body dictionary), years from–to (to may be open, from ≤ to), generation, ARGIC vehicle code (four digits). No slug.
- **Name:** required, unique ignoring case, editable; for a new vehicle the system proposes it from the fields in the FR-08 format, and the admin may change it. Never rewritten afterwards.
- **List and search** by name and fields; filters by make and body.
- **Delete:** allowed (no fitment exists yet); 05 adds the warning and fitment removal.
- Register the vehicle's use of make and body values with ticket 01's delete seam.

## Done when

- FR-07, FR-08, FR-09 (without the count) and FR-11 (without fitment) behave as specified; RULE-09 refusals tested.
- Handler tests, a Livewire test per page, `composer check` passes.
