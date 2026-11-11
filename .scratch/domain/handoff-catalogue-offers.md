# Handoff: Catalogue and Offers, from the domain grilling to delivery

- **Written:** 2026-11-09, at the end of the domain grilling.
- **For:** the next manager session (Opus). The user talks Polish; documents are in English.
- **Purpose:** continue the agreed flow from this file alone.

## Update 2026-11-11 (Catalogue D-66)

Step 2 is done: `.scratch/catalogue/functional-spec.md`, issues `.scratch/catalogue/issues/01–09`, data migration in `.scratch/data-migration/catalogue/`. The user changed the order: **Catalogue delivery (step 4) comes before the Offers grilling (step 3)**. Catalogue issue 09 (picker, approval, offer counts, in-use guard) waits for the Offers spec. If Offers needs more from Catalogue's `ModuleApi`, add it then and record a `D-` in both discoveries.

## Where we are

- Vehicles and Offers discoveries are done (`ready-for-grilling`).
- The domain grilling is **done**. Its output is listed below.

| Output | Contents |
|---|---|
| `.scratch/domain/glass-offer-model.md` | Decisions **D-01–D-28**, each with its reasons and the discovery ids it affects. Also the topics deferred to the module grillings, and ADD-A01 (Accessory model, waiting for the client). |
| `GLOSSARY.md` | Sections *Glass catalogue*, *Sales channels*, *Offers*. |
| `docs/adr/` | 0008 (Glass model known by several Eurocodes), 0009 (an Offer is one physical piece), 0010 (the panel only publishes to channels). |
| `.scratch/roadmap.md` | Revised: Catalogue replaces Vehicles, Submissions is dropped, OLX replaces Otomoto. |

The model in five lines (the full version is in the decisions file):

- **Catalogue** (one module) holds **Glass models**. Each Glass model has one or more **Eurocodes** (exactly one of them main), OEM codes as lookup keys, vehicle fitment, and the parameters position, colour and equipment. It also holds the dictionaries (position, colour, equipment, producer). Only admins maintain it.
- An **Offer** is one physical **Glass** put up for sale: producer, year, condition, rating, photos, price, seller, status. It refers to a Glass model and never overrides it. Without a Glass model it carries only an **identification hint**, and it cannot be approved until it has one.
- **Approval**, **Reservation**, **Sale** and **Inquiry** belong to the Offer. There is no Submission and no quantity.
- Channels hold **Adverts**. The panel only publishes and never pulls back.
- Offers reads Catalogue only through Catalogue's `ModuleApi`. Catalogue knows nothing of Offers.

## The flow (D-28)

V1 has **no catalogue**, so Catalogue gets **no new discovery**. Its "discovery" is the Vehicles discovery plus the Offers discovery plus the domain model. Run the work in this order and do not skip ahead:

```
1 carry decisions into the discoveries
2 Catalogue module grilling   → .scratch/catalogue/functional-spec.md (+ data-migration spec/ticket)
3 Offers module grilling      → .scratch/offers/functional-spec.md    (+ data-migration spec/ticket)
4 Catalogue issues → delivery (module-delivery)
5 Offers issues → delivery
6 WooCommerce (publish only), later OLX
```

### Step 1 — Carry the decisions into the discoveries

You are now the discovery manager, the only writer of the discovery documents.

1. `git mv .scratch/vehicles .scratch/catalogue`. In `discovery.md`, rename the module "Vehicles (working name)" to **Catalogue**. Add a line to section A: inputs are this discovery, the Offers discovery and the domain model. Move `domain-grilling-prompt.md` to `.scratch/domain/` (it is history now). Fix every path that points at `.scratch/vehicles/`: the roadmap, the domain model and the Offers discovery. `grep -rn "scratch/vehicles" .scratch docs` finds them.
2. In **both** discoveries:
   - Add to section M one entry per domain decision that touches the module, citing it (e.g. "D-03 (domain) — correct: …"), with the class `preserve`, `correct`, `new` or `exclude` (see `.claude/skills/module-discovery/grilling.md`).
   - Set the **Resolution** of each `Q-`, `IMP-` and `ADD-` item that the domain decisions settle (their ids are listed under every D-NN).
   - Leave the deferred topics open. They are listed in `glass-offer-model.md` → "Deferred to the module grillings".
