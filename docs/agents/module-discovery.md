# Module discovery

How a V1 module becomes an agreed functional spec before any V2 work starts. The operational rules live in `.claude/skills/module-discovery/` and `.claude/agents/legacy-investigator.md`. This page is the human overview.

## Why it exists

The retired SCAN → PLAN → IMPLEMENT workflow (`docs/archive/`) scanned one module and moved straight to planning. V1 has almost no module directories, so behaviour sits in shared folders, other models' hooks, jobs, cascades and other clusters. The old scans also mixed facts with guesses: the Submissions scan says "likely" and "inferred" about events and actions that nobody ever checked. Discovery fixes three things: it searches the whole repository, it tags every finding with its evidence, and it lets the user decide what the module should do before anyone designs it.

## Lifecycle

```
0 start/resume ─ 1 scope ─ 2 first wave ─┬─ 3 integrate reports ─ 4 gap review ─┐
                                          └────────── follow-up waves ◄─────────┘
                                                             │ exit criteria hold
                          5 readiness gate ── user says "ready" ──► 6 grill existing behaviour
                          7 "any additional functionality?" ─ no ─┐
                                    └─ yes ─► grill the additions ─┴─► 8 functional spec + audit
```

- **Manager:** the main session. Run it on Opus (`/model opus`, already the user default). It plans missions, judges reports, spot-checks evidence, and is the only writer of `discovery.md`.
- **Investigators:** `legacy-investigator` subagents on Sonnet. They are read-only on V1, run without CLAUDE.md so V2 architecture doesn't colour how they read V1, and each one writes only its own report file. Parallel missions never share a file.
- **Missions:** `local` (the anchors), `cross-repo` (everything else, plus an entry-point census), `flow` (one operation end to end), `verify` (independent re-check of claims), `spec-audit` (fresh eyes on the final spec).
- **Grilling** calls the `grilling` and `domain-modeling` skills, the same pair `/grill-with-docs` runs, so `GLOSSARY.md` and ADRs stay current.

There is no orchestration script. The Agent tool already runs subagents in parallel and notifies the manager when each one finishes. The documents on disk carry state between sessions.

## Artifacts

```
.scratch/<module>/discovery/discovery.md       working document, sections A–M
.scratch/<module>/discovery/reports/rNN-*.md   one evidence report per mission
.scratch/<module>/functional-spec.md           the deliverable
```

Stable ids (`F-`, `BR-`, `W-`, `Q-`, `G-`, `IMP-`, `ADD-`, `D-`, `rNN`) link every spec requirement back to its reports, its V1 evidence and the user's decision.

## Using it

```
Start the discovery process for module Submissions.
Continue discovery for offers.                     # resumes from discovery.md
```

Good points to `/clear` and resume: after each wave has been integrated, and after each grilling round. The manager keeps "Next actions" in `discovery.md` up to date for exactly this.

For a first run, start with a small module (Submissions or Settings) to calibrate the process before Offers.

## Limits and manual steps

- **Static reading only.** Nobody runs V1 or touches its data, so behaviour that depends on production data or runtime configuration ends up as `needs-runtime` gaps. The readiness summary lists them for you to check by hand in V1.
- **Access to V1.** Subagents read `/home/kp/laravel/projekty/AllGlass/`, outside this repo. If you get permission prompts for it, add the folder once with `/permissions` (Additional directories), or start Claude with `--add-dir /home/kp/laravel/projekty/AllGlass`.
- **Large modules** (Offers) take several sessions. If wave 4 is still finding new functional areas, the manager asks whether to split the module.
- After the spec: the architecture process, then `/to-tickets`.
