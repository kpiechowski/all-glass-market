# 07 - Glass model thumbnail and catalogue settings

Status: ready-for-agent
Blocked by: 06

Spec: FR-15, FR-16, FR-17, FR-18, RULE-12, §13 ADD-06, FR-04 thumbnail column. Decisions: D-54, D-55, D-56, D-57. ADR 0007 (settings belong to the module).

## What

- A shipped set of glass shapes (windscreen, rear, side drop, side fixed/body, roof, accessory, unknown) as SVG, and a shipped set of equipment pictograms plus a universal one.
- **Catalogue settings** page (admins): position → shape + mirrored; colour → fill colour + opacity 0–100%; equipment → pictogram. No upload of custom shapes. A mapping change changes no Glass model and raises no event.
- The thumbnail drawn from those mappings, with the equipment pictograms in a row, each with a tooltip of the full value name; defaults for anything unmapped (FR-18).
- Shown on the Glass model cards of 06.

## Done when

- FR-15–FR-18 behave as specified, the defaults included; tests for the drawing rules and the settings page; `composer check` passes.
