# r02 — local: Glass attribute dictionaries

Question: What are the glass attribute dictionaries in V1, who maintains them, what rules do they carry, and how does an offer hold them? · V1 commit: 511ce3b

(V1 paths below are relative to /home/kp/laravel/projekty/AllGlass/. Line numbers are from the files read.)

## Summary
- There are four live dictionaries: Producer (`offer_producers`), Colour (`offer_colors`), Glass type "Rodzaj szyby" (`offer_types`), Equipment "Dodatki / Wyposażenie" (`offer_equipment`). All four share one trait, one status workflow and one set of admin pages.
- `OfferExtras` is a dead stub. It has a table, a model and an empty factory, and nothing else uses it. Extras and Equipment do not differ in behaviour. Only Equipment exists in practice.
- Values are picked from the dictionary, never free text on the offer. Non-admins can propose new values in the offer wizard. These are created with `status = Oczekuje zatwierdzenia` and need admin approval. They are tied to the session through `tmp_session`.
- Offer holds producer, colour and type as nullable FK columns with **no DB constraint**, so deleting a value leaves dangling ids. Equipment is many-to-many through `offer_offer_equipment`, also without FK constraints. Only `shipping_method_id` has `nullOnDelete`.
- Every dictionary carries WordPress hooks. Type is the exception: it syncs to WooCommerce as a product category. ShippingMethod is a separate priced list. A glass type may point at one, and an offer may point at one directly. The offer's own value wins, and the type's value is the fallback. The only consumer of the resolved value is the Otomoto advert description.

## Findings

### Four dictionaries share one shape: name, slug, status, tmp_session
- Tag: confirmed · Impact: normal
- What happens: each table has `name` (string, UNIQUE), `slug` (nullable), `status` (enum `Oczekuje zatwierdzenia` | `Aktywna`, DB default Pending), timestamps, and later `tmp_session` (nullable string). Type additionally has nullable `shipping_method_id`. `slug` is filled by a `created` hook as `Str::slug(name)` and saved again. It is not refreshed on rename (the `updated` slug hook is commented out). `status` has two values only (`app/Filament/Enums/ResourceStatus.php`).
- Evidence: database/migrations/2024_10_06_144444_create_offer_colors_table.php, ..._create_offer_producers_table.php, 2024_10_19_214308_create_offer_equipment_table.php, 2024_11_14_175948_create_offer_types_table.php, 2024_12_09_163344_add_tmp_session_for_offer_resources.php:8-24; app/Filament/Traits/IsOfferTaxonomyModel.php:22-27 (created hook sets slug), :29-34 (commented updated hook); 2025_08_19_55555_add_shipping_to_glass_type.php.

### Validation and defaults of the admin form (identical for all four)
- Tag: confirmed · Impact: normal
- What happens: `name` ("Nazwa") is required, must be unique (ignoring the current record), and must match `regex:/^[^\_]+$/`, so **it cannot contain an underscore**. Max length is 255 for Color, Producer and Equipment. **OfferType has no maxLength**, but the DB column is `string` (255). `status` select is visible only to admins and required. Its default is `Aktywna` for an admin and `Oczekuje zatwierdzenia` for anyone else. Because the field is hidden for non-admins, a non-admin's record takes the DB default (Pending). The underscore rule has no stated reason (`ambiguous`; probably a WordPress or slug constraint, not shown).
- Evidence: app/Models/OfferModels/OfferColor.php:27-44, OfferProducer.php:27-45, OfferType.php:35-64, app/Models/OfferEquipment.php:25-41.

### Who maintains them: permission-gated, admin-only approval
- Tag: confirmed (permission names) / unknown (which roles hold them) · Impact: high (permissions)
- What happens: the Filament resources are in cluster Offers (`Kolory`, `Producenci`, `Rodzaje szyb`, `Dodatki / Wyposażenie`). Each action is gated by a policy that calls a Shield-style permission (`view_any_`, `view_`, `create_`, `update_`, `delete_`, `delete_any_`, `force_delete_`, `restore_`, `replicate_`, `reorder_` plus `offer::taxonomies::offer::{color|producer|type|equipment}`). `isAdmin()` means role `Admin` or `Super Admin`. Besides the permissions, the following parts are admin-only by code:
  - the status select;
  - the type's "Metoda wysyłki" select;
  - the WordPress sync section and actions;
  - the offer-form fields type, colour, equipment and shipping for admins. For non-admins in the **edit** form, type and colour are hidden unless `without_eurocode` is set.
