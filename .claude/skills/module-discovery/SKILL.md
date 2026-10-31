---
name: module-discovery
description: "Discovery of a V1 (legacy AllGlass) module before any V2 work: map what the module really does across the whole legacy repo with investigator subagents, grill the user on it, and produce an agreed functional spec. Use when the user says \"start/continue discovery for module X\", asks what a V1 module actually does, or wants a functional spec for porting a module."
---

# Module discovery

You are the **discovery manager**. You turn a V1 module into an agreed **functional spec**: what the module does and should do, written for a reader who never saw V1. You do not read the legacy code end-to-end yourself. You plan missions, send them to `legacy-investigator` subagents, judge their reports, and own the working document.

The spec describes **behaviour, never implementation**. V2 architecture (layers, CQRS, repositories, Filament classes) is decided in a separate process, so the V2 architecture skills play no part here. The same goes for V1's class structure: it is evidence of behaviour, not a design to keep.

## Fixed facts

- **V1 root:** `/home/kp/laravel/projekty/AllGlass/`. Read-only. V1 has almost no module directories (`app/Models`, `app/Filament`, `app/Services`… are shared), so a module's scope is the set of **anchors** and **vocabulary** you define in Phase 1, never a folder.
- **Artifacts**, all under `.scratch/<module>/` (lower-kebab module name):

  ```
  .scratch/<module>/
    discovery/
      discovery.md          the working document; only you write it
      reports/rNN-<mission>-<slug>.md   one per mission; only its investigator writes it
    functional-spec.md      the final deliverable (Phase 8)
  ```

- **Evidence tags.** Every finding carries exactly one: `confirmed` (code, config, migration or test shows it), `observed` (an execution path was traced end-to-end, or V1 was run), `inferred` (plausible, not proven), `ambiguous` (more than one reading fits), `unknown` (could not be established), `defect?` (looks wrong or inconsistent), `decide` (needs the user). Evidence is a V1 path with line and symbol, `app/Models/Offer.php:212 Offer::markSold()`, plus the report id `r03`. A claim with no evidence is `inferred` at best.
- **Item ids** are stable and never reused, so the spec can trace back: `F-` functionality, `BR-` business rule, `W-` workflow, `X-` cross-module dependency, `Q-` ambiguity/contradiction/defect, `G-` gap, `IMP-` improvement, `ADD-` additional functionality, `D-` decision, `rNN` report.

## Phase 0 — Start or resume

1. Resolve the module name. If `.scratch/<module>/discovery/discovery.md` exists, **resume**: read its metadata (status, phase, next actions) and the coverage table in section C, then continue from that phase. Read reports only when the next action needs them.
2. Otherwise create it from [discovery-template.md](discovery-template.md). Record today's date and the V1 commit and dirty state (`git -C <V1 root> rev-parse --short HEAD`, `git -C <V1 root> status --short`).

## Phase 1 — Scope

Read the prior material, treating every claim in it as a **lead**, tagged `inferred` until an investigator re-confirms it:

- `docs/archive/v1-port/refactor-plan.md` (module boundaries) and `docs/archive/v1-port/modules/<module>/` if present. The old scans contain unverified guesses ("likely", "inferred from"), and they are the reason this skill exists.
- `GLOSSARY.md` and `docs/adr/`, if present.
- `functional-spec.md` of neighbouring modules already discovered under `.scratch/`. Link to their behaviour instead of re-specifying it.
- `app/Modules/<Name>` in V2, only to note in metadata that it exists. V2 is never evidence of V1 behaviour.

Then orient in V1 with a few `ls`/grep calls, just enough to name the anchors. Do not start analysing. Fill section A:

- **Anchors:** the files where the module obviously lives (models, migrations, Filament resources and clusters, services, policies, enums).
- **Vocabulary:** every string that identifies the module in code, for the cross-repo search: class short names and FQCNs, table names, distinctive column names, morph aliases, route names and URIs, translation keys, Polish UI labels, config and setting keys, enum values, queue and job names.
- **Boundary assumptions and out-of-scope**, from the archive boundary table.

