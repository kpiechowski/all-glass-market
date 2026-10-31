# 06 - Password reset, invitations, forced password change

Status: ready-for-agent
Blocked by: 03

Spec: FR-11, FR-24 (invitation path), FR-25 (password sets the marker), FR-26, FR-36..FR-39, RULE-14, RULE-15, WF-03, WF-04 (invitation), WF-07, §7 Password change required, §10 E-mail, §11 password cases. Decisions: D-34, D-38, D-40, D-41, D-46, D-47.

## What

- **Reset (FR-11, WF-03, RULE-14):**
  - "Nie pamiętasz hasła?" on the sign-in page.
  - The link is single-use and valid 60 minutes. Unknown and deactivated e-mails get the same neutral response; the deactivated case arrives with ticket 07, so leave a seam.
  - Setting the password signs the User in and ends their other sessions.
- **Invitation (FR-24, WF-04):**
  - a single-use link valid 7 days;
  - offered only while e-mail sending works (§10). Decide how "works" is detected, and note it.
- **Admin actions (FR-26):** re-send an invitation or reset link, set or clear "password change required" without changing the password. All within RULE-08.
- **Forced change (FR-36..FR-39, WF-07):**
  - **Set by:** an Admin setting a temporary password or changing a password; an Admin can also set or clear it by hand.
  - **Cleared by:** any password the User sets.
  - **While set:** every panel page leads to "Ustaw nowe hasło".
  - **New password:** RULE-03, differs from the current one, the current one is not asked for.
  - **Afterwards:** other sessions end, the change is audited as forced, and the User lands on the dashboard.
  - **Markers:** "wymaga zmiany hasła" in the list and detail, plus a list filter.
  - **Impersonation:** an impersonator is not redirected. Impersonation arrives with 08, so leave the seam.

## Done when

- WF-03, WF-04 (invitation) and WF-07 are tested, including the expired or used link and the same-password refusal.
- `composer check` passes.

## Comments
