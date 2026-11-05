# 06 - Restore from a Change entry

Status: ready-for-agent
Blocked by: 04

Spec: FR-13, FR-14, RULE-05, WF-02, §3 (Restore). Decisions: D-11, D-16, D-36, D-38, D-41. Glossary: Restore.

## What

- On a registered type with restorable fields, a **Przywróć** action appears on a **zmieniono** entry in the record's history (and on the central page) when:
  - the entry holds at least one restorable field;
  - the record still exists.

  There is never a Restore on utworzono or usunięto entries, on relation-only entries, or on relations (D-38).
- **Confirmation (D-36):** a simple dialog lists the fields and values to be written, for example "Przywrócić: status → Aktywna, cena → 1200 zł?". There is no comparison with the current state.
- **Restore is literal (D-16):** it writes exactly the raw old values of the entry's restorable fields, and nothing else.
- **The owning module carries it out (FR-14).** Audit defines a contract (a `Domain/Contracts` port) that a module implements per restorable type. It receives the record id, the field values and the Actor, and dispatches the module's own command. Validation, events and syncs therefore run as for any edit.
  - If the module refuses, nothing changes and the reason is shown.
  - The resulting Change entry is marked "przywrócono z wpisu z {data}" and points to the source entry (D-38). Ticket 01/02 recording supplies the entry; add the marker through the command context.
- Guarded by the Permission "przywracanie zmian" (ticket 02), checked in the handler. It is granted to no Role, so only the Super Admin holds it by default; a Super Admin can grant it as an Extra Permission (D-41).
- Test with a test-only restorable type. No real module offers Restore yet: Users declares none.

## Done when

- Tests cover:
  - the action shown and hidden in every case above;
  - the confirmation lists the fields;
  - the literal write;
  - a refusal leaves the record unchanged and shows the reason;
  - the new marked entry;
  - a User without "przywracanie zmian" is refused in the handler, and an Admin holding it as an Extra Permission may Restore.
- `composer check` passes.

## Comments

### Triage — 2026-11-05
D-38 says Restore is a fixed Super Admin rule with no Permission of its own. The Core `Authorizer` only checks Permissions, so there are two ways to build it:
- **a)** A Permission "przywracanie zmian" that is granted to no Role: only Super Admin passes. Because Users FR-06 lets a Super Admin grant any registered Permission as an Extra Permission, an Admin could get it right away. That is the later topic D-16 mentions.
- **b)** A hard "is Super Admin" check, which needs a new Users `ModuleApi` read. It cannot be granted to anyone.

**Answer (user, 2026-11-05):** a) — recorded as D-41.
