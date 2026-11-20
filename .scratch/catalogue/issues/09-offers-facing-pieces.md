# 09 - Pieces that need Offers: picker, approval, offer counts, in-use guard

Status: needs-triage
Blocked by: 08, and the Offers module (spec and delivery; D-66)

Spec: FR-19, FR-20, FR-04 "N offers" link, FR-24 (a Glass model or producer referred to by an offer is in use), WF-06 (picker), §9 last bullet. Decisions: D-17, D-19, D-37, D-38, D-44, D-52.

## What

- The seller's Glass model picker in the offer form, as a dedicated search modal with cards and thumbnails (FR-19).
- Creating a Glass model in place during an offer's approval, main Eurocode prefilled from the identification hint (FR-20).
- The "N offers" link on Glass model cards (D-37).
- The guard: a Glass model or producer that an offer refers to cannot be deleted (FR-24), without Catalogue depending on Offers.

Waits for the Offers grilling and spec (D-66). The Offers spec decides the form shape and where the guard lives; triage this issue then.

## Done when

- Set at triage.

## Comments

- 2026-11-20 (Offers grilling): the Offers spec is written (`.scratch/offers/functional-spec.md`). Inputs for triage: the picker is a **slide-over** with the catalogue card list (Catalogue D-67, FR-19 amended; Offers D-38, FR-02); in-place creation during approval (Offers FR-10, D-44); "N ofert" counts Offers outside the Trash in every status and links to the filtered offer list (Offers FR-33, D-48); the in-use guard counts every Offer Offers keeps, Trash included (Offers FR-25, D-43). Offers also needs bulk "change Glass model" (Offers FR-14), which uses the picker. No new Catalogue module interface needed beyond §9.