- Evidence: app/Policies/OfferColorPolicy.php:17-90 (and the Producer, Type and Equipment policies, same pattern); app/Models/User.php:132-135 isAdmin; app/Filament/Traits/IsOfferTaxonomyView.php (`visible(fn() => Auth::user()->isAdmin())` on the sync sections); OfferEditForm.php:363-401, :431-460.
- Duplicate policy sets exist: `app/Policies/Offer{Color,Producer,Type}Policy.php` and `app/Policies/OfferPolicies/Offer{Color,Producer,Type}Policy.php`, plus `app/Policies/OfferEquipmentPolicy.php`. They check the same permission strings (diffed). Which class Laravel resolves is `unknown`. It makes no behavioural difference.
- Which roles actually hold these permissions is not in the anchors. Role/permission seeding was not examined (`unknown`).
- No bulk-delete guard exists: `IsOfferTaxonomy` gives the list a `DeleteBulkAction` and a row `DeleteAction`. See the delete finding.

### Admin screens: list with "pending" tab, detail page with approve and WordPress sync
- Tag: confirmed · Impact: normal
- What happens:
  - List: a search on name, a status badge, "Liczba ofert" (count of linked offers), a WordPress tick, and filters by status and "Synchronizacja z WP" (all / "Tylko zsynchronizowane" / "Bez synchronizacji"). Tabs are "Wszystkie" and "Oczekujące", with a count badge of Pending rows.
  - Create is a header action on the list. The create and edit pages exist, but their routes are commented out in `getPages()`. Editing is the "Edytuj" action on the view page (a modal with the same form).
  - Detail page: "Informacje" section with name, status and "Liczba powiazanych ofert", and an approve action "Zatwierdź" (confirmation, visible only when Pending). Approving sets `status = Aktywna`, clears `tmp_session`, and shows the notification "Status zaktualizowany". There is also an offers relation manager.
  - Note: `accept()` calls `->save()`, which fires the `updated` hook and so may push to WordPress (see the WP finding).
- Evidence: app/Filament/Traits/IsOfferTaxonomy.php:46-110; IsOfferTaxonomyList.php; IsOfferTaxonomyView.php (header actions, getInfoSection); IsOfferTaxonomyModel.php:38-48 accept; OfferColorResource.php:57-65 (create/edit routes commented out).

### Non-admins can propose new dictionary values from the offer wizard
- Tag: confirmed · Impact: normal
- What happens: in the user wizard step "Specyfikacja i wygląd", selects for type, producer, colour and equipment have `createOptionForm` (name, hidden slug, hidden `tmp_session` = current session id, status for admins only). The new row is Pending and tied to the session. Within the same session the select's query includes `status = Aktywna OR tmp_session = session`, so the proposer sees it. A warning reads "Dodane przez Ciebie pola zostaną zweryfikowane przez administratora przed publikacją oferty." The producer select lists everything except Pending (plus own tmp). Admin edit-form selects hide Pending (colour: Active only; producer: `!= Pending`; type and equipment: Active or own tmp). A non-admin's type select is further limited to names `PRZEDNIA`, `TYLNA`, `BOCZNA`, `SZYBA DACHOWA`. Type and colour options are sorted by popularity (offers_count desc). Producers are sorted by name.
- Evidence: app/Filament/Clusters/Offers/Resources/OfferResource/Components/UserOfferCreateForm.php:175-196 (resourceForm), :213-290; OfferEditForm.php:363-425; OfferCreateForm.php (same pattern per grep).
- This is the offer create/edit flow (r04), and it is noted only to show how values enter.

