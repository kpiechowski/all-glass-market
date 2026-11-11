# 08 - Catalogue's module interface for Offers

Status: ready-for-agent
Blocked by: 06, 07

Spec: FR-21, FR-22, FR-23, §9 (Offers), §10. Decisions: D-24, D-48, D-49, D-50, D-51, D-52, D-57. ADR 0008.

## What

- **Glass model summary** for one or many ids: main Eurocode, all Eurocodes, OEM codes, position, colour (or none), equipment and fitted vehicles as names, withdrawn flag, thumbnail and pictograms.
- **Candidate search** with the behaviour built in 06, returning candidates with their match label, and the matching model ids for filtering Offer lists.
- **Dictionary lists** of producers, positions and colours for picking and filtering; withdrawn values not offered, but their names readable by id.
- Check that the events of 01, 03, 04 and 05 are public and documented for listeners (integrations, Offers). Catalogue still knows nothing of Offers.

## Done when

- Each method has tests through the module interface only; `composer check` (deptrac included) passes.
