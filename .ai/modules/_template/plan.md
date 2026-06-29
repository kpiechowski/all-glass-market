# {Name} — V2 Plan

**Status:** ⬜ Not started
**Scan:** `.ai/modules/{name}/scan.md`
**Phase:** {phase number from refactor-plan.md}

> Written by PLAN agent after scan review and health approval.
> Updated in-place during implementation (tick checklist items, update status).

---

## Health decision

> Fill this if scan.md had ⚠️ concerns and the user made a decision.

{What the user decided, e.g. "Replace JournalAction hierarchy with domain event listeners."}

---

## Domain objects

### Models

```
{ModelName}
  Table: {table_name}
  Key fields: [list — exclude standard id/timestamps]
  Relationships: [list]
  Traits: [HasFactory, SoftDeletes, HasChangesLog, ...]
  Implements: [LoggableModel, ...]
```

### Enums

```
- {EnumName}: {description, backed type}
  Cases: [...]
  Implements: [HasLabel, HasColor, HasIcon]
```

### Value objects

```
- {ClassName}: {description}
```

### Domain events

```
- {EventName}: fired when [condition], carries [payload]
```

---

## Commands & Queries

One command per user intent. Not one per CRUD method.

```
Commands:
  - Create{Name}Command → {what it creates/does}
  - Update{Name}Command → {what it updates}
  - Delete{Name}Command → returns bool

Queries (if needed):
  - Get{Name}Query → returns {type}
```

---

## Repository API

Methods beyond standard CRUD (`find`, `findOrFail`, `all`, `create`, `update`, `delete`, `query()`):

```
- {methodName}(...): {return type} — {reason it belongs in repo, not on model}
```

---

## Filament UI

### Resources

```
{Name}Resource
  Model: {ModelName}
  Navigation: label, icon, cluster (if any)
  Pages: [CreateX, EditX, ViewX, ListX]
  Relation managers: [list if any]
```

### Standalone pages

```
{PageName}
  Purpose: [what the user can do here]
  Schema files: [Schemas/{Purpose}Form.php, etc.]
```

### Widgets

```
{WidgetName}: [description]
```

---

## Done checklist

- [ ] Module scaffolded (`dartisan make:module {Name}`)
- [ ] Migration written and tested
- [ ] Domain model(s) in `Domain/Models/`
- [ ] Repository in `Domain/Repositories/`
- [ ] Commands + Handlers for each user intent
- [ ] Queries + Handlers (if applicable)
- [ ] Domain events in `Domain/Events/` (if any)
- [ ] Event/Observer wiring in `Application/Providers/{Name}EventServiceProvider`
- [ ] Filament Resource(s) in `UserInterface/Filament/`
- [ ] Filament pages use `HasCommandBus`, no direct model mutation
- [ ] Translation file `UserInterface/resources/lang/pl/resource.php`
- [ ] Verify grep suite passes (no anti-patterns)
- [ ] `dartisan migrate` passes
- [ ] `pint --dirty` passes
- [ ] `refactor-plan.md` status updated to ✅

---

## Cross-module notes

```
Emits events:
  - {EventName} → consumed by: {module/integration}

Listens to events:
  - {EventName} from {module} → handler: {ListenerClass}

Depends on modules:
  - {ModuleName}: [why / which model is imported]
```
