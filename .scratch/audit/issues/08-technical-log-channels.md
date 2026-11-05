# 08 - Technical log: daily channels and logging rules

Status: ready-for-agent
Blocked by: —

Spec: FR-15, FR-19, FR-21, §2 Channel. Decisions: D-02, D-25, D-28, D-30.

## What

- Switch the default logging to daily files, kept **14 days**, instead of one growing file.
- Define the channels that exist now. Each is a separate daily file, with a Polish label for the log page:
  - **aplikacja:** the default;
  - **bezpieczeństwo:** used by ticket 09;
  - **kolejka:** background jobs and failures.

  **woocommerce** is added later by that integration (D-28). Audit does not register channels: the log page (ticket 10) reads whatever daily files exist. Keep the channel → label mapping in one place the page can read.
- **Logging rules for every module (FR-19, D-25):**
  - an error is logged as an error, with context: what failed, which record, the external response;
  - no error is swallowed without a trace;
  - a "szczegóły w logu" message to a user is allowed only for a channel shown on the log page.

  Write them into the architecture skills where a module author will meet them (`module-cqrs` for handlers and jobs; a line in `CLAUDE.md` golden rules only if it fits). Follow `writing-for-agents`.
- `.env.example` reflects the new defaults.

## Done when

- A test shows each channel writes to its own dated file.
- The rules are in the skills.
- `composer check` passes.

## Comments
