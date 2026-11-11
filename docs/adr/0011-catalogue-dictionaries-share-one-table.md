# The six catalogue dictionaries share one table

V1 kept each dictionary in its own model and table (`offer_colors`, `offer_producers`, `offer_types`, `offer_equipment`, `car_brands`, ...), each with its own screen. In V2 the six Catalogue dictionaries (position, colour, equipment, producer, vehicle make, vehicle body) are one `DictionaryValue` entity in one table, `catalogue_dictionary_values`, told apart by the `Dictionary` enum. Every value has only a name and the withdrawn flag (FR-12), and all six follow the same rules: the name is unique within its dictionary ignoring case, and a value can be withdrawn, restored, or deleted while unused. One table gives one set of code and one "Słowniki" screen with a tab per dictionary.

This was decided at delivery (Catalogue issue 01, 2026-11-11), not in the grilling.

## Consequences

- A column such as `glass_models.position_id` points at the shared table, so the database does not check that the value belongs to the right dictionary; the handlers check it.
- Integrations and Offers refer to a value by its id, and the `Dictionary` enum tells which kind it is (e.g. `producer` → the WordPress producer type). Their mappings stay theirs (D-26).
- The V1 data migration maps each old per-table id to a new id in the shared table.
