# 05 - Journal

Status: ready-for-agent
Blocked by: 02

Spec: FR-29..FR-33, RULE-12, §7 Journal entry, §11 Journal cases, §9 "Every module" and "Panel notification channel". Decisions: D-03, D-04, D-16..D-18, D-28, D-47. Glossary: Journal, Journal entry.

## What

- **Writing (FR-29):** any module adds an entry for a User through Users' module API.
  - Fields: kind, severity (info, success, warning, rejection), title, plain-text content, and an optional link to the subject.
  - An unknown User gets no entry, and the caller's operation still completes. A Deactivated User still gets entries (D-47).
- **Reading (FR-30, FR-31):** the owner sees their own entries only, newest first.
  - The unread count shows on the navigation item.
  - Entries can be filtered by kind and severity.
  - The owner can mark an entry read, open its details (which marks it read) and follow its link. A missing subject shows a warning.
  - The owner can delete one entry or many. No hand creation or editing.
- **Notification (FR-32):** every new entry sends a panel notification that opens that entry and marks it read.
  - The "go to subject" button is named after the entry's kind; kind `offer` reads "Przejdź do oferty".
  - Use the shared panel notification channel. If Core has no helper yet, add the smallest one that sends to a User and to the "Admins" group (D-04), and note it in Comments.
- **Plain text (FR-33):** content is always escaped.

## Done when

- FR-29..FR-33 are tested, including the owner-only scope, escaping and the missing-subject warning.
- The module API for writing entries is documented in the Users module so other modules can use it.
- `composer check` passes.

## Comments
