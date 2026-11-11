# 04 - Eurocodes of a Glass model: add, move, remove, change main

Status: ready-for-agent
Blocked by: 03

Spec: FR-03, WF-02, RULE-01, RULE-02, RULE-03; FR-04 sentence "A Eurocode has no screen of its own". Decisions: D-03, D-04, D-36, D-39, D-41. GLOSSARY: Eurocode, Main eurocode.

## What

- On a Glass model: **add** a Eurocode (normalised; refused when another model holds it, naming that model), **move** one to another model (active or withdrawn; it joins as non-main), **remove** one (it leaves the catalogue), **change the main** one.
- Refusals: moving or removing the last Eurocode; moving or removing the main one until another is main.
- Every affected model announces *Glass model changed*.
- Further Eurocodes may also be given when creating a model (FR-01).

## Done when

- WF-02 with every refusal is covered by entity and handler tests; a Livewire test for the Eurocode management on the model page; `composer check` passes.
