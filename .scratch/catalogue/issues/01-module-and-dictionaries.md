# 01 - Catalogue module, permission and the six dictionaries

Status: resolved
Blocked by: —

Spec: §1, §3, FR-12, FR-13, FR-14, FR-24 (dictionary values, uses inside Catalogue), FR-25 (dictionary value views), RULE-07, RULE-08, §7, WF-04 (dictionary values), §9 events *Producer changed / withdrawn*. Decisions: D-18, D-23, D-25, D-32, D-42, D-43, D-44, D-51.

## What

- **Module foundation:** the Catalogue module, its permission(s) for Admin and Super Admin only (D-42), and the **Katalog** navigation group. Sellers (User) see no Catalogue screen.
- **Six dictionaries:** position, colour, equipment, producer, vehicle make, vehicle body. A value has a name (unique per dictionary regardless of case) and the withdrawn flag. List, create, rename, withdraw, restore, delete.
- **Delete** only while unused. Nothing uses a value yet; leave a seam so tickets 02, 03 and 05 can add their uses (vehicle make/body, Glass model position/colour/equipment), and 09 the offers' use of producers.
- **Events:** *Producer changed* (rename) and *Producer withdrawn*. The fan-out "a renamed position/colour/equipment announces its Glass models" comes with 03.
- **FR-25:** the view of a dictionary value has an extension point where another module may add a section; render nothing now.

## Done when

- FR-12–FR-14 behave as specified for all six dictionaries, refusals included (duplicate name ignoring case, delete while in use through the seam).
- Handler tests per rule, a Livewire test per page, `composer check` passes.

## Comments

### Worker — 2026-11-11
- **Built:** Catalogue module with permission `catalogue.manage` (Admin by grant, Super Admin by wildcard, sellers none; D-42, RULE-07) and the "Katalog" sidebar group. One `DictionaryValue` entity, one table and one repository serve all six dictionaries (`Dictionary` enum is the discriminator); name unique per dictionary ignoring case (RULE-08, backed by a unique `(dictionary, name_key)` index); create, rename, withdraw, restore, delete commands (FR-12, FR-13), each authorised first. Events `ProducerChanged` (only when the name really changes) and `ProducerWithdrawn` (FR-14, WF-04, §9). One Filament resource "Słowniki": a list tab per dictionary, modal create/rename/withdraw/restore/delete, view page with audit history tab (dictionary value registered as an Audit record type). FR-25: `DictionaryValueViewSections` registry renders nothing until a module registers a section factory. Delete-while-unused seam (FR-24): `Domain\Contracts\DictionaryValueUsage` (`usesOf(Dictionary, valueId): list<DictionaryUse>`) registered into `Application\ModuleApi\DictionaryValueUsages` from a provider's boot(); the delete handler refuses with the places listed. Tickets 02/03/05 (inside Catalogue) and 09 (Offers, producers) only implement the contract and call `register()`; they also add translation keys for their `place` label.
- **Deviations:** (1) Edited the existing `AdminPanelProviderTest` (`blocks()` now allows the extra Katalog group; new test for the group). (2) Create/rename are modal actions on the list/view pages, so the resource has no create/edit pages. (3) Rename emits `ProducerChanged` only when the name changes (case change counts). (4) Copied gitignored `public/build` into the worktree so panel render tests run there; not part of the diff.
- **Seams left:** `DictionaryValueUsages::register()` (02, 03, 05, 09); position/colour/equipment rename fan-out "Glass model changed" is a comment in `RenameDictionaryValueCommandHandler` (03); `DictionaryValueViewSections::register()` (integrations, FR-25); `catalogue::uses.*` translation keys to be added by the registering tickets.
- **Open questions:** none
- **Check:** composer check green (779 tests)

### Review — 2026-11-11
- **Verdict:** approved after 0 fix rounds (one mechanical change by the manager: `DictionaryUse` moved from `Domain/ValueObjects` to `Domain/Contracts`, because it is part of the cross-module contract Offers implements in 09, and only `Domain/Contracts` and `Domain/Enums` are shared).
- **Notes:** one `DictionaryValue` aggregate with a `Dictionary` enum serves all six dictionaries. Delete seam: implement `Domain/Contracts/DictionaryValueUsage` and call `DictionaryValueUsages::register()` in the provider's boot(), plus a `catalogue::uses.*` key. The FR-14 rename fan-out for position, colour and equipment is left to 03 (a comment marks it in `RenameDictionaryValueCommandHandler`). FR-25 seam: `DictionaryValueViewSections`. Accepted deviations: modal create and rename (no create or edit pages); `AdminPanelProviderTest::blocks()` now takes the last group as the bottom block.
