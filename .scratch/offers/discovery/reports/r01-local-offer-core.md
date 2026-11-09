# r01 — local: Offer core (data, states, transitions, guards, derived values, hooks)

Question: What is an Offer in V1 as a business object — its data, states and transitions, guards (who/when can edit, view, delete), derived values (price with/without VAT, SKU, thumbnails), model hooks and the events they fire, and what each event causes? · V1 commit: 511ce3b

## Summary
- An Offer is one listing that is also the physical glass: a seller (`user_id`), a net price, a usage state (Nowa / Używana / Outlet), a 1–5 rating, an SKU, eurocodes (pivot, ordered by priority), type / colour / producer / equipment, a photo gallery. `quantity` is a stock count only for "Nowa" glass (prod: 2030 of 2046 offers have quantity 1; 16 have 2–13).
- Nine statuses exist (`OfferStatus`). Paths that set a status are all in Filament actions, the Woo import job and the model's soft-delete hooks. **Expired ("Wygasła") is never set anywhere, and there is no expiry mechanism**: `expires_at` is never written (prod: 0 of 2046 non-null), and the scheduled command `UpdateStatusOfExpiredOffers` is imported in routes/console.php but its file does not exist and it is not scheduled.
- Soft delete and status "Usunięta" are the same act: the `deleted` hook sets status "Usunięta" on every soft-deleted row (prod: all 288 soft-deleted rows are "Usunięta", and no non-deleted row has that status). Restore returns the offer to "Oczekuje zatwierdzenia" (re-moderation).
- A non-admin saving the edit form always sends the offer back to "Oczekuje zatwierdzenia" (or "Szkic" when saved as draft), whatever its previous status.
- Price is stored once. `OfferPriceService` adds 23% for private sellers and leaves the stored price for company sellers (`company_account && has_accepted_terms`); the result is used for Woo, Otomoto and the "Cena brutto" displays.
- Model events: `updated` fires `OfferUpdatedEvent` (→ Otomoto advert refresh, only when Active) and a Woo push (only when `wooSync.auto_update` and status ≠ Pending); sold/archived/deleted fire events that deactivate Otomoto adverts. Versioning is a snapshot per update of 9 fields (status excluded); prod has ≥1 version for every offer.
- Main defects/decisions: no expiry (dead field and enum case), quantity arithmetic in sale/reserve actions, a blanket Pending on any non-admin save, no state guard on several transitions, SKU generator retry bug, `canBeViewByUser` ignores its `$user` argument. See the final sections.

## Findings

### Offer data model (what an offer holds)
- Tag: confirmed · Impact: normal
- What happens: columns on `offers`: eurocode (free-text string typed by seller), alt_eurocodes (json), oem, vim, sku, price decimal(10,2) required, user_description, description, quantity int default 1, usage_status enum Nowa/Używana/Outlet default "Nowa", rating int default 5, year string, image (id of a Media row), gallery json (unused by the model code reviewed), offer_producer_id / offer_color_id / offer_type_id nullable, user_id required, without_eurocode bool default 0, status enum (9 values) default "Oczekuje zatwierdzenia", expires_at, archived_date, sold_date, notes (private), attachments json, deleted_at (later migration). Later additions (shipping_method_id, eurocodes pivot `eurocode_offer` with `priority`) are in other migrations, not examined (Vehicles/Eurocode discovery).
- Prod shape: price 30.00–4300.00, none ≤ 0; 11 distinct sellers; 1 offer without eurocode (`without_eurocode`); 9 without type, 74 without producer, 81 without colour; 532 have `image` set.
- Evidence: database/migrations/2024_10_06_151733_create_offers_table.php:19-52; 2024_12_07_205358_add_soft_delete_for_offers.php:15-17; app/Models/Offer.php:148-154 ($guarded=[], casts attachments/alt_eurocodes array, without_eurocode boolean); prod aggregates.

### Statuses and their meaning (enum)
- Tag: confirmed · Impact: high (state)
- What happens: values (stored as the Polish text, DB enum): Szkic, Oczekuje zatwierdzenia (default), Aktywna, Odrzucona, Wygasła, Zarezerowana (sic, typo is the stored value), Archiwalna, Sprzedana, Usunięta. Display names differ: Archiwalna shows as "Wycofana", Pending as "Oczekuje"/"Oczekuje na zatwierdzenie", Reserved as "Zarezerwowana" (corrected spelling). Prod counts: Aktywna 1409, Sprzedana 317, Usunięta 288, Archiwalna 23, Zarezerowana 5, Odrzucona 4; Szkic / Oczekuje / Wygasła 0 at dump time.
- Evidence: app/Filament/Enums/OfferStatus.php:9-17 (values), :53-81 (tableRowName, userFriendlyName); migration :43 (enum from `OfferStatus::toArray()`); prod GROUP BY status.

