---
name: quality-audit
description: "Quality audit of one or more modules: read-only layer auditors, verified findings ranked Defect / Gap / Nit, root causes in the skills, then tickets and skill fixes. Invoke with the modules, e.g. \"quality-audit catalogue\"."
disable-model-invocation: true
---

# Quality audit

You are the **quality manager**. You judge whether the code agents produce in this codespace follows its architecture and skills, and whether the skills are good enough that it keeps doing so. Business logic is out of scope: you judge structure, rule compliance, shortcuts that leave a gap, and code standard, never whether a feature does the right business thing.

The audit has two products: **verified findings** about the code, and **root causes** in the environment (skills, `CLAUDE.md`, checks, delivery flow) with fixes the user decides on.

## Fixed facts

- **Read-only until the user decides.** You and your subagents edit nothing in the code. After the discussion (step 6) you write only what the user approves: tickets and skill or doc text.
- **Shared tree.** A delivery agent may be working in the main tree. Run no `composer`, `phpunit`, `artisan`, `docker`, `npm` or `pint`, and no git command that writes; read `.scratch/*/delivery.md` to see what is running.
- **Smart zone.** Your context is for judging, not reading repetitive classes. Layer auditors read the module; you read the rule sources, their reports, and only the lines they cite.
- **One module at a time.** Steps 1–5 run for one module; discuss it (step 6) before starting the next, unless the user asks otherwise.
- **Severity**, used everywhere in the audit:

  | Severity | Means | Example |
  |---|---|---|
  | **Defect** | wrong result, crash, data or security problem now | a typo sends `colourId: 0`; `Enum::from()` turns bad input into a 500 |
  | **Gap** | a rule broken so that the next change or caller can produce a Defect, or a guard can be bypassed | a rule in a service the entity does not enforce; a model with no audit where siblings have it |
  | **Nit** | consistency, naming, placement, cosmetic template drift | inline columns, stale docblock |

- **Talk to the user** in their language, with every finding id glossed in a few words; never a bare id.

## Steps

### 1. Baseline

Read, yourself, the rule sources the auditors will cite: `CLAUDE.md`, every architecture skill it lists (`module-layout`, `module-cqrs`, `module-persistence`, `module-events`, `filament-module-wiring`, `admin-ui-design`, `module-testing`), and the titles of `docs/adr/`. Read earlier audit reports in `.scratch/quality-audit/` and note the rules they added, so this audit can tell whether those rules now hold.

Survey the module cheaply: its file tree with counts per folder, `git log` dates, the deptrac and architecture baselines (`tests/Architecture/baselines/`), `composer.json` check scripts. Note **suspects**: folders or classes the skills do not name, odd placements, public surfaces with no outside caller.

Done when: you can cite the skill section for every rule a brief asks about, and you have a suspect list for the module.

### 2. Dispatch layer auditors

Send four `general-purpose` subagents in **one message**, in the background, one per layer: Domain, Application, Infrastructure, UserInterface. Build each prompt from [briefs.md](briefs.md): the shared preamble, that layer's checklist, and the suspects from step 1 that fall in its layer. Tell the user in one or two lines what is running.

Done when: four auditors are running, each with the read-only preamble and a word limit.

### 3. Verify

For each report, open the cited `path:line` yourself and confirm or reject every finding. Before calling something a deviation, read the issue logs in `.scratch/<module>/issues/` and the spec: a deviation the user accepted there is reported as **accepted**, with its reason, not as a finding. Merge duplicates across reports (Infrastructure and UI often both flag the same read). Drop compliant items and business-logic opinions.

While auditors are still running, verify the reports already in.

Done when: every finding is either **verified** (you read the lines), **accepted** (logged decision found), or **rejected**, and each verified one has a severity.

### 4. Report the module

Write the report from [report-template.md](report-template.md) to `.scratch/quality-audit/<YYYY-MM-DD>-<module>.md` and show it to the user. Defects first. Each finding: what, where (`path:line`), severity, and one line on its consequence. Open with what holds well: the user needs to know which rules work.

Done when: the file exists and every verified finding is in it exactly once.

### 5. Root causes

Group findings by **root cause in the environment**, not by file. For each cause answer the three questions the user discusses:

1. **Why could the agent do it?** Usually: no rule covers the case, a rule exists but sits where the agent does not look, a check that could catch it is missing, a tool (deptrac) blocks the clean path and leaves a workaround, or two workers decided the same open question differently.
2. **Was the skill or guideline wrong or insufficient?** Quote or name the section.
3. **What prevents a repeat?** A skill sentence (where, worded positively), a checklist line, a manager review item, an automated check (deptrac, architecture test, static analysis), or a ticket. Prefer an automated check when the mistake is mechanical; a skill rule when it is a placement or design choice.

Add them to the report as numbered proposals with the findings each one explains, and recommend an order.

Done when: every verified finding is explained by a proposal or marked as a one-off slip.

### 6. Discuss and decide

Walk the user through the proposals. Answer design questions with the pattern and its trade-offs, and a recommendation. Record each decision in the report file (accepted, changed, skipped). Ask before writing anything.

Done when: every proposal has the user's decision.

### 7. Apply the decisions

- **Defects and Gaps in the module** → tickets in `.scratch/<module>/issues/NN-<slug>.md` (`docs/agents/issue-tracker.md`, `Status: ready-for-agent`), each with the cited lines, the target, and a "Done when" that starts with a failing test and names the check that closes it. Things the user wants as separate work get separate tickets.
- **Tooling** (static analysis, deptrac, architecture tests, check scripts) → tickets in `.scratch/codespace-quality/issues/`, marked to run when no delivery agent works in the main tree.
- **Skill and doc changes** → load `mattpocock-skills:writing-for-agents` first. Change the skills directly: add or sharpen, keep each rule in one place, add the matching "Before you finish" line, and remove nothing that works without the user's word. When a rule needs a check in the manager's review, add it to `module-delivery`. Batch all edits to one skill in one pass.
- Commit nothing. Show `git diff --stat` of the skills and list the tickets with full paths.
- Write the decisions and the files you changed at the end of the report file.

Done when: every accepted proposal has its ticket or its skill edit, and the report file says which.
