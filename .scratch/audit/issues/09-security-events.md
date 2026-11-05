# 09 - Security events in the "bezpieczeństwo" channel

Status: ready-for-agent
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