### Status transition table (every code path that sets `offers.status`)
- Tag: confirmed (each row; paths listed with evidence) · Impact: high
- Entry:
  - → Szkic: create form with "Zapisz ofertę jako szkic" on (admin or user) — CreateOffer.php:36-37; edit form with the same toggle on (admin or user, from any status the form can be opened in) — EditOffer.php:54-56.
  - → Oczekuje zatwierdzenia: create by non-admin when not draft — CreateOffer.php:38-39; edit by non-admin when not draft, from ANY current status — EditOffer.php:57-58; Szkic → activation by non-admin (`ActivateFromDraftAction`, also creates a Submission and notifies admins) — Journal/ActivateFromDraftAction.php:31-39; restore from soft-delete — Offer.php:183-186; replicate ("Pomyślnie skopiowano ofertę") always sets Pending, quantity 1, current user as owner — Offer.php:644-649.
  - → Aktywna: Pending/Rejected accepted by admin (`OfferAcceptedAction`; single button in AdminTab for Pending and Rejected; bulk "Zaakceptuj oferty" only for offers in Pending or Rejected via `OfferStatusAggregate::canOfferBeAccepted`; submission "Rozwiąż zgłoszenie" with new_status Aktywna) — Journal/OfferAcceptedAction.php:24; AdminTab.php:107-161; AcceptOffersBulkAction.php:33; ViewSubmission.php:482. Admin activating own draft → Active directly (ActivateFromDraftAction:31). Admin creating/editing offers keeps the status chosen in the admin "Status oferty" select (default Active) since only non-admins are forced to Pending — OfferEditForm.php:60-70, CreateOffer.php:38. Partial sale or partial reservation sets (or keeps) Active and sets `sold_date = null` — OfferAmountSoldAction.php:35-37, OfferAmountReservedAction.php:35-37. Cancelling a reservation → Active (owner of the reservation only) — StockSaleTab.php:117, UserReservationSection.php:62. Sale return ("Zwrot sprzedaży", admin) → Active — StockSaleTab.php:167. Woo import of an in-stock, non-reserved product → Active — FetchWooProducts.php:109.
  - → Odrzucona: admin "Odrzuć ofertę" (reason required) from Pending or Active; submission resolution — OfferRejectedAction.php:29; AdminTab.php:107-134; ViewSubmission.php:485.
  - → Zarezerowana: when a reservation is created and `quantity - total reserved == 0` — OfferReservationChangeAction.php:34-40 → OfferFullyReservedAction.php:35; Woo import with ACF reservation true — FetchWooProducts.php:107.
  - → Sprzedana (+ `sold_date = now()`, fires `OfferSoldEvent`): a sale is recorded and `quantity - total sold == 0` — OfferStockChangeAction.php:36-41 → OfferFullySoldAction.php:37-40. Woo import with `outofstock` or stock 0 → Sprzedana, quantity forced to 1, plus an `OfferSale` row labelled 'Status sprzedaży z wordpress' — FetchWooProducts.php:103-105,141-147.
  - → Archiwalna (shown "Wycofana", fires `OfferArchivedEvent`): admin stock action "Wycofaj szybę" ("Spowoduje to zmianę statusu w Wordpress oraz Otomoto") — StockSaleTab.php:~190-200 → OfferArchivedAction.php:31-33. No `archived_date` is written (prod 0 non-null).
  - → Usunięta + `deleted_at` (fires `OfferDeletedEvent`): any soft delete — Offer.php:163-176.
  - → Wygasła: **no code path** (see expiry finding).
- Guards of the actions that cause these (who can see the buttons) are in r02/r03 scope; the ones relevant here: status-changing admin buttons are shown only to admins on the Administracja tab; stock actions only when `allowedToStockChange()` (available quantity ≥ 1 and status Active or Reserved) — Traits/HasSalesRecords.php:51-65.
- No central state machine: none of the setters validates the current status (only the bulk-accept partition uses `OfferStatusAggregate`). See Contradictions.

### Expired status and expiry mechanism
- Tag: confirmed (absence) · Impact: normal
- What happens: `Wygasła` exists in the enum, is allowed by `canBeEdited` and the Woo allow-lists, but nothing sets it. `expires_at` has a read-only display (AdminTab TextEntry) and a DatePicker in UserOfferCreateForm; `OfferStatus::Expired` is never assigned. routes/console.php imports `App\Console\Commands\UpdateStatusOfExpiredOffers` but the class file does not exist in app/Console/Commands, and it is not scheduled (only the Otomoto twice-daily command and a queue worker every minute are). `Offer::getExpired()` is defined but never called outside a commented block. Prod: 0 of 2046 offers have `expires_at`, 0 have status Wygasła.
- Evidence: routes/console.php:7,14-22; ls app/Console/Commands (5 files, none for expiry); grep for `OfferStatus::Expired` shows only Offer.php lists; migration :44; Offer.php:589-592; prod: SUM(expires_at IS NOT NULL)=0.

