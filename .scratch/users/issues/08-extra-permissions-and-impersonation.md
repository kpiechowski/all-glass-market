# 08 - Extra Permissions and impersonation

Status: ready-for-agent
Blocked by: 03

Spec: FR-06, FR-28, RULE-10, RULE-11, WF-06, §11 impersonation cases. Decisions: D-21, D-22, D-47.

## What

- **Extra Permissions (FR-06, RULE-11):**
  - a Super Admin grants and revokes any registered Permission to an individual User in the create and edit form;
  - only Super Admins see the field, and the detail lists them separately for Super Admins;
  - grants and revocations are audited;
  - the Authorizer (ticket 01) also passes on an Extra Permission.
- **Impersonation (FR-28, WF-06, RULE-10):**
  - It is a Users Permission. Super Admin has it by wildcard; an Admin has it only as an Extra Permission.
  - The target must have a lower Role, be active and hold a registered Role. Nobody impersonates a Super Admin, and impersonation is never nested.
  - Start and end are audited.
  - A banner with a return button shows during impersonation.
  - An impersonator is not sent to the forced password change screen. If ticket 06 is resolved, fill its seam.

## Done when

- RULE-10 and RULE-11 are tested, refusals included, along with the Authorizer's Extra Permission path.
- Start and end of impersonation are tested.
- `composer check` passes.

## Comments
