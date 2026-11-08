# 08 - Technical log: daily channels and logging rules

Status: resolved
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

### Worker — 2026-11-05
- **Built:** FR-15/FR-21: daily channels `aplikacja` (default, via `stack` with `LOG_STACK=aplikacja`), `bezpieczenstwo`, `kolejka`, each in `config/logging.php`, own file `storage/logs/<channel>-YYYY-MM-DD.log`, 14 days (`LOG_DAILY_DAYS`). Channel -> Polish label map is `config('logging.labels')` (ticket 10 reads it; the Polish spelling "bezpieczeństwo" lives only there). FR-19/D-25 rules added to `module-cqrs` under Errors > Logging. `.env.example` updated. Ticket 07 seams closed: cleanup failure logs to `aplikacja`; retention check covers every `daily`-driver channel (test with `kolejka` at 30 days). Tests: `tests/Feature/Core/LogChannelsTest.php`.
- **Deviations:** keys are ASCII (`bezpieczenstwo`), label is Polish. Labels are in config, not a lang file, so an integration adds channel and label together (woocommerce later). `phpunit.xml` sets `LOG_STACK=aplikacja` so local `.env` values do not leak into tests. No CLAUDE.md line (did not clearly fit).
- **Seams left:** ticket 09 logs to `bezpieczenstwo`; ticket 10 lists files via `logging.labels`; woocommerce integration adds its channel + label.
- **Open questions:** none
- **Check:** composer check green

### Worker — fix 1
- **Built:** `config('logging.labels')` now maps channel key to a translation key (`audit::channels.<name>`); Polish text is in `app/Modules/Audit/UserInterface/resources/lang/pl/channels.php`. Config comment and `module-cqrs` Logging lines updated. Test: every label resolves to a translated (non-key) Polish text, and `bezpieczenstwo` gives "bezpieczeństwo".
- **Deviations:** none. The earlier "labels in config, not lang file" deviation is withdrawn.
- **Seams left:** ticket 10 resolves labels with `__()`; integrations add their own key.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 1 fix round
- **Notes:** Daily channels `aplikacja` (default via `LOG_STACK`), `bezpieczenstwo`, `kolejka`, 14 days (`LOG_DAILY_DAYS`). Keys are ASCII; `config('logging.labels')` maps key → translation key (`audit::channels.*`), the one place ticket 10 reads; an integration adds its channel and its own label key. 07's seams closed: failures log to `aplikacja`, the retention check covers every daily channel. Logging rules in `module-cqrs` → "Logging". Local `.env` needs `LOG_STACK=aplikacja` (phpunit sets it).