### Session-temporary rows are cleaned on offer create
- Tag: confirmed (create) / defect? (edit) · Impact: high (deletion)
- What happens: before an offer is **created**, the chosen type, producer or colour has `tmp_session` cleared (it stays Pending and awaits approval). **All other rows with the same `tmp_session` are deleted**, as are unchosen temporary equipment rows. The user's abandoned proposals are hard-deleted. `EditOffer::beforeCreate()` carries the same logic, but `beforeCreate` is a hook of CreateRecord, not of EditRecord. I did not find where it is called on the edit page, so the edit-time cleanup probably never runs. Tag `defect?`, `unknown` without a runtime check.
- Evidence: app/Filament/Clusters/Offers/Resources/OfferResource/Pages/CreateOffer.php:55-80; EditOffer.php:84-115.

### Delete behaviour: no constraint, no nullify, no guard for producer/colour/type/equipment
- Tag: confirmed · Impact: high (deletion)
- What happens: `offers.offer_producer_id`, `offer_color_id` and `offer_type_id` are `foreignIdFor(...)->nullable()` without `->constrained()`. There is therefore **no FK, no cascade and no nullify**. Deleting a dictionary row, via the row action, the bulk action or the edit-page action, leaves offers pointing at a missing id. The relation then returns null, which shows as an empty value. The dictionaries have no soft deletes. `offer_offer_equipment` uses `foreignIdFor` with no constraint either, so pivot rows are orphaned too. The delete actions carry no usage check ("Liczba ofert" is informational only). The only constrained FK is `shipping_method_id` (offers and offer_types), with `nullOnDelete`.
- Evidence: 2024_10_06_151733_create_offers_table.php (relations block); 2024_10_20_145335_create_offer_offer_equipment.php; 2025_08_17_55555_create_shipping_methods_table.php; IsOfferTaxonomy.php:getTableActions (DeleteAction, DeleteBulkAction).
- Other deletion paths (jobs) are out of scope.

### Cardinality and how an offer holds the values
- Tag: confirmed · Impact: normal
- What happens: Offer to Producer, Colour and Type is many-to-one (`belongsTo` with columns `offer_producer_id`, `offer_color_id`, `offer_type_id`). Relation names are `producer()`, `color()` and `type()`. Each is nullable, so an offer may have none. Dictionary to Offer is `hasMany`. Offer to Equipment is many-to-many (`belongsToMany`, pivot `offer_offer_equipment`, which has its own `id` and timestamps). The value is picked (select with `relationship`) and is never free text on the offer. When an offer is replicated ("duplikuj"), the producer and `year` are **excluded** from the copy, while type and colour are kept and equipment is synced. quantity is reset to 1.
- Evidence: Offer.php:498-504, :533-536, :560-562, :606-640; OfferProducer.php:47, OfferEquipment.php:49-52.

### OfferType and ShippingMethod: type holds a default, the offer can override it, Otomoto uses it
- Tag: confirmed · Impact: normal (the price text is customer-facing)
- What happens: `ShippingMethod` is a separate dictionary (`name` unique, `description` nullable, `price` as **string**, `active` boolean default 1). It is maintained in `Metody wysyłki` (create, edit and delete; form labels "Nazwa metody", "Cena", "Opis wysyłki"). The `active` toggle in the form is commented out, so it is always true. The `active` value is never used for filtering (grep: no other use). It has no permission/policy class (the resource uses default behaviour, and no `ShippingMethodPolicy` exists), and no uniqueness beyond the name. A glass type can pick a "Metoda wysyłki" (admin only). An offer can too (admin-only field "Metoda wysyłki" in the offer forms). `Offer::shipping_method` accessor returns the offer's own `shipping` or else `type->shipping`. Its only consumer found is `OtomotoCreationService`. In the Otomoto advert description it prints the user's own `shipping_information` if set, else, if a method resolves, "Wysyłka w 24 godziny w cenie {price}" (hard-coded "24 godziny"). The computed `$price` with " zł" appended is not used in the printed line (the raw `price` is). `OtomotoOptions` also maps shipping methods for Otomoto (r-other scope). Deleting a ShippingMethod nulls both FKs.
- Evidence: app/Models/OfferModels/OfferType.php:30-33,55-60; Offer.php:507-530; app/Services/OtomotoService/Services/OtomotoCreationService.php:142-157; ShippingMethodResource.php:39-60; 2025_08_17/2025_08_19 migrations.
- Whether Shield generates a permission for ShippingMethod is `unknown` (no policy found in app/Policies).

