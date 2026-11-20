# 02 - Public and private photos

Status: ready-for-agent
Blocked by: 01

Spec: FR-05 (photos), §8 Offer photos. Decisions: D-29, D-49.

## What

- Up to **10 public photos**, ordered; the first is the main photo; the order changes by dragging.
- Up to **10 private photos**, visible only to the owner and admins, never part of anything published.
- Photos travel in command data (golden rule 5); the form section "Zdjęcia" and the gallery on the view page.
- Public photos are part of the Offer's data that 03's re-approval rule watches; private photos are not.

## Done when

- Limits refused above 10 per kind; order kept; a seller cannot see another Offer's private photos.
- Handler and Livewire tests; `composer check` passes.
