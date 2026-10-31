# 02 - User entity and user management (list, detail, create, edit)

Status: ready-for-agent
Blocked by: 01

Spec: FR-22..FR-25, FR-35, RULE-01..RULE-03, RULE-08, WF-04 (temporary-password path only), §8 "Information kept about a User". Decisions: D-13, D-20, D-23, D-29, D-30, D-32, D-41, D-47.

## What

- **User** as an entity with the information in spec §8. Leave out the parts later tickets add: active state (07), "password change required" (06), Extra Permissions (08) and declaration acceptance (04). Do add the Account type (always Private here) and the Otomoto shipping text.
- **List (FR-22):** name and e-mail searchable, Account type badge, Role badge with "nieznana rola", join date sortable, filters by Role and Account type. The active and password-change markers and their filters come with 06 and 07.
- **Detail (FR-23):** contact and company data, Account type, Role, Otomoto shipping text. Leave a place for Offers' statistics and tab (D-05); Offers does not exist yet, so render nothing there. The audit history comes with Audit; leave a place for it.
- **Create (FR-24, WF-04):** Role User or Admin only; Super Admin is never offered. Validation per RULE-01..RULE-03 (D-29: no unique-name and no "_" rule). Only the "set a temporary password" path; the invitation path is ticket 06. "Password change required" does not exist yet; ticket 06 wires it.
- **Edit (FR-25):** the same form. Admins see Super Admins read-only. Nobody changes their own Role. An Admin may demote an Admin to User. The Role section is hidden for Super Admins. An empty password keeps the current one.
- **Otomoto shipping text (FR-35):** a rich-text field in the admin form, with the helper text from the spec.
- **Permissions:** every action is guarded by a Users Permission through the Authorizer, and every write goes through a command carrying the Actor.

Replace today's Users resource, commands, repository, observer and `DeleteUser` path; there is no delete in V2 (D-39). The known gaps listed in `.scratch/architecture/issues/05-migrate-users.md` apply here.

## Done when

- FR-22..FR-25 and FR-35 behave as specified within the limits above. Handler tests cover each RULE-08 limit (refusals included), and there is a Livewire test per page.
- Users classes touched here leave the deptrac baseline.
- `composer check` passes.

## Comments