3. Set the status of both discoveries to `grilling`.
4. Show the user the diff summary and commit explicit paths after they accept.

### Step 2 — Catalogue module grilling

- Load the `module-discovery` skill and follow **Phases 6–9 only** (`.claude/skills/module-discovery/grilling.md`), on `.scratch/catalogue/discovery/discovery.md`.
- **Settled ground.** D-01–D-28 are decided. Do not re-ask them. Challenge one only when a new fact contradicts it, and say which decision and fact.
- **Adjusted design tree.** Purpose and boundaries are settled (D-23, D-24, D-25). So:
  1. Start with the inventory. Map each Vehicles `F-` item onto the new model (eurocode register → Glass models with Eurocodes; dictionaries; vehicles; fitment) and decide keep, change or drop.
  2. Then the rules and workflows the model brings that V1 never had: creating a Glass model, adding or moving a Eurocode, merging two Glass models, assigning a Glass model to an Offer during Approval (as seen from Catalogue).
  3. Then the `Q-` and `G-` items and the Catalogue entries of the deferred list.
  4. Then **the contract Offers will use** (spec section 9): the Glass model summary, the search by eurocode, OEM or vehicle returning candidates, and the domain events (e.g. Glass model changed). Keep it at the functional level. Classes come later from the architecture skills.
- **Facts are your job.** Use the discovery reports, the production dump (`allglass_v1_prod` in `laradock8_5-mysql-1`, aggregate queries only, no personal data) and, if needed, one narrow `legacy-investigator` mission.
- **Phase 9 data migration** must cover:
  - the admin review list (D-09 conflicts, D-13 suffix-different pairs);
  - joining alternative eurocodes into Glass models, transitively (D-13);
  - folding `car_offer` (D-15) and `offers.oem` (Vehicles Q-16);
  - code normalisation.
- **ADD-A01** (Accessory model) stays out of the spec unless the user reports that the client wants it.

### Step 3 — Offers module grilling

- Same procedure on `.scratch/offers/discovery/discovery.md`.
- The Catalogue spec's section 9 is the contract. If Offers needs more from Catalogue, amend the Catalogue spec and record a `D-` in both documents.
- Offers deferred topics are in the domain model's deferred list (form shape, re-approval scope, statuses, price and VAT at sale, reservations, Inquiry, expiry, notifications, shipping).
- Phase 9 must cover:
  - splitting the 16 offers with quantity > 1 (D-02);
  - mapping V1 statuses;
  - dropping Submissions (D-20) and keeping what the admin needs;
  - turning imported sales and reservations into plain Sales and Reservations (ADR 0010).

### Steps 4–5 — Issues and delivery

- **Issues:** cut them from each spec into `.scratch/<module>/issues/`, following `docs/agents/issue-tracker.md` and `docs/agents/triage-labels.md`.
- **Delivery:** the user runs `/module-delivery` ("deliver catalogue", later "deliver offers"). It is user-invoked.
- **Catalogue first:** Offers code depends on Catalogue's `ModuleApi`.

## Rules that still hold

- No V2 code for a module before its spec exists (CLAUDE.md).
- Git:
  - Never `git add -A`, `stash`, `checkout` or `reset`.
  - Commit explicit paths, and only after the user accepts.
  - Branch `catalog` holds this work (renamed from `feat/vehicles` on 2026-11-11, after `feat/vehicles` was merged into `main`). Ask the user before renaming it or branching.
- Commands in the container run as `laradock` (uid 1000).
- Safe points to `/clear` and resume: after each step, and after each grilling round (the discovery's "Next actions" stays current).

## Start prompts for the user

- Step 1: `Przeczytaj .scratch/domain/handoff-catalogue-offers.md i wykonaj krok 1.`
- Step 2: `Przeczytaj .scratch/domain/handoff-catalogue-offers.md i zacznij krok 2: grillowanie modułu Catalogue.`
- Step 3: `Przeczytaj .scratch/domain/handoff-catalogue-offers.md i zacznij krok 3: grillowanie modułu Offers.`
