# 09 - Dashboard account tile

Status: resolved
Blocked by: 04

Spec: FR-34. Decisions: D-33.

## What

The first dashboard tile shows:
- the initials avatar;
- "Użytkownik" and the name;
- "Konto firmowe" or "Konto prywatne";
- a sign-out button;
- an "Edytuj profil" link to "Mój profil".

The rest of the dashboard belongs to other features.

## Done when

- The tile renders for Private and Company Users, and both cases are tested.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** FR-34/FR-13 `AccountTile` widget (initials avatar, "Użytkownik" + name, Konto firmowe/prywatne, sign-out, "Edytuj profil" link) with view `users::widgets.account-tile` and `widgets.php` translations; tested for Private and Company users.
- **Deviations:** reads the signed-in read-only `UserModel` from the Filament guard, no new `{X}Queries` method (nothing to query). Sort -10 puts it before Filament's default AccountWidget, which is still registered in AdminPanelProvider (untouched, ticket 06 area); removing it is a follow-up.
- **Seams left:** the default AccountWidget/FilamentInfoWidget removal in AdminPanelProvider.
- **Open questions:** none
- **Check:** composer check green

### Worker — fix 1
- Removed AccountWidget from AdminPanelProvider widgets; added a test that the panel registers AccountTile and not AccountWidget. composer check green.

### Review — 2026-11-01
- **Verdict:** approved after 1 fix round
- **Notes:** Round 1 removed Filament's default `AccountWidget`, which duplicated the tile. The tile reads the signed-in read-only `UserModel`; no `{X}Queries` method was needed. `FilamentInfoWidget` stays until the dashboard feature decides.
