# Submissions — V1 Scan

> Written from V1 exploration during agent setup redesign (2026-06-29).
> V1 source: `/home/kp/laravel/projekty/AllGlass/`

---

## Models

### `Submission` (`app/Models/Submission.php`)

```
Table: submissions
Fields:
  - id: bigIncrements
  - title: string
  - user_id: foreignId nullable (set null on delete)
  - offer_id: foreignId constrained (cascadeOnDelete)
  - original_data: json nullable
  - message: text nullable
  - is_read: boolean (added in later migration: 2025_09_06_55555_add_read_status_to_submission.php)
  - timestamps
Casts: original_data => array
Traits: HasFactory
Relationships:
  - belongsTo: User (nullable)
  - belongsTo: Offer
Note: $guarded = []
```

---

## Filament UI

### Resources

```
SubmissionResource (app/Filament/Resources/SubmissionResource.php)
  Navigation label: 'Zgłoszenia'
  Icon: heroicon-o-exclamation-triangle
  Navigation sort: 6
  Navigation badge: count of unread submissions (is_read = false), hidden if zero
  Model: Submission
  Form: empty schema (submissions are created from public-facing form, not admin)
  Table: columns include title, offer link, read status icon, timestamps
  Actions: custom Action to mark as read (inferred from is_read flag)
  Pages: ListSubmissions (likely only list; no create/edit in admin)
  Relation managers: none
```

---

## Services / Actions / Jobs / Listeners

```
No dedicated service, job, or listener for Submissions.
Submission records are created from a public-facing form (not via admin panel).
```

---

## Console commands

None.

---

## Events & Policies

```
Events: likely SubmissionCreated or similar fired on form submit (not confirmed — scan V1 routes/controllers if needed)
Policies: not discovered
```

---

## Migrations

```
Table: submissions
  - id
  - title: string
  - user_id: foreignId nullable (set null on delete)
  - offer_id: foreignId constrained (cascadeOnDelete)
  - original_data: json nullable
  - message: text nullable
  - timestamps

Added by later migration:
  - is_read: boolean default false (2025_09_06_55555_add_read_status_to_submission.php)
```

---

## Cross-module bleeds

```
- Submission belongs to Offer → Offers module dependency
- SubmissionResource links to OfferResource in table row action (V1 cross-resource URL)
- In V2: Submission can reference offer_id FK without importing Offers domain model if kept as raw FK
```

---

## Health Assessment

- [x] Module is fully implemented for its purpose (read-only admin view + public create)
- [x] No mixed concerns
- [x] No abstract hierarchy
- [x] No booted() service calls
- [x] No abandoned features
- [x] Patterns are consistent (simple resource, simple model)

**⚠️ CONCERNS:**

```
1. offer_id FK creates a hard dependency on the Offers module.
   Submissions is Phase 1c but Offers is Phase 2b — not yet built.
   Impact on V2: In V2, keep offer_id as a raw FK column (no Eloquent relationship to Offer model
   until Offers module is built). The relationship can be added in Phase 2b.
   This is not a health concern — just a sequencing note.
```

**→ No blocking concerns. Safe to proceed to PLAN step.**