### OfferExtras versus OfferEquipment: only in name
- Tag: confirmed · Impact: normal
- What happens: `OfferExtras` is an empty Eloquent model on `offer_extras` (`id`, `name`, timestamps), with no relation, resource, form, hooks or pivot. Its factory returns an empty array. A grep over app/database/resources/routes found no other reference. Equipment is the live concept ("Dodatkowe wyposażenie" on the offer, "Dodatki / Wyposażenie" in the menu, `accessories` in WordPress). Extras never became a feature (`unreachable`).
- Evidence: app/Models/OfferModels/OfferExtras.php:8-12; 2024_10_10_192433_create_offer_extras_table.php; database/factories/OfferExtrasFactory.php.

### Equipment differs slightly from the other three dictionaries
- Tag: confirmed · Impact: normal
- What happens: Equipment lives in `App\Models\OfferEquipment` (not in OfferModels), and has many-to-many instead of FK columns. Its `canBeSync()` returns false if `name` is empty **or status is Pending**, which is stricter than Color and Producer. Those check only name/slug (written as `empty($this->name || $this->slug)`, which only blocks when both are empty/falsy). Type's `canBeSync()` requires both name and slug.
- Evidence: OfferEquipment.php:54-57; OfferColor.php:57-60; OfferProducer.php:58-61; OfferType.php:80-83.

### WordPress/Woo hooks per dictionary (note only)
- Tag: confirmed · Impact: high (external system)
- What happens:

| Dictionary | Contract | WP endpoint | Meta key | Exported fields |
|---|---|---|---|---|
| Color | WordpressSyncablePost | `glass-colors` | `color` | `title` = name |
| Producer | WordpressSyncablePost | `glass-producents` | `brand` | `title` = name |
| Equipment | WordpressSyncablePost | `glass-accessories` | `accessories` | `title` = name |
| Type | WooSyncableCategory (Woo product category) | n/a | n/a | `name`, `slug`, `parent: 0` |

  - On `updated`, if the row is already synced to WordPress (`wordpressSync` morphOne) with `auto_update` on, and status is not Pending, the change is pushed. Type goes to Woo (`updateProductCategory`), the others to `WordpressApiService::updateModel`.
  - The detail page offers "Synchornizuj" (typo in V1), "Aktualizuj do WP" (rate limited to 5/min per user), and a toggle for auto-update. The sync section is admin-only, and the buttons are disabled while Pending.
  - The offer export puts the offer's `color` and `producer` as terms (`color` and `brand`) and `equipment` as the `accessories` repeater. Type becomes the product's category. The offer's `year`, `rating` (as `condition`), usage state, primary OEM and eurocode go in ACF fields.
- Evidence: model files cited; IsOfferTaxonomyModel.php:51-62; ViewOfferType.php (sync/update actions); app/Services/ApiService/Actions/UpdateProductToWordpressAction.php:36-38, :96-110, :147-160; app/Services/ApiService/DTO/WooProductDTO.php:55-84.
- The reverse direction (Fetch*Jobs import) exists in `app/Services/ApiService/Jobs` and was not examined (outside scope).

### Audit logging of dictionaries
- Tag: confirmed · Impact: normal
- What happens: Color, Producer, Type and Equipment use Spatie `LogsActivity` and log only `name` and `status`. Extras and ShippingMethod are not logged.
- Evidence: OfferColor.php:51-55 (same in the others).

## Glass columns on `offers`

