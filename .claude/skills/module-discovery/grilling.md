# Grilling and the functional spec (Phases 6–8)

Reached from `module-discovery` once the user has said they are ready to start grilling. Set the status in `discovery.md` to `grilling`.

Grilling turns findings into decisions. The goal is what the module **should** do, so legacy behaviour is a starting point to be challenged, not a default to be confirmed.

## Phase 6 — Grill the existing functionality

Call the Skill tool twice, for "grilling" and "domain-modeling" (installed as `mattpocock-skills:grilling` and `mattpocock-skills:domain-modeling`). Together they are the `grill-with-docs` workflow, which is user-invoked and so cannot be called from here. Follow their rules: rounds over the decision frontier, numbered questions, a recommended answer for each, and "yes" accepting the recommendation. `domain-modeling` keeps `GLOSSARY.md` current as terms settle, and offers an ADR only when its three conditions hold.

**Shape the design tree.** Settle earlier branches first, because later ones hang on them:

1. Purpose and boundaries: what the module is for, what it owns, and the ownership of anything found in another module's territory.
2. The inventory: for each `F-` item, keep it, change it, or drop it. Settled `F-` items open their rules and workflows. Uncontroversial items can share one question ("Keep F-01, F-02, F-05 exactly as described?").
3. Business rules (`BR-`) and workflows (`W-`) of the kept items, including edge cases and exceptional paths.
4. Ambiguities, contradictions and defects (`Q-`).
5. Gaps that need a business answer (`G-` of kind `needs-business`) and the `decide`-tagged findings.
6. Cross-module behaviour (`X-`).
7. Improvements (`IMP-`).

**Each question stands on its own.** Cite the ids, give the evidence in two to four lines (what V1 does, with `path:line`), lay out the interpretations or options, and state the consequence of each before your recommendation. Group closely related questions into one coherent discussion rather than scattering them across rounds.

**Facts are your job.** When a question needs a fact nobody has established yet, dispatch a `verify` or `flow` mission (see [missions.md](missions.md)) and keep grilling the branches that don't depend on it.

**Record as you go.** After every round, add `D-` entries to section M, each with its class:

- `preserve`: keep the legacy behaviour.
- `correct`: change or fix it, and say how.
- `new`: an approved addition.
- `exclude`: dropped from V2.
- `pending`: the user deferred the decision.

Point the source item at its decision (`Q-03 → D-07`). Never choose for the user. An unanswered question stays open or becomes `pending`.

**Done when** every `F-` item and every open `Q-`, `G-` (`needs-business`), `decide`-tagged finding and `IMP-` item has a `D-` decision (possibly `pending`), and the user confirms a shared understanding of the existing functionality.

## Phase 7 — Additional functionality

Ask this round even when section J is empty. Present the `ADD-` items: each one's purpose, value and dependencies, in a few lines. Then ask explicitly:

> **Do we want to include any of these additional functionalities in the new module?** You can also bring new ideas.

- **No additions (path A):** record `D-` "no additional functionality" (class `exclude` for each listed `ADD-`) and go to Phase 8.
- **Some selected (path B):** set the status to `grilling-additions`. Unselected items get `exclude` or `pending`. New ideas become new `ADD-` items. Selection is not approval, so grill each selected item as its own subtree:
  - its exact purpose and the actors who use it;
  - user interactions and the workflow, step by step;
  - its rules and constraints;
  - its dependencies on existing `F-` items and on other modules;
  - edge cases and error handling;
  - what is in and out of its scope;
  - its expected behaviour, stated as testable outcomes.

  Record a `D-` decision (class `new`, or `exclude`/`pending`) per item. If the additions reopen an existing decision, revisit it explicitly and log a new `D-` entry that supersedes the old one.

**Done when** every `ADD-` item has a decision, and every question the additions raised is resolved or `pending`.

## Phase 8 — Write the functional spec

1. Write `.scratch/<module>/functional-spec.md` from [spec-template.md](spec-template.md). The requirements are exactly the `preserve`, `correct` and `new` decisions, together with the confirmed findings they cover. An undecided suggestion is never a requirement. Every requirement cites its trace, `(F-03, BR-02, r04; D-07)`.
2. Dispatch one `spec-audit` mission to a `legacy-investigator`. Fix every finding, or note why it doesn't apply.
3. Set the status in `discovery.md` to `spec-written`, and its next action to "architecture process for <module>".
4. Report to the user: the spec path, counts per class (preserve / correct / new / exclude / pending), the pending decisions, and the audit result.
