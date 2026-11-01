# <Module> — V1 data migration spec

<!-- Written in Phase 9. V1 side: tables and columns with evidence. V2 side: the information named in
     the functional spec's terms, never V2 column names; whoever builds the import maps those. -->

- **Functional spec:** `.scratch/<module>/functional-spec.md` §8 · **Discovery:** `.scratch/<module>/discovery/discovery.md`
- **Ticket:** `ticket.md` beside this file

## 1. Sources

<Every V1 table holding the module's data, pivot and role tables included, with the V1 migration that defines it.>

## 2. Mapping

| V1 table.column | Meaning in V1 | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `users.company_account` | company flag | transform | Account type | Company only with `has_accepted_terms` too (D-xx) |

<Fate is keep, transform or drop. Every V1 column has a row.>

## 3. New information

<V2 information with no V1 source, and the value each migrated record starts with.>

## 4. Data quality

<Known dirty values, duplicates, orphans and volumes, with evidence. "Not checked" is a valid entry.>

## 5. Run plan

<Order relative to other modules' imports, the idempotency key, what a re-run does, the counts that verify a run, and what the user does afterwards.>

## 6. Open questions
