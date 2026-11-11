# 04 - Eurocodes of a Glass model: add, move, remove, change main

Status: resolved
Blocked by: 03

Spec: FR-03, WF-02, RULE-01, RULE-02, RULE-03; FR-04 sentence "A Eurocode has no screen of its own". Decisions: D-03, D-04, D-36, D-39, D-41. GLOSSARY: Eurocode, Main eurocode.

## What

- On a Glass model: **add** a Eurocode (normalised; refused when another model holds it, naming that model), **move** one to another model (active or withdrawn; it joins as non-main), **remove** one (it leaves the catalogue), **change the main** one.
- Refusals: moving or removing the last Eurocode; moving or removing the main one until another is main.
- Every affected model announces *Glass model changed*.
- Further Eurocodes may also be given when creating a model (FR-01).

## Done when

- WF-02 with every refusal is covered by entity and handler tests; a Livewire test for the Eurocode management on the model page; `composer check` passes.

### Worker — 2026-11-11
- **Built:** WF-02 / FR-03 / RULE-01..03. Entity `GlassModel`: `addEurocode` (taken by another model refused, naming it; already held = no change), `removeEurocode` (refuses the last and the main one), `changeMainEurocode`, `receiveEurocode`. Commands AddEurocode, RemoveEurocode, ChangeMainEurocode, MoveEurocode (one command and transaction: source releases, target receives, both saved, *Glass model changed* for both; target may be withdrawn; same model refused). Add/change main announce only when something changed. CreateGlassModelCommand takes `furtherEurocodes` (FR-01; a taken one refuses the whole creation). Model page: "Eurokody" action group (add, change main, move, remove), Eurocodes badge list in the infolist, "Dalsze eurokody" tags field on create. `GlassModelQueries::moveTargets()` for the target search.
- **Deviations:** (1) Remove/move/change-main actions are hidden when the model has one Eurocode (the handler still refuses). (2) Moving is picked on the source model's page ("push"), target found by a fragment of any of its Eurocodes. (3) The edit page keeps the main Eurocode locked, with the existing helper text pointing to the model page.
- **Seams left:** none (06's cards can show `eurocodes` of the model).
- **Open questions:** none
- **Check:** composer check green (955 tests)

### Review — 2026-11-11
- **Verdict:** approved after 0 fix rounds
- **Notes:** `GlassModel::addEurocode / removeEurocode / receiveEurocode / changeMainEurocode` hold WF-02's rules; `MoveEurocodeCommandHandler` saves source then target in one command (the bus transaction) and announces both. Create takes `furtherEurocodes`; a taken one refuses the whole creation. `GlassModelQueries::moveTargets()` finds a target by a fragment of any Eurocode. Accepted deviations: remove/move/change-main actions hidden for a single-Eurocode model (the handler still refuses); a move starts from the source model; the edit page keeps Eurocodes read-only (managed on the view page).
