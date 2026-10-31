---
name: module-delivery
description: Deliver a module's issues from `.scratch/<module>/issues/` with module-worker subagents — the manager dispatches, reviews each diff against the issue and spec, sends fixes back, and commits approved issues. Invoke with the scope, e.g. "deliver users", "users issues 1-8", "users issue 03".
disable-model-invocation: true
---

# Module delivery

You are the **manager**. Workers (`module-worker` subagents, Sonnet) implement issues; you choose what runs, review what comes back, and own every commit. The files carry the work: the spec says what, the issue says which slice, the skills say how, and the issue's Comments hold what happened. Read them instead of reconstructing from memory.

## Fixed facts

- **Inputs:** `.scratch/<module>/functional-spec.md`, `.scratch/<module>/issues/NN-*.md`, `GLOSSARY.md`, `docs/adr/`, the architecture skills listed in `CLAUDE.md`.
- **Run log:** `.scratch/<module>/delivery.md`. Only you write it. It holds one row per issue (status, worktree, round, last verdict) and the next action, so a fresh session can resume from it alone.
- **Isolation:** every issue gets its own worktree, so `git diff` there shows only that worker's changes.
  - `worktree.sh create <module> <NN>` makes `.worktrees/<module>-<NN>` on branch `<module>/<NN>`, from HEAD, with vendor and `.env`.
  - `worktree.sh remove <module> <NN>` deletes it after the merge.
  - `run.sh <dir> "<cmd>"` runs any PHP, Artisan or Composer command in the Laradock container.
  - All three live beside this skill.
- **Safe points:** after each commit `delivery.md` is current, so the user can `/clear` and resume with the same request.
- **Concurrency:** at most **2 workers** at once, and only as many as there are ready issues.
- **Ready** means:
  - `Status: ready-for-agent`;
  - every issue in `Blocked by:` is `resolved` **and merged** into the current branch;
  - the issue is inside the user's scope.

  Any other status (`needs-triage`, `needs-info`) waits for the user.

## Loop

1. **Resume or start.** Read `delivery.md` if it exists, otherwise create it. Read the spec's table of contents and every in-scope issue's header. Then read `git status` in the main tree: commit nothing of what you find there. Other sessions' uncommitted work stays theirs.
2. **Dispatch.** Fill free slots with ready issues, lowest number first. For each one:
   1. Create its worktree.
   2. Dispatch a `module-worker` with the brief below, in one message per batch. Fix rounds use SendMessage to the same worker; load it with ToolSearch if it is deferred.
   3. Mark it `in progress` in `delivery.md`.

   Wait for the completion notification; never poll.
3. **Review** each returned worker (see Review). The verdict is **approve** or **fix**.
   - **Fix:** send the numbered findings back to the same worker with SendMessage, then wait.
   - **Approve:** go to Commit.
4. **Commit and merge** (see Commit). Then set the issue `Status: resolved`, append your review, update `delivery.md`, and remove the worktree.
5. **Repeat** from step 2 until no in-scope issue is ready or running. Finish with a report to the user:
   - issues resolved, with their commits;
   - issues stopped, and why;
   - deviations the user should look at;
   - what is left blocked.

### Worker brief

```
Issue: .scratch/<module>/issues/<NN>-<slug>.md
Worktree: <absolute path> (branch <module>/<NN>) — work and run commands only there, via .claude/skills/module-delivery/run.sh
Spec: .scratch/<module>/functional-spec.md
Resolved issues you build on: <NN, NN> (their Comments note the seams they left)
Focus: <one or two lines: the riskiest part of this issue, or a lesson from an earlier review>
```

Keep the brief this short. Your `module-worker` definition carries the rules, and the files carry the content.

## Review

Run every check yourself; a worker's "green" is a claim, not evidence.

1. Read the worker's log entry in the issue file, inside the worktree.
2. `git -C <worktree> status` and `git -C <worktree> diff --stat`. Untracked files count as part of the diff. Then read the changed files, starting with the domain and application layers.
3. Check each item and note every miss as a numbered finding with `path:line`:
   - every "Done when" line of the issue holds;
   - every FR, RULE or WF the issue cites behaves as the spec says, refusals included;
   - each such behaviour has a test, and test-first classes follow `module-testing`;
   - the layer rules in `CLAUDE.md` and the skills hold: no repository in Filament, no business logic in UI, and every write goes through a command carrying the Actor;
   - nothing outside the issue's scope was built, and the seams the issue names exist;
   - each logged deviation is justified by the spec or decisions. A deviation that changes behaviour the user decided goes to the user, not to the worker.
4. `run.sh <worktree> "composer check"` must be green.

Approve only when the findings list is empty. Style preferences beyond the skills are comments, not findings.

## Limits

- **Rounds:** at most **3 fix rounds** per issue. A round is one SendMessage with findings plus the worker's reply.
- **Repeating findings:** if a finding returns after it was marked fixed, or a fix breaks something an earlier round approved, stop that issue at once without spending the remaining rounds. Fix rounds that reproduce their own problems never converge.
- **Stopping an issue:**
  1. Set `Status: needs-info`.
  2. Append your findings and the loop you saw to the issue.
  3. Leave the worktree in place for the user.
  4. Record it in `delivery.md`.
  5. Continue with issues that do not depend on it.
- **Questions only the user can answer** (spec gaps, decided behaviour that turns out impossible) stop that issue the same way, without a fix round.
- **Merge conflicts:** if a rebase onto the current branch conflicts beyond trivial hunks, send the conflict to the worker as a fix round.

## Commit

In the worktree, on the issue's branch:

1. **Status line.** Set the issue's `Status: resolved` and append your review under `## Comments`:

   ```
   ### Review — <YYYY-MM-DD>
   - **Verdict:** approved after <n> fix rounds
   - **Notes:** <what is worth knowing later: decisions taken, deviations accepted, seams left>
   ```

2. **Message.** Commit everything with a message shaped by the `pr` skill's sections, with no preamble or boilerplate:
   - subject `feat(<module>): <NN> <issue title>`;
   - **Summary:** the smallest tree or diff sketch that shows what changed;
   - **Evidence:** the tests that cover the issue, and the `composer check` result;
   - **Merge Danger:** door and blast radius, one line each.

   Use the `GLOSSARY.md` terms.
3. **Merge.** If the current branch moved since the worktree was created, run `git -C <worktree> rebase <current branch>` and then `composer check` in the worktree again. Then, in the main tree, run `git merge --ff-only <module>/<NN>`.
