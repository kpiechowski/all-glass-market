# 01 - Offers module and the Offer: create, edit, view, own lists

Status: ready-for-agent
Blocked by: catalogue 09 (the Glass model picker slide-over)

Spec: FR-01, FR-02, FR-03, FR-04, FR-05 (descriptions and private notes; photos are 02), FR-06 (Draft saving), FR-15, RULE-01, RULE-02, RULE-07, RULE-11, RULE-13, §2, §3, §8 Offer, §9 Catalogue and Audit. Decisions: D-01, D-02, D-08, D-09, D-10, D-16, D-17, D-24, D-25, D-29, D-38, D-40, D-46, D-49. ADR 0009.

## What

- The **Offers module** (skills: `module-layout`) with its Permissions, translations and navigation section "Oferty".
- The **Offer** as a Draft: owner (the creator, never changes), offer number "AG" + 8 digits (assigned once, unique), Glass model id **or** identification hint (never both, never glass parameters), producer (Catalogue's producer list), year, condition (Nowa / Używana / Outlet), rating 1–5 only for Używana and Outlet (Nowa stores none and is shown with five stars), public description, private notes, entered price.
- **One form**, no wizard, used by sellers and admins: "Szyba" section on top with "Wybierz model szyby" (catalogue 09's slide-over; the section then shows the model's card) and "Nie znam modelu" (hint field), then the piece, private notes, price. Saving keeps the Offer a Draft; sending for approval is 03.
- **View page**: the piece, the Glass model summary from Catalogue's module interface (current data, withdrawn models still shown), private notes only to the owner and admins.
- **Plain lists**: seller "Moje oferty" (own only), admin "Wszystkie oferty" (all). Tabs, filters and stat cards are 10.
- Register the Offer as auditable with Audit (Change history).
- A seller never sees another seller's Offer (lists, view, edit → refused).

## Done when

- A seller and an admin can create and edit a Draft with a Glass model or a hint; the owner is the actor; the number is assigned once.
- Refusals: Glass model and hint together; rating on Nowa; another seller's Offer.
- Test-first entity, value object and handler tests, mapper and repository tests, a Livewire test per page; `composer check` passes.