### Soft delete vs status "Usunięta"
- Tag: confirmed · Impact: high (deletion)
- What happens: deleting an offer (row Delete action, bulk delete, or the Delete button on the view/edit page) soft-deletes it. The `deleted` hook then (unless force-deleting) writes `status = Usunięta` and `deleted_at = now()` quietly (no `updated` event), logs, fires `OfferDeletedEvent`, then calls the Woo sync action (Woo product set to out of stock because Deleted counts as a "sold" status). So a soft-deleted offer is always both. Admin sees trashed offers and can "Przywróć ofertę"; "Usuń permanentnie" (admin only, only when trashed) first deletes the Woo product with `force=true` and removes the local sync row, then force-deletes: versions are removed with it and all media are deleted by the media library. Bulk "Usuń na zawsze" on the full list / user reserved page does the same (Woo delete then forceDelete) with no status check.
- Restore: `restored` hook sets status Oczekuje zatwierdzenia and `save()` (fires `updated`; Woo push is skipped because Pending). The restore confirmation text says "Ta akcja jest nieodwracalna, nie będzie można przywrócić usuniętej oferty" — copy-paste of the permanent-delete wording (defect?).
- Who can delete: `OfferPolicy::delete` = admin or owner; the UI shows the delete buttons only when `canBeEdited()`, i.e. not for Zarezerowana, Archiwalna, Sprzedana, Usunięta. Bulk delete needs permission `delete_any_offer`; force delete `force_delete_offer`; restore `restore_offer` (Spatie permission names; actual role grants not examined here).
- Evidence: Offer.php:163-186; ViewOffer.php:79-84,451-479; FullListOffer.php:158-165; UserReservedOffersPage.php:108-116; OfferPolicy.php:63-107; vendor/overtrue Versionable.php:54-60 and spatie InteractsWithMedia.php:51-62 (library behaviour); prod: 288 soft-deleted all "Usunięta".

### Who can view, edit, delete (guards)
- Tag: confirmed · Impact: high (permissions)
- What happens:
  - View: the offer page (`ViewOffer::authorizeAccess`) calls `canBeViewByUser`: admin → yes (including trashed); otherwise a trashed offer → no (403); otherwise only the owner. Note the method checks `Auth::user()->isAdmin()` and ignores the `$user` argument for the admin test (equal in practice). `OfferPolicy::view` is admin or owner (does not look at trashed). `viewAny` is admin-only when the route URI is `admin/glass/offers` (the full list), true otherwise.
  - Edit: `canBeEdited()` is true for Szkic, Aktywna, Odrzucona, Oczekuje zatwierdzenia, Wygasła (not Zarezerowana, Sprzedana, Archiwalna, Usunięta). It gates the Edit/Delete buttons on lists and the non-admin Edit button on the view page (admin and the owner of a Szkic get the Edit button regardless of `canBeEdited`, hidden only when trashed). The edit page itself is not gated by `canBeEdited` in anything examined, only by `OfferPolicy::update` (admin or owner) — so a Sold offer is reachable by URL for its owner (unknown whether Filament resource-level canEdit adds a check; r02).
  - Non-admin edit warning: "Pamiętaj!" / "Jeśli dokonasz zmian w ofercie, będziemy musieli ponownie ją zweryfikować." and the save sets Pending (see transitions).
  - Create: `OfferPolicy::create` = permission `create_offer`.
- Evidence: Offer.php:355-384; ViewOffer.php:60-65,427-448; OfferPolicy.php:21-67; EditOffer.php:50-60.

### Edit by a non-admin triggers re-moderation and a Submission
- Tag: confirmed · Impact: high (state)
- What happens: after a non-admin saves an edit, `OfferEditedByUserAction` forces rating 5 when usage is "Nowa", re-matches eurocode input (or clears eurocodes when `without_eurocode`), creates a Submission for admins and notifies them. Creation by a non-admin (`OfferCreatedByUserAction`) does the same with a Submission, or a "draft created" journal entry when Szkic. Submissions/journal belong to r03/r04; noted only because it is part of the status flow.
- Evidence: EditOffer.php:101-121 (afterSave); OfferEditedByUserAction.php:29-60; OfferCreatedByUserAction.php:34-60.

