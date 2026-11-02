# Audit — V1 data migration spec

<!-- Written in Phase 9. V1 side: tables and columns with evidence. V2 side: the information named in
     the functional spec's terms, never V2 column names; whoever builds the import maps those. -->

- **Functional spec:** `.scratch/audit/functional-spec.md` §8 · **Discovery:** `.scratch/audit/discovery/discovery.md`
- **Ticket:** none. Nothing is imported (D-37), so there is nothing to build.

## 1. Sources

V1 root `/home/kp/laravel/projekty/AllGlass/` @ `511ce3b`.

- `changes_logs`: the custom change log, Offer only. Defined in `database/migrations/2025_04_12_181156_create_changes_logs_table.php`.
- `activity_log`: the package activity log for 8 models. Defined in `database/migrations/2024_10_14_193507_create_activity_log_table.php`, with columns added by `…193508_add_event_column…` and `…193509_add_batch_uuid_column…`.
- `versions`: offer snapshots. Defined in `database/migrations/2019_05_31_042934_create_versions_table.php`, with changes from `2020_07_03_163707_add_deleted_at_to_versions.php` and `2021_03_18_160750_make_user_nullable.php`.
- `otomoto_bulk_actions`: Otomoto run history. Not Audit's. It goes with Otomoto, which does not exist in V2 (D-24).
- Log files (`storage/logs/laravel.log`, `otomoto.log`): not database data. They are not migrated.

## 2. Mapping

Every column is dropped. The V2 Change history starts empty on migration day, and V1 history stays only in the V1 database backup (D-37).

| V1 table.column | Meaning in V1 | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `changes_logs.id` | entry id | drop | — | D-37 |
| `changes_logs.user_id` | acting user, null for background work, nulled when the user was deleted | drop | — | D-37 |
| `changes_logs.loggable_type` | logged model class (Offer only) | drop | — | D-37 |
| `changes_logs.loggable_id` | logged record id | drop | — | D-37 |
| `changes_logs.changes` | JSON `{field: {old, new}}` of offer attributes | drop | — | D-37 |
| `changes_logs.message` | Polish summary text | drop | — | D-37 |
| `changes_logs.meta_data` | JSON `{action: created/updated/deleted}` | drop | — | D-37 |
| `changes_logs.created_at` | time of the change | drop | — | D-37 |
| `changes_logs.updated_at` | never meaningfully updated | drop | — | D-37 |
| `activity_log.id` | entry id | drop | — | D-37 |
| `activity_log.log_name` | always "default" | drop | — | D-37 |
| `activity_log.description` | event text | drop | — | D-37 |
| `activity_log.subject_type` | logged model class (8 models) | drop | — | D-37 |
| `activity_log.subject_id` | logged record id | drop | — | D-37 |
| `activity_log.event` | created/updated/deleted | drop | — | D-37 |
| `activity_log.causer_type` | causer class (User) | drop | — | D-37 |
| `activity_log.causer_id` | acting user, null for background work | drop | — | D-37 |
| `activity_log.properties` | JSON old/attributes of the listed fields | drop | — | D-37 |
| `activity_log.batch_uuid` | unused | drop | — | D-37 |
| `activity_log.created_at` | time of the change | drop | — | D-37 |
| `activity_log.updated_at` | not meaningful | drop | — | D-37 |
| `versions.id` | version id | drop | — | D-37, D-06 |
| `versions.user_id` | usually the offer owner, not the editor (r05 #5) | drop | — | D-37, D-06 |
| `versions.versionable_type` | Offer | drop | — | D-37, D-06 |
| `versions.versionable_id` | offer id | drop | — | D-37, D-06 |
| `versions.contents` | JSON snapshot of the offer | drop | — | D-37, D-06 |
| `versions.created_at` | time of the snapshot | drop | — | D-37, D-06 |
| `versions.updated_at` | not meaningful | drop | — | D-37, D-06 |
| `versions.deleted_at` | soft delete, never used by the UI | drop | — | D-37, D-06 |

## 3. New information

There is none to fill: the V2 Change history and the Technical log start empty.

## 4. Data quality

This is why the user chose a clean start (D-37):
- the V1 data has no actor for background writes (BR-02);
- it is missing every change made through quiet saves and every relation change (BR-08, BR-09);
- the activity log and the change log overlap on offers (BR-07);
- type and colour are stored as raw ids.

Row volumes were not checked.

## 5. Run plan

There is no import. On migration day, take a backup of the V1 database that includes these three tables, and keep it under the user's retention for V1 backups.

## 6. Open questions

None.
