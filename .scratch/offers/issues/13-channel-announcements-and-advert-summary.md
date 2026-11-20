# 13 - Announcements for channels and the offer summary for adverts

Status: ready-for-agent
Blocked by: 07, 08

Spec: §10, RULE-12, §9 Catalogue events. Decisions: D-05, D-26, D-51. ADR 0010. Skill: `module-events`.

## What

- Public Offers events, each carrying the Offer id: became a candidate for adverts (approved, resumed, returned, released, restored), changed while a candidate (incl. buyer's price change from 05 and a Glass model change), reserved, sold, stopped being a candidate (rejected, back to Pending, withdrawn, deleted).
- React to Catalogue's *Glass model changed / withdrawn / restored* and *Producer changed / withdrawn* by announcing "changed while a candidate" for the affected Active/Reserved Offers.
- A module-interface read: the **offer summary for adverts** — offer number, Glass model summary, producer, year, condition, rating (Nowa flagged for five stars), public description, public photos in order, buyer's price, reservation flag. Candidates are Active or Reserved Offers.
- No channel code here; integrations consume this later.

## Done when

- Each transition raises the right event (tested); the summary is tested through the module interface; `composer check` passes.