| Column | Meaning | Type / null / default | Duplicates other data? |
|---|---|---|---|
| `eurocode` | Legacy single eurocode string | string, nullable | Yes. The authoritative codes are now the `eurocodes` M:N (`eurocode_offer`, with `priority`). `Offer::primaryEurocode()` reads the relation and the Woo export and the log title use it, not the column. The column is still in `$versionable`, logged, and the label says "Eurocode/Oem". It is probably still written by the form (not verified; r04). |
| `alt_eurocodes` | Alternative eurocodes shown or exported | JSON array (cast `array`), nullable | Yes, partly. Overlaps with the relation. WooProductDTO uses `getAltEurocodes()` (relation) for `eurocode_alt`. |
| `oem` | OEM code typed by the user | string, nullable | Yes. `getPrimaryOem()` takes OEM from `eurocodes.oem_codes`, and the export uses that. The form field `oem` ("Kod OEM") is in the user wizard. |
| `vim` | VIM number ("Vim") | string, nullable | Not found elsewhere. It appears only in the wizard form and the log. |
| `year` | Production year of the glass | string, nullable (free text, placeholder YYYY) | No. It is not the car's year. It is the glass's production year (the log label says "Rok produkcji"). Visible to non-admins only for Used or Outlet, and always to admins. Exported as ACF `year`. |
| `usage_status` | Condition: `Nowa` / `Używana` / `Outlet` | enum, default `Nowa`, not null | No. Exported as ACF `status` via `OfferUsageStatus::acfValue`. |
| `rating` | Condition grade 1-5 stars | integer, default 5, not null | No. The form only shows and saves it when status is Used/Outlet (`dehydrated` only when not `Nowa`), default 1 in the edit form and 5 in the wizard. Exported as ACF `condition`. |
| `quantity` | Number of identical glass items | integer, default 1, not null | Partly. The form allows editing only for `Nowa`, with a helper text pointing to "duplikuj" for used glass. The Woo export ignores it: `stock_quantity` is 1 or 0 by status. (Minimum quantity is computed by `getMinimumQuantity()`, whose definition I did not locate. Reservations are out of scope.) |
| `without_eurocode` | The seller has no eurocode or OEM code | boolean, default 0, not null | No. It switches the form: if set, non-admins see the type and colour selects. The log title becomes "brak eurocodu". `Offer::getWithoutEurocode()` counts such offers. |
| `offer_producer_id`, `offer_color_id`, `offer_type_id` | Dictionary references | bigint unsigned, nullable, no FK | No. They are the only holders of these values. `producer()`, `type()` and `color()` also feed the activity log (`producer.name` etc.) and the Otomoto readiness check. |
| `shipping_method_id` | Offer-specific shipping | nullable FK, nullOnDelete | Overrides `type.shipping_method_id`. |

- Tag: confirmed for the schema and form rules (2024_10_06_151733_create_offers_table.php; OfferEditForm.php:168, :315-361; Offer.php:63-66, :120-134, :152-153, :425-476). The "duplicates" column is `inferred` where it says "probably".
- Cross-flag: Offer `canCreateOtomotoAdvert()` requires type, producer and colour to be present and not Pending, and returns "Szyba nie posiada wymaganych pól lub ma błędy w statusach: …" (Offer.php:244-300). So an empty or Pending dictionary value blocks the Otomoto advert (Otomoto itself is out of scope).
- No other later migration alters the glass columns (grep of Schema::table('offers') found only soft delete and shipping).

## Contradictions and defects
- `EditOffer::beforeCreate()` is probably dead on the edit page (see the temp-row finding). `defect?`
- The `Offer` model has the legacy columns `eurocode`, `oem` and `alt_eurocodes` alongside relation-based eurocodes. `decide` for V2: which is authoritative.
- ShippingMethod `active` is stored and displayed but never edited or filtered. `defect?`
- `ShippingMethod.price` is a free string ("10 zł", "10,00", ...), so it cannot be calculated with. The Otomoto code trims it and tests for a trailing "zł" and then ignores the result. `defect?`
- A single-column unique `name` means a producer "Guardian" and "GUARDIAN" may conflict depending on collation (not verified; MySQL default collation is case-insensitive, `unknown`).
- Deleting a dictionary value with offers attached is allowed without any guard. `decide`.

