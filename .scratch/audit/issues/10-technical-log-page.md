# 10 - "Logi techniczne" page

Status: ready-for-agent
Blocked by: 08

Spec: FR-16, WF-04, §3. Decisions: D-02, D-12, D-29, D-38.

## What

A panel page **Logi techniczne**, only with "logi techniczne" (Super Admin by default):
- **Opening:** shows the aplikacja channel, today. Channel and day pickers.
- **Table** of the day's entries, newest first, paged. Keep it lean: time, level, and the message shortened to one line.
- **Filters:** by level (debug, info, notice, warning, error, critical, alert, emergency). Search covers the message and the context.
- **Empty state:** a day with no file or no entries shows "brak wpisów".
- **Entry detail:** full message, context, and stack trace if present.
- **Download:** the day's file.
- **No deleting** from the panel.
- **Large files:** they must not exhaust memory. Read lazily or page through the file, and say how in Comments.

## Done when

- Tests cover:
  - access refused without the Permission;
  - the default channel and day;
  - level filter and search;
  - parsing a multi-line entry with a stack trace;
  - the empty day;
  - the download.
- `composer check` passes.

## Comments
