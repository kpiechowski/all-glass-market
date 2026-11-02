# Discovery missions

Shared by the discovery manager (to plan and brief) and the `legacy-investigator` (to execute). Each mission answers one question. Its **done when** line is the bar the investigator works to.

## Brief template

The manager sends exactly this, filled in, as the Agent prompt:

```
Mission: <local | cross-repo | flow | verify | spec-audit>
Report: r<NN> → .scratch/<module>/discovery/reports/r<NN>-<mission>-<slug>.md
V1 root: /home/kp/laravel/projekty/AllGlass/ (read-only) · V1 commit: <sha>
Module: <name> — <one-line apparent responsibility>
Question: <the one thing this mission must answer>
Scope: <anchors | vocabulary | the operation and its entry point | the numbered claims | spec + discovery paths>
Already known: <≤10 lines of ids and gists, so the work isn't repeated; omit for verify>
Out of scope: <areas covered by other missions or owned by other modules>
Done when: <the mission's criterion below, sharpened for this scope>
```

## local — what the anchors do

Read every anchor in full, and follow each class an anchor calls directly, one hop out. Report **functionality**, not files: what a user or the system can do; the entities and their states; validation and guard conditions; defaults and derived values; settings consumed; side effects; permissions. Note Filament form and table behaviour as user capabilities ("an admin can filter offers by status"), including hidden or conditional fields and actions.

Look for: `booted()` / `static::*` model hooks, observers, accessors and mutators that encode rules, scopes that define business sets ("active", "available"), casts and enums with their values, soft deletes, cascade rules in migrations.

**Done when:** every anchor is in the coverage ledger as examined, and every functionality candidate has a tag and evidence.

## cross-repo — where else the module lives

Search all of V1 except `vendor/`, `node_modules/`, `storage/` and `public/build/` for every vocabulary term: `app/`, `routes/`, `config/`, `database/` (migrations, seeders, factories), `resources/` (views, Livewire, JS, lang), `tests/`, `bootstrap/`, `composer.json` scripts. Anchors are covered elsewhere, so look **outside** them, and try at least two spellings per term (short name and FQCN; table name and model name; label and translation key).

Hunt the indirect paths: raw `DB::table()` and query-builder access by table name, events and notifications carrying the model, observers registered in providers, other models' hooks touching it, jobs and scheduled commands (`routes/console.php`, `Kernel`), API and webhook routes, policies and gates, Filament resources, relation managers, widgets, exports and global search in **other** clusters, mail and Blade views, FK cascades in other tables' migrations (deleting X deletes Y is business behaviour), and string-based class references.

Also produce the **entry-point census**. It is the manager's completeness denominator, so it covers the whole of V1, anchors included: one table row per entry point that reads or changes the module's data or behaviour. That covers routes, Filament pages, actions and bulk actions, Livewire components, artisan commands, scheduled tasks, queued jobs, event listeners, observers and webhooks. Give each one its kind, path, what it does in a single line, and whether it can actually be reached. Code with no route, registration or caller is `unreachable` and goes in the report.

**Done when:** every vocabulary term has been searched (list the queries), every hit outside the anchors is classified, and the census is complete.

## flow — trace one business operation end to end

Start from the given entry point and follow **everything** that runs: calls, dispatched events and their listeners, model events and observers fired by each save, queued jobs, notifications and mail, external HTTP calls, DB cascades. Record:

- **Preconditions:** authentication, permissions and policies, validation, state guards.
- **Steps**, in order, each one described as behaviour.
- **State changes:** which data changes, before and after, and the status transitions.
- **Side effects:** other records, external systems, notifications, logs, journals.
- **Failure paths:** validation errors, exceptions, caught-and-swallowed errors, retries, partial writes, and whether a transaction wraps the operation.
- **Alternative paths:** other entry points that perform the same operation, and how they differ.

Say where the trace stopped and why: vendor code, dynamic dispatch, or runtime-only data.

**Done when:** the operation is followed until no V1-owned code remains to run, or each stopping point is named.

## verify — independent check of claims

You get numbered claims with no reasoning attached. Settle each one from V1 alone, without reading other reports, as `confirmed`, `refuted` (with what is actually true) or `partly` (what holds and what doesn't). Use `git -C <V1 root> log -S '<term>'` or `git blame` when the question is whether code is still live or was abandoned.

**Done when:** every claim has a verdict with its own evidence.

## spec-audit — fresh-eyes check of the final spec

Read `functional-spec.md`, then `discovery.md`, sections C, J and M. Report:

1. Requirements with no trace to an `F-`/`BR-`/`W-` item or a `D-` decision.
2. `D-` decisions not reflected in the spec, or reflected wrongly.
3. `IMP-`/`ADD-` items in the spec without an approving decision.
4. Implementation leaking in: class, table, pattern or framework names used as requirements rather than as legacy traces.
5. Places where a reader who never saw V1 could not act: undefined terms, missing pre- or postconditions, vague rules.

**Done when:** all five checks have been run over the whole spec, with findings or an explicit "none".
