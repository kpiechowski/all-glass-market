# 03 - Glass models: create, edit, withdraw, delete

Status: ready-for-agent
Blocked by: 01

Spec: FR-01, FR-02, FR-06, FR-14 (fan-out to Glass models), FR-24 (Glass models, uses inside Catalogue), FR-25 (Glass model view), WF-01, WF-03, WF-04 (Glass models), RULE-01, RULE-02, RULE-03, RULE-04, RULE-05, RULE-06, §7, §8 Glass model, §9 events. Decisions: D-01, D-03, D-04, D-07, D-09, D-14, D-35, D-39, D-41, D-43, D-44, D-51. ADR 0008.

## What

- **Glass model** with its main Eurocode (normalised per RULE-03, unique across the catalogue including withdrawn models), position (required), colour (optional), equipment (set), OEM codes (RULE-04: stored uppercase and trimmed, compared ignoring spaces, dashes and dots, once per model) and the withdrawn flag.
- **Create** (WF-01) and **edit** (WF-03), also while withdrawn; a withdrawn colour or equipment value already on the model survives a save but cannot be newly chosen.
- **Withdraw, restore, delete-unused** (WF-04). Nothing outside Catalogue uses a model yet; leave the seam that 09 fills with the offers' use.
- **Events** *Glass model changed* (on every change, not on creation), *withdrawn*, *restored*; and the fan-out of FR-14: renaming a position, colour or equipment value announces every Glass model using it.
- Register the model's use of position, colour and equipment with ticket 01's delete seam.
- **A plain list** of Glass models (main Eurocode, position, colour) and a view with the FR-25 extension point. Cards, search and filters come with 06. Only the main Eurocode is entered here; ticket 04 adds further Eurocodes.

## Done when

- WF-01, WF-03, WF-04 and their refusals behave as specified (taken Eurocode names the model holding it; empty or over-long code; missing position; delete while in use).
- Test-first entity and handler tests, mapper and repository tests, a Livewire test per page, `composer check` passes.