### Quantity semantics (single piece vs stock)
- Tag: confirmed · Impact: high (state)
- What happens: `quantity` is the stock count; the form field "Ilość szyb" is visible only when usage is "Nowa" (helper: "Pole dostępne wyłącznie dla „Nowych” szyb. W przypadku szyb używanych skorzystaj z funkcji „duplikuj” na liście ofert."). Minimum allowed value = total sold + total reserved (so stock cannot drop below committed). Available = quantity − sum(sales.quantity) − sum(reservations.quantity). Replication resets quantity to 1. Prod: 2030 offers quantity 1; 11 "Nowa" and 5 "Używana" offers have quantity > 1 (max 13); no "Outlet" with quantity > 1.
- Sale/reservation of N pieces requires 0 < N ≤ available, else error "Operacja nie powiodła się" / "Wprowadzona ilośc jest niezgodna z obecnym stanem". Selling the last pieces gives Sprzedana; otherwise Aktywna with a journal entry "Sprzedaż Twojej oferty!". Reserving all remaining gives Zarezerowana; a partial reservation keeps/sets Aktywna.
- Woo import: quantity = stock_quantity or 1 when empty; forced to 1 for sold items.
- Evidence: Traits/HasSalesRecords.php:26-48; OfferEditForm.php:343-352; Offer.php:644-649; OfferStockChangeAction.php:18-47; OfferReservationChangeAction.php:15-43; FetchWooProducts.php:99-105; prod GROUP BY quantity.

### Usage status and rating
- Tag: confirmed · Impact: normal
- What happens: `usage_status` ∈ Nowa / Używana / Outlet (default Nowa); required in the form ("Stan szyby"). `rating` ("Ocena stanu szyby", 1–5 stars) is shown only for Używana/Outlet and is only submitted when usage ≠ Nowa; a non-admin edit of a Nowa offer forces rating 5. Default in DB is 5; prod shows Nowa → 696 rating 5, 1 rating 4. For Woo, Używana and Outlet both map to ACF "used", Nowa → "new". "Krótki opis szyby" (description) is shown for Używana/Outlet. "Rok produkcji" is shown for used/outlet and always for admins. Photos, VIN and etc. not covered.
- Evidence: Enums/OfferUsageStatus.php:9-11,49-56; OfferEditForm.php:325-352 (visibility, Rating min 1 max 5, default 1, dehydrated only when ≠ Nowa); OfferEditedByUserAction.php:44-49; prod GROUP BY usage_status, rating.

### Price, VAT and derived gross price
- Tag: confirmed · Impact: high (money)
- What happens: stored `price` (decimal 10,2; form field "Cena", required, numeric, min 1, suffix "zł", commas stripped). `OfferPriceService::getAdjustedOfferPrice(price, owner)`: if no owner, or owner `isCompanyAccount()` (`company_account` and `has_accepted_terms` both truthy) → price unchanged; otherwise `round(price × 1.23, 2, HALF_UP)`. So the stored price behaves as "the amount the seller wants / net" and for private (non-company) sellers VAT 23% is added on top. The multiplier 1.23 is hard-coded (not a Setting) — confirms the earlier Settings note. "Cena brutto" is shown (read-only) in the edit form and view page only for non-company owners; admins see a warning on the view page for non-company sellers: "Uzytkownik tej oferty nie zaznaczył oświadczenia sprzedawcy.". The adjusted price is what is sent to Woo (`regular_price`), to Otomoto adverts (create, update, bulk) and shown in lists.
- The service is registered as a singleton.
- Bulk price change (admin, "Zmień cenę"): modes "Kwota (±zł)", "Procent (±%)", "Cena stała (zł)" (fixed min 1); new price ≤ 0 → offer skipped with "Pominięto N ofert – nowa cena wyniosłaby 0 zł lub mniej."; saves quietly (no version), then jobs in chunks of 15 re-save with the final price so a version, an activity-log entry and the Woo push happen, and then update Otomoto adverts directly. (`BulkPriceUpdateSyncJob`: first quietly reverts to the previous price, then saves final price.)
- Evidence: Services/Offer/OfferPriceService.php:13-20; Models/User.php:177-180; Providers/AppServiceProvider.php:25; DTO/WooProductDTO.php:59; OtomotoService/Actions/UpdateAdvertAction.php:27, UpdateCoreAdvertData.php:30, BulkCreateAdvertsAction.php:52; OfferEditForm.php:262-295; ViewOffer.php:236-249; ChangePriceBulkAction.php (whole); Jobs/Offer/BulkPriceUpdateSyncJob.php:28-48.

