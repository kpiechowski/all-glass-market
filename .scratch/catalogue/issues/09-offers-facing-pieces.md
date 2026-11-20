# 09 - Glass model picker slide-over and in-place creation (for Offers)

Status: ready-for-agent
Blocked by: 08

Spec: FR-19 (amended 2026-11-20), FR-20, WF-06, WF-01 (from approval). Decisions: D-17, D-19, D-38, D-52, D-57, D-67. Offers spec: FR-02, FR-10, FR-14 (`.scratch/offers/functional-spec.md`).

## What

- A reusable **Glass model picker**: a slide-over holding the catalogue card list with its search and filters (the same cards as the admin list, issues 06 and 11), in a seller variant (no "N ofert", no withdrawn models) and an admin variant. The user marks one card and confirms at the bottom; the picker hands the chosen Glass model id back to the form or action that opened it. It can open prefilled with a search text (Offers' identification hint).
- **In-place creation** for admins from the picker (FR-20): create a Glass model (WF-01) with the main Eurocode suggested from the prefill text, normalised by RULE-03, for the admin to confirm or correct; the new model is returned as the choice.
- Catalogue still knows nothing of Offers: the picker is a UI element of Catalogue that Offers' form and actions embed. A small demo use (e.g. on a styleguide or test page) is enough to test it before Offers exists.
- Moved out of this issue (2026-11-20): the in-use guard and the "N ofert" link → Offers issue 11.

## Done when

- Seller variant hides withdrawn models and counts; confirming returns the id; prefill searches; admin in-place creation returns the new model and refuses a taken Eurocode naming its model.
- Livewire tests for the picker; `composer check` passes.

## Comments

- 2026-11-20 (Offers grilling): the Offers spec is written (`.scratch/offers/functional-spec.md`). Inputs for triage: the picker is a **slide-over** with the catalogue card list (Catalogue D-67, FR-19 amended; Offers D-38, FR-02); in-place creation during approval (Offers FR-10, D-44); "N ofert" counts Offers outside the Trash in every status and links to the filtered offer list (Offers FR-33, D-48); the in-use guard counts every Offer Offers keeps, Trash included (Offers FR-25, D-43). Offers also needs bulk "change Glass model" (Offers FR-14), which uses the picker. No new Catalogue module interface needed beyond §9.
