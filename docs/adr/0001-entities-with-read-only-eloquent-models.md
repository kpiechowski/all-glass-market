# Entities with mappers, Eloquent models read-only

The domain works on plain PHP entities, loaded and saved through repository ports. Each aggregate also has one Eloquent model, kept because Filament's tables, search, filters and relations run on models. Outside the repository adapter the model is read-only: `ReadOnlyModel` throws on `save()`, `update()` and `delete()` unless the adapter (or a test factory) has opened its write scope. We picked this over Eloquent models as entities because nothing in a model stops a Filament page from writing past the command bus. We also rejected a separate write model plus a read model per aggregate, which needs four classes where two do.

## Consequences

- Each aggregate is an entity, a mapper, a model and an adapter. Aggregates stay small so mappers never handle deep graphs.
- The adapter writes through `$model->fill()->save()`, so observers still fire. They emit only the generic `RecordChanged` for audit. Business events come from command handlers, which know the intent.
- Filament fields that save relations or media by themselves are banned, since they would bypass the command. Pivot `sync()` and media writes do not go through `save()`, so the guard cannot catch them. Deptrac and the resource tests do.