### SKU generation
- Tag: confirmed · Impact: normal
- What happens: on create the package generates the SKU `AG` + 8 random digits (e.g. 10 chars, "AG10504338"); regenerated never on update (`refreshOnUpdate(false)`); the configured source field `name` does not exist on offers so the "AG" is effectively a separator between an empty source and the signature. Uniqueness is forced by re-checking against all rows incl. trashed. An admin can overwrite the SKU manually on creation (CreateOffer `afterCreate` writes `$data['sku']` quietly when filled). SKU is the route key (URLs use the SKU). Prod: 1563 of 2046 have the `AG########` form; the rest are numeric ids or legacy Woo SKUs (483 with other lengths, 110 neither AG nor purely numeric) — Woo-imported offers keep the Woo SKU. No duplicate SKUs in prod.
- Evidence: Offer.php:55-58,386-394; vendor binary-cats SkuMacro.php:15-27, SkuGenerator.php:46-92, SkuObserver.php:21-29; CreateOffer.php:84-88; FetchWooProducts.php:149-152,173-176; prod aggregates.
- Defect?: SkuGenerator::makeSku retry on collision calls `makeSku($source, $unique)` (passes the boolean as separator, so a retried SKU would be "1"+digits, 9 chars, and is not re-checked). Probability tiny; not seen in prod (no 9-char AG-less pattern verified).

### Photos, thumbnails and media collections
- Tag: confirmed · Impact: normal
- What happens: media collections in use: `offer-gallery` (public photos, max 10 files in the form, reorderable; first by `order_column` is the main photo; admin can "select main image" which swaps order with the previous first — ViewOffer::selectImage) and `offer-private-gallery` (seller-only, max 10, "Uwaga! Prywatne zdjęcia nie zostaną opublikowane w ofercie. Będą one widoczne wyłącznie dla Ciebie."). Prod: 8390 gallery media, 297 private media, all on disk `public`; 7 offers have no gallery media at all. Conversions: `preview` (Fit::Contain 300×300, non-queued) and `large` (Fit::Contain 1024×1024, queued). `Offer::getGallery()` returns `offer-gallery`. `image` column (Media id) is set only by the Woo import (first image) and read by `getThumbnailImage()`; list tables use the first `offer-gallery` media or an `offer-thumbnails` collection reference in table code (no such media exist in prod → the collection is legacy/unused). Gallery is also the source of images pushed to Woo (first image as thumbnail, all in gallery) and Otomoto.
- Evidence: Offer.php:189-200,406-418; OfferEditForm.php:140-160,475-486; ViewOffer.php:500-520; Services/ApiService/Actions/UpdateProductToWordpressAction.php:58-80; FetchWooProducts.php:195-225; prod GROUP BY collection_name; FullListOffer.php:520 (offer-thumbnails).
- Unknown: whether `offer-thumbnails` exists anywhere in storage (0 rows in the media table for Offer).

### Versions (overtrue/laravel-versionable)
- Tag: confirmed · Impact: normal
- What happens: strategy SNAPSHOT; versioned fields: price, eurocode, user_description, description, usage_status, rating, without_eurocode, alt_eurocodes, year. Excluded: id, user_id, created_at, updated_at, status. Behaviour of the package: an initial version on create, an initial version created before the first update if none exists, a new version after every `updated` (only when a versioned field is dirty, per package `shouldBeVersioning`); `keep_versions = 0` (unlimited). The admin edit page has a "Historia zmian" style revisions action (RevisionsAction, hidden for non-admins) to a revisions page (`/{record}/revisions`). Not versioned: status, quantity, type/colour/producer ids, equipment, eurocodes pivot, gallery. Prod: every one of the 2046 offers has ≥ 1 version.
- Evidence: Offer.php:125-146; config/versionable.php; vendor Versionable.php:29-66; EditOffer.php:35-37; Pages/OfferRevisionsPage.php; OfferResource.php:297; prod versions aggregate.
- Note: the changes-log (`HasChangesLog`, `$loggable=['*']` minus id/sku/user_id/timestamps) and spatie activitylog (`logOnly` price, eurocode, usage_status, rating, year, type.name, producer.name, color.name) are two further audit mechanisms on the same model; handled by the Audit module, listed here for completeness. Labels in `$logFieldLabels` (e.g. "Kod OEM", "Stan szyby", "Eurokody zatwierdzone", "Bez eurokodu").

