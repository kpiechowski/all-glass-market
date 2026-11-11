# 01 - Catalogue module, permission and the six dictionaries

Status: ready-for-agent
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
