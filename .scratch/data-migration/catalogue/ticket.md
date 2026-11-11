# Catalogue — migrate V1 data

Status: needs-triage
Blocked by: Catalogue delivery (issues in `.scratch/catalogue/issues/`, not cut yet)

Spec: `.scratch/data-migration/catalogue/spec.md`. Review list: `.scratch/data-migration/catalogue/review-list.md`. Functional spec: `.scratch/catalogue/functional-spec.md` §8. Decisions: D-13, D-34, D-35, D-41, D-44–D-47, D-59–D-65 in `.scratch/catalogue/discovery/discovery.md`.

## What

A repeatable import of the V1 catalogue data into V2, as an Artisan command:

- **Glass models** built from V1 eurocodes joined through their offers (live and deleted), with main Eurocode, OEM codes, position, colour, equipment and fitment chosen by the spec's rules.
- **Vehicles** with the V1 name kept 1:1 and the structured fields parsed where certain.
- **Dictionaries:** positions, colours, equipment, producers, makes, bodies.
- A small **mapping file** read by the import for answers V1 cannot hold (review list C and D).
- A **run report**: every default applied, every dropped or unmigrated code, the counts of §5.
- **Hand-over for the Offers import:** the V1 offer → Glass model map, and the hint material (eurocode text, OEM, vehicle names) of offers left without a Glass model.

## Done when

- The command is idempotent (keys in spec §5) and tested against a fixture of V1 rows covering: a multi-code group, a join through a deleted offer, a parameter conflict, a code with no offer, an OEM field with several codes, an unparseable vehicle year.
- A dry run on the current V1 dump produces the counts of spec §4–§5 and the run report.

Status is `needs-triage`: the source connection (V1 database access from the V2 environment), the run procedure and the client's answers to the review list are not settled yet.