### Model hooks and what the events cause
- Tag: confirmed · Impact: high (external systems)
- What happens:
  - `updated` (every save with changes, not saveQuietly): fires `OfferUpdatedEvent` and runs `afterUpdateSyncAction`. The event's only listener `UpdateAdvertAfterOfferChange` (queued) refreshes the Otomoto advert core data (price from the adjusted price service, description) for all the offer's adverts, but only if the offer has an Otomoto offer and status is Aktywna.
  - `afterUpdateSyncAction` (also called after soft delete): pushes the offer to WooCommerce (`updateProduct`) only if the offer already has a Woo sync row with `auto_update` on, status is not Oczekuje zatwierdzenia, and status ∈ Aktywna, Odrzucona, Zarezerowana, Wygasła, Sprzedana, Archiwalna, Usunięta (Szkic excluded, so effectively "anything but Draft/Pending"). The Woo payload: reserved flag true when status Zarezerowana; stock_status `outofstock` for Sprzedana, Usunięta, Archiwalna, else `instock`; stock_quantity 0/1 (manage_stock true — Woo always treats the glass as a single piece regardless of `quantity`). If Woo answers "invalid id" (400, woocommerce_rest_product_invalid_id), the local sync row is deleted. Prod: 2155 Woo sync rows, 2154 with auto_update (more sync rows than offers: 2046; includes soft-deleted offers' rows or rows of other types — not investigated).
  - `OfferSoldEvent`, `OfferArchivedEvent`, `OfferDeletedEvent` (queued listener `DeactivateAdvertListener`): for each Otomoto advert of the offer, deactivate it. The deleted event restores trashed models when the queued job is deserialised. These events are fired only by the actions listed above (not by hooks), except Deleted which is fired by the `deleted` hook; accepting, rejecting, reserving do not touch Otomoto.
  - `forceDeleted`: `clearMediaCollection()` (default collection only; effectively redundant as the media library already removes all media on force delete).
  - `restored`: status → Oczekuje zatwierdzenia, `save()`.
  - Saves with `saveQuietly()` skip all of this: bulk price change first pass, Woo import final save, SKU override, rating normalisation in `OfferEditedByUserAction`, OEM sync job.
  - Provider registration: `OfferEventServiceProvider` is registered in bootstrap/providers.php:10.
- Evidence: Offer.php:156-227; Services/Offer/OfferEventServiceProvider.php:20-35; bootstrap/providers.php; OtomotoService/Listeners/UpdateAdvertAfterOfferChange.php:26-45 and DeactivateAdvertListener.php:25-38; DTO/WooProductDTO.php:73-98; ApiService/WoocommerceApiService.php:131-155.
- Defect?: the Otomoto refresh fires on every update including the quiet-bypassing ones' follow-ups; and a non-admin edit sets Pending in the same save, so the Otomoto advert is NOT refreshed (status ≠ Active) and Woo is NOT updated (Pending) — the live listings keep the old data until an admin accepts, and after acceptance (status-only save) the price/description changes reach Woo (the status flip is an `updated`) and Otomoto. Confirmed by reading, not observed at runtime.

### Eligibility rules for syncs (business conditions)
- Tag: confirmed · Impact: high (external systems)
- What happens:
  - `canBeWooSynch()` returns true or the Polish reason: (1) "Oferta posiada synchronizację z Wordpressem" when a sync row exists; (2) "Status produktu nie pozwala na synchronizację" unless status ∈ Aktywna, Zarezerowana, Wygasła, Sprzedana; (3) "Produkt nie posiada wymaganych pól, aby zsynchronizować z WordPressem: …" listing missing `eurocode` (primary eurocode code), `regular_price` (price empty or ≤ 0), `sku`; (4) "Produkt nie posiada wymaganych pól lub ma błędy w statusach: …" per missing or pending (status "Oczekuje zatwierdzenia") type / producer / colour with labels "Typ szyby", "Producent szyby", "Barwa szyby" and suffix " - brak" / " - oczekuje".
  - `canCreateOtomotoAdvert()`: status must be Aktywna or Zarezerowana ("Status produktu nie pozwala na synchronizację"); must have at least one car via its eurocodes ("Oferta nie posiada powiazanego pojazdu"); type, producer and colour present and not pending ("Szyba nie posiada wymaganych pól lub ma błędy w statusach: …").
  - Eurocode-derived cars: cars joined through `car_eurocode` and `eurocode_offer` (distinct) — attribute detail is in the Vehicles discovery.
  - `Offer::wooSync` is a hasOne to `WooCommerceSync`; `otomotoOffer` is the latest of many `otomotoAdverts`.
- Evidence: Offer.php:250-353,479-496; callers: WoocommerceApiService.php:106, BulkCreateAdvertsAction.php:37, OtomotoCreationService.php:28, WooSyncBulkAction.php:31, BulkAdvertCreateAction.php:30.

### Business sets (static getters and scopes)
- Tag: confirmed · Impact: normal
- What happens: `getArchived`, `getPendings`, `getActives`, `getExpired`, `getSold` are plain COUNT of the status (soft-deleted rows excluded by the SoftDeletes scope, though they have status Usunięta anyway). `getWithoutEurocode` counts `without_eurocode = 1`. Used as tab badges on the admin full list (Pendings, Actives, Sold, Archived, WithoutEurocode); `getExpired` has no caller. Other business sets in code: "reserved offers" lists filter `status = Zarezerowana` (admin and user pages); the full lists hide Szkic by default through a filter (`status != Szkic` unless the toggle is set). `scopeWithShipping` eager loads shipping. Shipping method: offer's own `shipping_method_id` else its type's shipping (`getShippingMethodAttribute`); seller info `getUserShippingInformation` from the user.
- Evidence: Offer.php:507-531,576-600; FullListOffer.php:93-111,213-214; OfferResource.php:253-254; AdminReservedOffersTable.php:50; UserReservedOffersPage.php:57,70.

### Replication ("duplikuj")
- Tag: confirmed · Impact: normal
- What happens: copies an offer (any status where the action is shown) into a new one owned by the current user, status Oczekuje zatwierdzenia, quantity 1; excludes sku (a new one is generated), producer, image, year, status, expires_at, archived_date, sold_date, notes, deleted_at; copies equipment and the ordered eurocodes with priorities; redirects to the edit page. Producer is cleared (must be chosen again); type and colour are kept. Used as the way to list further used pieces ("duplikuj").
- Evidence: Offer.php:608-657.

### Link to eurocode relations (only the call sites)
- Tag: confirmed · Impact: normal
- What happens: on create/edit by user, the offer's free-text `eurocode` is normalised (trim, upper-case, whitespace removed) and matched against `Eurocode.code` or an OEM code (`orWhereJsonContains('oem_codes')`); a match is attached via the `eurocode_offer` pivot. `findBestRelatedOfferByEurocodeId` and `findRelatedOfferByEurocodes` pick the most recent non-Pending offer (trashed included) sharing the eurocode to copy colour/type/producer/equipment from (ordered by created_at, then by number of filled attributes + equipment count). Pending offers are never a donor. Detail is in the Vehicles discovery. `assignRelationsByEurocode` is an empty stub.
- Evidence: Services/Offer/OfferEurocodeRelationService.php:14-83; OfferCreatedByUserAction.php:50-80.

### Woo import of offers (creates offers with a status)
- Tag: confirmed · Impact: high (state)
- What happens: `FetchWooProducts` pages through Woo products and creates an Offer per product (owner = Setting `woo-offers-default-user`, default 1), maps stock status (outofstock / 0 → Sprzedana with an OfferSale row, ACF reservation → Zarezerowana with an OfferReservation row, otherwise Aktywna), `without_eurocode` when the ACF eurocode is empty, price 0.0 when missing (bypassing the form's min 1), imports images to `offer-gallery` with the first as `image`, and creates the sync row. Keeps the Woo SKU and creation date. Not deduplicated by SKU in the code read (every run creates new offers — not verified how it is invoked).
- Evidence: Services/ApiService/Jobs/FetchWooProducts.php:97-180.

### Other jobs touching offers
- Tag: confirmed · Impact: normal
- What happens: `OfferUpdateService::handleUsersPublishedOffer(User)` pushes all of a user's offers that have a Woo sync to Woo in jobs of 10 (`OfferUpdateOffersChunk`); no caller found in the files examined (grep in scope only — cross-repo mission to confirm). `SynchOemCodeFromWordpressJob` fills an empty `oem` from the Woo ACF field by SKU (quiet save). `BulkPriceUpdateSyncJob` described above.
- Evidence: Services/Offer/OfferUpdateService.php:11-18; Jobs/Offer/*.php.

## Contradictions and defects
- Expired is a dead state: enum case, in two allow-lists, `getExpired()`, `expires_at` column and form field, imported-but-missing command. Decision needed for V2 (drop, or define the expiry rule). (decide)
- Partial-sale arithmetic: `OfferStockChangeAction` computes left = quantity − total sold and compares `=== 0` with the result of `sum()`; reservation computes left = quantity − total reserved ignoring sales and sold ones. With quantity 2, 1 sold and 1 reserved, left = 1 → status stays Aktywna although nothing is available; with strict `===` an int/string type mismatch could also prevent Sold/Reserved. (defect?)
- Cancelling a reservation or returning a sale sets status Aktywna unconditionally (even if other reservations remain, or the offer is still fully reserved/sold by others); sale return leaves `sold_date` set (prod: 3 Aktywna offers have a `sold_date`) and a sale row is zeroed instead of removed. (defect?)
- Prod: 3 Sprzedana offers have no sale row (data created before sales records / manual status). (observed in data)
- A non-admin saving an Aktywna/Odrzucona/Wygasła/Szkic offer always lands in Pending/Szkic, and the Pending skip in `afterUpdateSyncAction` means external listings of an edited offer are not touched until acceptance; however the price/description of Woo/Otomoto is out of step. (inferred from the code; no runtime check)
- `canBeEdited()` hides buttons but does not guard the edit route; see "Who can ... edit". (unknown whether other layers guard it; r02)
- `canBeViewByUser(User $user)` uses `Auth::user()` for the admin test and `$user` for the owner test.
- Restore modal text repeats "nie będzie można przywrócić" for a restore.
- Status value typo "Zarezerowana" is baked into the DB enum and data (5 rows); V2 needs a mapping.
- Soft-deleting an offer with an active Otomoto advert deactivates the advert (event) but the Woo product is merely set out of stock and not deleted (only the permanent delete removes it).
- Table/enum name mismatches: `tableRowName` "Wycofana" for Archiwalna while the stored value is "Archiwalna".
- ViewOffer line `->after(fn($livewire) => dd($livewire))` on the Pending "Odrzuć ofertę" action in AdminTab.php:127 — a debugging `dd` after rejecting from a Pending offer. (defect)

## Leads outside scope
- Journal entries and database notifications created by every journal action (`JournalAction::__invoke` creates a user journal entry and an in-app notification to the seller): r03.
- Submissions created on user create/edit/activate: r04.
- `woo_commerce_syncs` has 2155 rows vs 2046 offers: cross-repo/Woo module should check orphan or duplicate rows.
- `UpdateStatusOfExpiredOffers` import in routes/console.php: whole-repo census (r05) should confirm there is no other scheduler.
- `OfferUpdateService::handleUsersPublishedOffer` has no caller in scope: r05.
- `OfferStatusAggregate` has a single method used only by the bulk accept: r02.
- Policy permissions (`create_offer`, `delete_any_offer`, `force_delete_offer`, `restore_offer`, `replicate_offer`, `reorder_offer`): which roles hold them is in the seeded permission data, not examined.
- Filament forms (admin tab, stock tab, shouts, filters, widgets): r02.

## Could not establish
- Whether the edit route is blocked for Sold/Reserved/Archived offers by the resource-level `canEdit` (r02 files not read). Runtime or the r02 report settles it.
- Whether the `public` conversions (`preview`, `large`) exist on disk for all offers; not checkable from the DB alone.
- How often / by whom `FetchWooProducts` is invoked and whether it deduplicates (belongs to Woo module).
- Why 288 vs 288 check showed 0 for a shell comparison on "Usunięta" with a non-ASCII literal (client charset); the first GROUP BY already shows all 288 soft-deleted rows are "Usunięta", so I rely on that.
- Meaning of `gallery` json and `attachments` json columns: no reads or writes found in scope (probably legacy before media library). Not verified across the repo.

## Coverage ledger
- Searched: `OfferStatus::`, `OfferSoldEvent|OfferArchivedEvent|OfferDeletedEvent`, `OfferStatusAggregate`, `OfferPriceService|getAdjustedOfferPrice`, `expires_at|archived_date|sold_date`, `UpdateStatusOfExpiredOffers`, `canBeEdited|canBeViewByUser|canBeWooSynch|canCreateOtomotoAdvert`, `getReplicateTableAction`, `DeleteAction|forceDelete|restore`, static getters, `offer-gallery|offer-thumbnails|offer-private-gallery`, `getMinimumQuantity`, `saveQuietly`-style paths via the status setters; prod aggregates on offers, versions, media, woo_commerce_syncs, offer_sales, offer_reservations.
- Examined: app/Models/Offer.php (full); app/Filament/Enums/{OfferStatus,OfferUsageStatus,ResourceStatus}.php; app/Services/Offer/** (OfferUpdateService, OfferPriceService, OfferEurocodeRelationService, OfferEventServiceProvider, Aggregates/OfferStatusAggregate, Events/*); app/Jobs/Offer/*; app/Policies/OfferPolicy.php; migrations create_offers_table, add_soft_delete_for_offers, add_tmp_session_for_offer_resources (only touches taxonomy tables, adds `tmp_session` to producers/colours/types/equipment for temporary resources created inline in the form — cleaned up at offer create/edit `beforeCreate`), create_media_table; one hop: Filament/Actions/Journal/* (status setters), Actions/Stock/*, Actions/OfferCreatedByUserAction + OfferEditedByUserAction, Traits/HasSalesRecords, CreateOffer/EditOffer/ViewOffer pages (parts), AdminTab, StockSaleTab, UserReservationSection, AcceptOffersBulkAction, ChangePriceBulkAction, OfferEditForm (price/usage/quantity parts), FullListOffer (actions), UserReservedOffersPage (actions), FetchWooProducts, WooProductDTO, WoocommerceApiService (update/delete/sync), OtomotoService Listeners + UpdateAdvertAction, bootstrap/providers.php, routes/console.php, config/versionable.php, vendor sources for Versionable, SKU generator and InteractsWithMedia (library behaviour).
- Matched, not examined: app/Filament/Pages/{MyOffers,FullListOffer (tables/filters), AdminReservedOffersPage}, OfferResource tables, widgets (r02); OfferCreateForm, UserOfferCreateForm (r02); OfferSale/OfferReservation models and journal entry creation (r03); Submission pages (r04); WooCommerce/Otomoto actions beyond trigger points; Models/WooCommerceSync.php; SpatieMediaLibrary/CustomPathGenerator (file layout); HasChangesLog trait and Spatie activitylog config (Audit); migrations for shipping_method_id and eurocode_offer (Vehicles).
