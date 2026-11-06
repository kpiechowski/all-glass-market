# Admin UI: handoff for the manager

You are the **manager** of the admin UI restyle of AllGlass Market (Laravel + Filament v5.11, Tailwind v4 through the Filament theme, PHP 8.5). You do not write most of the code yourself. You hand issues to subagents, verify them technically, report to the user in Polish, and keep the spec, the issues and the skill up to date. The user is a developer with Filament experience who reviews the look in the browser and decides about design.

## Where things are

- **Branch:** `feat/admin-ui` (pushed). Commit to it. Do not switch branches.
- **Spec:** `.scratch/admin-ui/functional-spec.md`. Decisions D-01…D-43 and §12 amendments. It is authoritative. Every user decision goes there as a new or amended D-xx.
- **Issues:** `.scratch/admin-ui/issues/01…11-*.md`, with a `Status:` line and a `## Comments` log. Read the Comments of the current issue; notes for later issues live there too.
- **Skill for screens:** `.claude/skills/admin-ui-design/` (`SKILL.md`, `templates/A–F`; `variants.md` comes in issue 10).
- **Skill for Filament mechanics:** `.claude/skills/filament-module-wiring/`.
- **ADR:** `docs/adr/0005-admin-ui-templates-and-theme-variants.md`. Also 0003 (enums with `HasColor`).
- **Styleguide:** `/admin/styleguide`, local only. `app/Core/UserInterface/Filament/Pages/Styleguide*`, organised in tabs (`?obszar=`): Formularze / Sekcje i układ / Karta rekordu / Tabele i filtry / Akcje i modale. It is the place where the user judges the look.
- **Theme:** `resources/css/admin/`, split as tokens/, base/, filament/<area>/, components/, pages/. `theme.css` holds only imports and @source.
  - Tokens: `--gray-*`, `--primary-*` (600 = #003eb2, 950 = navy), `--brand-navy` #001B4E, `--brand-royal` #003eb2, `--brand-sky` #0070C0, `--brand-sky-light`, `--ag-*`.
  - Motion: `tokens/motion.css`, reused for every transition and zeroed under reduced motion.
  - The navy sidebar lives in `filament/layout/sidebar-navy.css`.
- **Navigation:** built explicitly in `AdminPanelProvider::navigation()`. Top group: Pulpit. Main group. Bottom group: profile.

## Status (2026-11-06)

- **Done:** 01 (skill rename), 02 (admin-ui-design skill), 03 (Styleguide), 04 (theme, layout, Manrope, logos, buttons), 05 (sections, tabs, navy sidebar, Styleguide in tabs).
- **Next:** 06 forms → 07 infolists → 08 tables (+ filter feedback in its Comments) → 09 actions, modals, badges (**ask the user about D-41 first: chamfered geometry and other accents**) → 10 variants + `variants.md` → 11 retrofit of Users and Audit from the skill alone.
- **Open for later:**
  - breadcrumbs or the page title in the empty topbar;
  - a sliding tab indicator (needs Alpine or a vendor view, so only on the user's order).

## How you run an issue

1. Read the issue, its Comments and the spec decisions it cites.
2. Send **exactly one subagent** (`general-purpose`). Never two at once, never in parallel; the user requires it. Its brief always includes:
   - **Working rules:**
     - branch `feat/admin-ui`, no branch switching, **no commit**;
     - run tooling in the container **as `laradock` (uid 1000)**: `docker exec -u laradock -w /var/www/all-glass-market laradock8_5-workspace-1 sh -c '...'`; Artisan is `dartisan`. Running as root leaves root-owned compiled views in `storage/framework/views`, and php-fpm (www-data, uid 1000) then fails with `touch(): Utime failed`. Fix: `docker exec -w /var/www/all-glass-market laradock8_5-workspace-1 chown -R 1000:1000 storage bootstrap/cache`.
   - **Skills to load first:** `admin-ui-design`, plus `filament-module-wiring` if PHP changes, plus `frontend-design:frontend-design` for aesthetics.
   - **How to style:**
     - read the vendor CSS (`vendor/filament/*/resources/css`) for the real `fi-*` selectors and variables, and never guess them;
     - build on the existing tokens and motion tokens;
     - the modification ladder (D-30): CSS on `fi-*`, then variant classes, then our own Blade, and a vendor view override **only on the user's order**;
     - in schemas use only our classes; Tailwind utilities only in our own Blade.
   - **Styleguide:** add missing cases to the Styleguide (the right tab) when they are needed for review.
   - **Finish with:** `vendor/bin/pint <own files>` (explicit paths, see "The user's working rules"), `npm run build`, `composer check` (pint, deptrac, phpunit). Then a `## Comments` entry in the issue (Status unchanged), and a short reply with what changed (values) and what the user should look at.
3. When the subagent reports back, **verify it technically yourself**:
   - `git status` (only expected files);
   - `npm run build` and `composer check` in the container;
   - `curl -H 'Host: all-glass-market.test' http://localhost/admin/login` (200) and `/admin/styleguide` (302 to login).
   - Read the key diff (provider, PHP, the main CSS).
4. Report to the user in Polish: what changed, the verification result, and a numbered list of what to look at on the Styleguide. **No browser agents.** The user clicks the panel themselves.
5. The user's feedback becomes the next round, sent through **SendMessage to the same subagent** (it keeps its context). Small one- or two-line CSS tweaks you may do yourself and rebuild.
6. After the user accepts: set `Status: resolved`, add a closing comment (what was accepted, what is left for later), then **one commit** with explicit paths and a message in the style `feat(admin-ui): NN <summary>`. If the user leaves mid-issue, make a WIP commit with a handoff note in the issue.
7. Every user decision on rules goes into the spec (D-xx) and, if it changes how agents build screens, into the `admin-ui-design` skill.

## What the user expects

- **Language:** speak Polish. Code, spec, issues and skills are in English, and UI copy is in Polish.
- **Opinion first:** when asked "what do you think", give a design opinion with a recommendation, and change nothing until they say so. When they ask for alternatives, propose a few with a recommended one.
- **Direction (D-31):**
  - quiet and modern, "not a disco", not a 2010 or WordPress panel;
  - the client's colours: navy, royal and sky; the client's website uses navy blocks and chamfered geometry;
  - Manrope;
  - subtle, pleasant motion (D-42), because the client's customers use the panel.
- **Lessons learned:**
  - `primary-50` is nearly the canvas tint (#f3f6fb), so a fill on the canvas needs `primary-100` or more;
  - on light backgrounds there is no white text: the "white" equivalent is navy plus weight;
  - an inset active bar in the sidebar was rejected: the bar stays on the fill's edge;
  - the page-tab glow stays as it is for now.
- **Sidebar badges (D-23 amended):** a count or an attention indicator. Counts must be cheap or cached, because Filament computes them on every page load.

## The user's working rules

- One subagent at a time. The user accepts each issue on screenshots, often over several rounds.
- Commit only after acceptance, never with `git add -A` on the whole tree.
- **Another agent may work in the same working tree at the same time** (a different area of the project). Neither you nor your subagents may commit, discard or move its changes:
  - no `git add -A`/`.`, `git stash`, `git checkout`/`restore`/`reset`/`clean`, no branch switching;
  - stage and commit only explicit paths that belong to the current issue;
  - modified files outside the issue's scope in `git status` are the other agent's work: leave them alone, and do not count them as a failed `git status` check;
  - run Pint on explicit paths, not `--dirty`, so its files are not reformatted;
  - if `composer check` fails on its files, report it to the user instead of fixing it.
  - Put these rules in every subagent brief.
- Do not touch `docs/archive/`. Do not change dependencies without approval.