## Leads outside scope
- `app/Services/ApiService/Jobs/Fetch{WordpressColors,WordpressProducers,WordpressEquipment,WooOfferTypes,WooProducts}.php`: import direction, and `FetchWooProducts::handleRelation` creates dictionary rows from WP.
- `app/Filament/Resources/OtomotoOfferResource/Pages/OtomotoOptions.php`: maps offer types and shipping methods to Otomoto categories and meta (`otomoto_offer_type_mapping`).
- `Offer::getMinimumQuantity()` (grep found no definition in Offer.php; probably a trait) and reservations affect `quantity`.
- `WordpressSync` model (morph target for dictionary sync state).
- Role/permission seeding for `offer::taxonomies::offer::*` permissions.
- `OffersRelationManager` (Shared) used on dictionary detail pages.
- The user wizard `UserOfferCreateForm` and `OfferCreateForm` (r04).

## Could not establish
- Which roles hold the dictionary permissions: role/permission seeders not examined, so a runtime or seeder check is needed.
- Which policy class (App\Policies or App\Policies\OfferPolicies) Laravel resolves. No behavioural impact. Same permission names.
- Whether `EditOffer::beforeCreate` is called by anything (Filament EditRecord has no such hook). Only runtime would settle it.
- Whether the form still writes `eurocode`, `oem` and `alt_eurocodes` on the offer (r04).
- Why names may not contain an underscore. A business or WordPress answer is needed.
- `getMinimumQuantity()` definition.

## Coverage ledger
- Searched: OfferExtras|offer_extras; ShippingMethod|shipping_method|->shipping|shipping_method_id; tmp_session; table('offers'; offer_colors|offer_producers|offer_types|offer_equipment in migrations; OfferColor|OfferProducer|OfferType|OfferEquipment|ShippingMethod|OfferExtras across app/database/routes/config/resources; getWordpressMetaKey|accessories|brand|color in ApiService; Policies|Gate::policy in Providers; isAdmin.
- Examined: app/Models/OfferModels/{OfferColor,OfferProducer,OfferType,OfferExtras}.php; app/Models/OfferEquipment.php; app/Models/ShippingMethod.php; app/Filament/Clusters/Offers/Resources/OfferTaxonomies/{OfferColorResource,OfferProducerResource,OfferTypeResource,OfferEquipmentResource}.php (Producer/Type/Equipment diffed against Color) and their Pages (Color: all four read; Type: List, View read, Create/Edit grep only; Equipment: List, View read; Producer pages: not read individually, structure assumed identical to Color via the resource diff); app/Filament/Clusters/Offers/Offers/Resources/ShippingMethodResource.php and its three pages; migrations create_offer_colors, create_offer_producers, create_offer_types, 2025_08_19_55555_add_shipping_to_glass_type, create_offer_extras, create_offer_equipment, create_offer_offer_equipment, create_offers_table, 2025_08_17_55555_create_shipping_methods_table, 2024_12_09_163344_add_tmp_session; one hop: app/Filament/Traits/{IsOfferTaxonomyModel,IsOfferTaxonomy,IsOfferTaxonomyList,IsOfferTaxonomyView}.php, app/Filament/Enums/{ResourceStatus,OfferUsageStatus}.php, app/Policies/OfferColorPolicy.php (the others diffed), Offer.php (glass-related parts), OfferEditForm.php:160-175 and :310-460, UserOfferCreateForm.php:150-290, CreateOffer.php:55-80, EditOffer.php:40-115, OtomotoCreationService.php:130-175, WooProductDTO.php:55-100, UpdateProductToWordpressAction.php (partial), database/seeders/OfferResourcesSeeder.php, OfferExtrasFactory.php.
- Matched, not examined: Fetch*Jobs, OtomotoOptions.php, ViewOffer.php, OfferCreateForm.php (same pattern as edit form per grep), ViewSubmission.php, WoocommerceApiService, Sync*/Update*CategoryAction.php, SyncProductToWordpressAction.php (the same relations as the Update action, by grep), User.php (shipping_information only). All are out of scope or belong to other missions.
