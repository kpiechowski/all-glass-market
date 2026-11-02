# Settings — V1 Scan

> Written from V1 exploration during agent setup redesign (2026-06-29).
> V1 source: `/home/kp/laravel/projekty/AllGlass/`

---

## Models

### `Setting` (`app/Models/Setting.php`)

```
Table: settings
Fields:
  - id: bigIncrements
  - key: string
  - value: string
  - user_id: foreignId nullable (purpose unclear — likely unused or for per-user overrides)
  - timestamps
Casts: none
Traits: HasFactory, LogsActivity (spatie/activitylog — NOT available in V2)
Static methods:
  - value(string $key): string|null — fetch by key
  - setValue(string $key, string $value): void — upsert by key
Note: $guarded = [] (all mass assignment allowed)
Note: LogsActivity is a V1-only dependency; V2 uses HasChangesLog from the Audit module instead
```

---

## Filament UI

### Cluster: Settings (`app/Filament/Clusters/Settings/`)

```
Settings (cluster class)
  Contains:
    - ApiSettings page
    - GeneralSettings page
    - MessagesSettings page
    - Shield/RoleResource (spatie/laravel-permission — NOT available in V2)

ApiSettings (app/Filament/Clusters/Settings/Pages/ApiSettings.php)
  Purpose: Configure API keys / credentials (Otomoto, WooCommerce)
  Fields: (not deeply scanned — likely text inputs for API keys)

GeneralSettings (app/Filament/Clusters/Settings/Pages/GeneralSettings.php)
  Purpose: General app configuration (business name, address, etc.)

MessagesSettings (app/Filament/Clusters/Settings/Pages/MessagesSettings.php)
  Purpose: Configurable notification/email message templates
```

### No standalone pages. No widgets. No table resources.

---

## Services / Actions / Jobs / Listeners

```
No dedicated services or jobs for Settings.
```

---

## Console commands

None.

---

## Events & Policies

```
No events fired by Settings.
No policies discovered.
```

---

## Migrations

```
Table: settings
  - id
  - key: string
  - value: string
  - user_id: foreignId nullable
  - timestamps
```

---

## Cross-module bleeds

```
- ApiSettings stores Otomoto and WooCommerce credentials →
  consumed by OtomotoApiService and WoocommerceApiService via Setting::value('key')
- MessagesSettings likely stores notification templates →
  consumed by notification services
- Shield/RoleResource in the Settings cluster is spatie/laravel-permission UI →
  NOT applicable in V2 (no spatie/permission installed)
```

---

## Health Assessment

- [x] Module is fully implemented (three working settings pages)
- [x] No mixed concerns — each page is focused
- [x] No abstract hierarchy
- [x] No booted() service calls
- ⚠️ DEPENDENCY — spatie/activitylog used on model (not in V2)
- ⚠️ CLUSTER CONTAINS SHIELD — RoleResource belongs to Users, not Settings

**⚠️ CONCERNS:**

```
1. Class: Setting model uses LogsActivity (spatie/activitylog)
   Problem: spatie/activitylog is not installed in V2.
   Impact on V2: Replace with HasChangesLog from Audit module, OR omit logging for settings
   (settings changes are low-risk enough to skip audit logging in Phase 1).
   Decision needed: use HasChangesLog or skip audit for Settings?
   Suggestion: skip for now — Audit module is Phase 1a and will be available.

2. Shield/RoleResource in the Settings cluster
   Problem: Depends on spatie/laravel-permission which is not installed in V2.
   V2 uses a custom RoleEnum on the User model instead.
   Impact on V2: Drop this resource entirely. Role management is handled via the Users module.
   No action needed in the Settings module.
```

**→ Concerns are minor and have clear resolutions. Can proceed to PLAN after confirming:**
- Whether to include HasChangesLog on Setting model (recommended: yes, after Audit module is done)
- Confirm Shield/RoleResource is dropped (no spatie/permission in V2)
