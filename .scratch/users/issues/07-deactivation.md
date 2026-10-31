# 07 - Deactivate and reactivate Users

Status: ready-for-agent
Blocked by: 03

Spec: FR-27, RULE-08, WF-05, WF-01 deactivated branch, WF-03 deactivated branch, §7 User activity, §11 deactivation cases. Decisions: D-39, D-47 (A-10 rejected: nobody deactivates a Super Admin from the panel).

## What

- **Deactivate and reactivate** from the user detail or edit page, with a confirmation step.
  - An Admin can deactivate Users and Admins. Nobody can deactivate a Super Admin, and nobody can deactivate themself.
  - Deactivation ends all the User's sessions at once and announces "User deactivated"; reactivation announces "User reactivated". Both are domain events, audited.
- **Effects:**
  - a Deactivated User cannot sign in (WF-01 message) and gets no reset link, only the neutral response;
  - the "Admins" group contains active Users only;
  - the list shows an inactive marker and has an active/inactive filter.
- **No delete anywhere.**

## Done when

- WF-05 is tested, including every refusal from RULE-08.
- Sign-in refusal and the neutral reset response for Deactivated Users are tested.
- If ticket 06 is already resolved, its deactivated seam is filled; otherwise leave a note.
- `composer check` passes.

## Comments