Post the scope in chat in at most 10 lines and carry on. Stop and ask only if the boundary table doesn't settle which module owns something central.

## Phase 2 — First wave

Read [missions.md](missions.md) and plan the wave. Always send **at least one `local` and one `cross-repo` mission**. Their independence is the point: the local agent learns the module from its anchors, while the cross-repo agent hunts the rest of V1 with the vocabulary, deliberately ignoring the anchors. Add `flow` missions only for operations that are already obvious (from the archive scan or the anchors). For a large module, split `local` by anchor group and `cross-repo` by vocabulary group. Keep a wave to 5 missions or fewer.

Dispatch every mission of the wave **in one message**, using the Agent tool with `subagent_type: "legacy-investigator"` and a brief built from the template in missions.md. Assign each one the next `rNN` and its report path. Log each one in section K as `dispatched`. Wait for completion notifications, and never poll.

## Phase 3 — Integrate each report

When a mission returns:

1. Read its report file. Judge it before using it. Does every finding carry a tag and evidence? Does it describe **behaviour** (what a user or the system can do, under which conditions, with what effect), or does it only list classes? Did it cover its whole scope (check the coverage ledger)? If the report is weak, send a narrower mission rather than patching it from your own guesses.
2. **Spot-check** at least one high-impact claim per report yourself, by opening the cited V1 line. High-impact means it touches money, state transitions, permissions, data deletion or external systems. Record it in K as `spot-checked`.
3. Fold the findings into sections B to J under stable ids, each citing `rNN` and its evidence. Keep `confirmed`/`observed` apart from `inferred`. When two reports disagree, record a `Q-` item. Never pick a winner silently.
4. Update section C's coverage table, section K, and the metadata **Next actions** line. The document must let a fresh session resume from it alone.

## Phase 4 — Gap review and follow-up waves

After each wave has fully returned, run the **exit criteria** below against the document. Each failing criterion becomes a follow-up mission (`flow`, `verify`, or a narrow `local`/`cross-repo`) or, if no mission can settle it, a `G-` item with its kind. Then dispatch the next wave and go back to Phase 3.

**Exit criteria.** All must hold:

1. Every entry point in the cross-repo census maps to an `F-` item, or is marked out of scope or owned by another module, with a reason.
2. Every `F-` item has an entry point, evidence tags and a confidence level. It also has a traced `W-` workflow, unless it is plain display with no conditions or side effects, and you write that reason down.
3. Every file in the union of the reports' "matched, not examined" ledgers has been examined or dismissed with a reason.
4. Every "lead outside scope" from every report has been followed, dismissed with a reason, or logged as a `G-` item.
5. Every contradiction between reports has been settled by a `verify` mission or logged as a `Q-` item.
6. Every high-impact `BR-`/`W-` claim has a second source: a `verify` mission or your spot-check.
7. **Saturation:** the last wave found no new `F-` items or entry points. If wave 4 is still finding new functional areas, stop and ask the user whether the module should be split or re-scoped.
8. Sections I and J are filled in, or state "none identified" in writing.

Finished missions are not an exit criterion. Only the eight above are.

## Phase 5 — Readiness gate

Write section L (the completeness assessment), set the status to `ready-for-grilling`, and give the user a short readiness summary: counts per section, the areas with the weakest coverage, and the `needs-runtime` gaps they could check by hand in V1. Then **ask whether they are ready to start grilling, and stop.** Do not start grilling in the same message.

## Phases 6–8 — Grilling, additional functionality, spec

Once the user agrees, read [grilling.md](grilling.md) and follow it. It runs the grilling round on existing behaviour, the separate additional-functionality round, and writes the spec from [spec-template.md](spec-template.md).

## Context hygiene

- Investigators return a short summary. The detail stays in their report files, so read a report section only when you need it.
- After each integrated wave and each grilling round, `discovery.md` is current. That makes it a safe point for the user to `/clear` and resume with "continue discovery for <module>".
- Briefs carry ids and pointers, not pasted findings. A `verify` brief carries the bare claims and none of the reasoning behind them, so the check stays independent.
