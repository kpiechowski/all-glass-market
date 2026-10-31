# {Name} — V1 Scan

> Written by SCAN agent. Read-only after completion — never edit during implementation.
> V1 source: `/home/kp/laravel/projekty/AllGlass/`

---

## Models

For each model: list fields (name + type), casts, relationships, scopes, `booted()` hooks, traits.

```
Model: {ModelName}
Table: {table_name}
Fields:
  - id: bigIncrements
  - ...
Casts: [...]
Relationships:
  - belongsTo: ...
  - hasMany: ...
Traits: [HasFactory, SoftDeletes, ...]
booted() hooks: [none / describe]
Static scopes/methods: [list]
```

---

## Filament UI

### Resources
```
{ResourceName}
  Navigation: label, icon, sort, cluster
  Table columns: [list with types]
  Form fields: [list with types]
  Infolist entries: [list]
  Pages: [Create, Edit, View, List — note any custom pages]
  Actions: [list any table/header/bulk actions]
  Widgets: [list if any]
```

### Standalone pages
```
{PageName}
  Purpose: [what it does]
  Special: [any noteworthy logic]
```

### Clusters
```
{ClusterName}
  Contains: [list resources/pages]
```

---

## Services / Actions / Jobs / Listeners

For each file: one-line description of what it does and which models it reads/writes.

```
Services:
  - {ClassName}: [description]

Actions (Filament):
  - {ClassName}: [description]

Jobs:
  - {ClassName}: [description]

Listeners:
  - {ClassName}: [description, which event it handles]
```

---

## Console commands

```
- {CommandName} (signature: ...): [description]
```

---

## Events & Policies

```
Events fired related to this module:
  - {EventName}: [when / by what]

Policies:
  - {PolicyName}: [which model, which gates defined]
```

---

## Migrations

```
Table: {table_name}
  - id
  - {column}: {type} [nullable / constrained / etc.]
  - timestamps
  Added by later migration: {describe}
```

---

## Cross-module bleeds

Code in other V1 modules/resources that directly references this module's models.

```
- {File}: references {Model} for [reason]
```

---

## Health Assessment

Run through each criterion. Check ✅ or flag ⚠️.

- [ ] Module is fully implemented (no skeleton resources, no empty form schemas)
- [ ] No mixed concerns in a single class (e.g. UI action that also sends notifications)
- [ ] No abstract base class hierarchy where a plain interface or event listener would suffice
- [ ] No `booted()` callback that calls a Service class via `app()` or direct instantiation
- [ ] No half-built feature (migration exists but no model; resource with no pages)
- [ ] No TODO/FIXME comments indicating the feature was abandoned
- [ ] Patterns are consistent with the rest of V1 (not an outlier that needs redesign)

**⚠️ CONCERNS:**
```
[none]

OR describe the concern precisely:
  - Class: {ClassName}
  - Problem: {what is wrong}
  - Impact on V2: {why this matters for the rewrite}
```

If any concerns: **do not proceed to PLAN step — show the user this section and ask for direction.**
