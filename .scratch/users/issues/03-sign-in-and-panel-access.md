# 03 - Sign-in, panel access, rate limit, sessions

Status: ready-for-agent
Blocked by: 02

Spec: FR-07..FR-10, FR-12, RULE-13, WF-01 (without the deactivated and forced-change branches), §11 first edge case. Decisions: D-10, D-20, D-34, D-37, D-47.

## What

- **Panel access (FR-07):** only a User whose Role is registered (ticket 01) may enter the panel. Anyone else is refused at sign-in with the message from FR-07. The deactivated branch is ticket 07.
- **Sign-in (FR-08):** e-mail and password. `/` and the plain sign-in address lead to the panel sign-in page, which is noindex.
- **Rate limit (FR-09, RULE-13):** at most 5 failed attempts per minute per e-mail and IP, with a retry-time message.
- **Sessions (FR-10):** 120 minutes of inactivity, they survive closing the browser, and a password change ends the User's other sessions.
- **No self-registration (FR-12):** no registration route exists. Drop any leftover Fortify/Jetstream-style self-service if present (§14).

## Done when

- WF-01 is covered by feature tests: success, wrong password, rate limit hit, unregistered Role refused.
- A password change ends the User's other sessions, and this is tested.
- `composer check` passes.

## Comments
