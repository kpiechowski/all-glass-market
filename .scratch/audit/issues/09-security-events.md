# 09 - Security events in the "bezpieczeństwo" channel

Status: resolved
Blocked by: 08

Spec: FR-17, RULE-01, §9 Users, §11 impersonation case. Decisions: D-23, D-27, D-38.

## What

Write one entry to the **bezpieczeństwo** channel for each of these:
- sign-in;
- failed sign-in;
- sign-out;
- password reset requested;
- password reset completed;
- impersonation start;
- impersonation end.

Each entry holds:
- who: the User's id and e-mail, or the e-mail that was tried, for a failed sign-in;
- when;
- the IP address;
- for impersonation, also whom.

Rules:
- Listen to the framework's authentication events and to Users' `ImpersonationStarted` / `ImpersonationEnded` events, which are allowed across modules. Users' code stays unchanged, unless a needed event is missing, in which case add it in Users and say so.
- These are not Change entries (RULE-01).
- Never log a password or token.

## Done when

- Each event has a test asserting one entry with the fields above, and no secret in it.
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** FR-17: one entry per event in `bezpieczenstwo`, context `event`, `user_id`, `email`, `ip` (plus `target_user_id`, `target_email` for impersonation), message from `audit::security.*`; the log line carries the time. Listener `Audit/Application/Listeners/WriteSecurityLogEntry` (one method per event, only logs) through `Audit/Application/Services/SecurityLog`, which builds the context from named fields only, so no password/token can reach it. Listens to the framework's `Login`, `Logout`, `Failed` and Users' `ImpersonationStarted`/`Ended`. Audit port `UserIdentities` (id -> id, email) with a read-only adapter, because the Users events carry ids only. No Change entries written (RULE-01).
- **Deviations:** Two events were missing and are added in Users (Domain/Events), and the Login page now fires the framework's `Failed` (it never calls `attempt()`, so `Failed` never fired; fired for wrong password, unknown e-mail, and refused Deactivated/no-role Users, not for throttling; credentials carry the e-mail only, never the password; UserInterface may not use Domain events, hence the framework event): `PasswordResetRequested(userId)` (framework `PasswordResetLinkSent` does not fire as links use `createToken()`; dispatched by `RequestPasswordResetCommandHandler` only when a link went out, so an unknown e-mail logs nothing, RULE-14) and `PasswordResetCompleted(userId)` (framework `PasswordReset` does not fire; dispatched by `SetPasswordFromLinkCommandHandler` for the reset kind only, not invitations). Also `ImpersonationSession::switchTo` now mutes the guard's dispatcher around `login()`, otherwise each impersonation start/end would log a false sign-in.
- **Seams left:** ticket 10 reads the channel file. Forced logouts by `AuthenticatePanel` (deactivated User) log as sign-out.
- **Open questions:** none
- **Check:** composer check green

### Worker — fix 1
- **Built:** Audit no longer reads Users' model: new `Users/Application/ModuleApi/UserEmails::emailOf(int): ?string` (backed by `UserRepository`), used by `SecurityLog`; Audit's `UserIdentities` port and adapter are removed. Test: `UserEmailsTest`. `ImpersonationSession::switchTo` restores the dispatcher in `finally`.
- **Deviations:** none
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 1 fix round
- **Notes:** `WriteSecurityLogEntry` → `SecurityLog` writes one `bezpieczenstwo` entry per event (event, user_id, email, ip; target for impersonation), named fields only. Users changes accepted: new `PasswordResetRequested`/`PasswordResetCompleted` events (framework fired none), Login fires the framework `Failed` with the e-mail only, `ImpersonationSession` mutes the guard dispatcher around `login()` (restored in `finally`), new ModuleApi `UserEmails::emailOf()`. A forced logout of a Deactivated User logs as a sign-out.
