# 05 - Fitment of vehicles to Glass models

Status: ready-for-agent
Blocked by: 02, 03

Spec: FR-09 (count of Glass models a vehicle fits), FR-10, FR-11 (warning and fitment removal), WF-05, RULE-10, §9 event fan-out for vehicle changes. Decisions: D-11, D-15, D-44, D-51. GLOSSARY: Fitment.

## What

- An admin fits vehicles to a Glass model and removes fitments, from the Glass model. A fitment records no reason or source.
- The vehicle list shows how many Glass models each vehicle fits.
- Deleting a vehicle that fits models: warning with the count, then the vehicle and its fitments go (WF-05).
- *Glass model changed* for the model on fitment changes, and for every model a vehicle fits when that vehicle is changed or deleted.

## Done when

- FR-10, FR-11 and WF-05 behave as specified, with handler tests for the fan-out; Livewire tests for the fitment editing and the delete warning; `composer check` passes.
