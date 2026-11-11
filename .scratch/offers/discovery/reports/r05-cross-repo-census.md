# r05 — cross-repo: where else Offer data and behaviour live, and the entry-point census

Question: Where else in V1 does offer data or offer behaviour live, outside the anchors — and what is the complete entry-point census of everything that reads or changes offers? · V1 commit: 511ce3b

## Summary
- Offer data is read or changed by about 150 V1 files outside the anchors. Most are: other-cluster Filament files (taxonomy resources, Cars, Users, dashboard widgets), the WooCommerce/WordPress sync (`app/Services/ApiService/**`), and Otomoto (`app/Services/OtomotoService/**`, `OtomotoOfferResource`). Otomoto rows are marked "dropped from V2".
- **Nothing in V1 expires or archives offers on a schedule.** `UpdateStatusOfExpiredOffers` ("offers:update-status-of-expired-offers", archives offers past `expires_at`, notifies admins) was added in f282532 (2024-11-22). Its schedule line was deleted in 968f8c4 (2025-07-10). The command file no longer exists, but `routes/console.php:7` still imports it. Production confirms `expires_at` is NULL on all 2046 offers. The only schedules are `UpdateFullDataOfActiveAdvertsCommand` (Otomoto, 06:00 and 16:00) and a per-minute `queue:work --queue=otomoto,default`.
- The offer status model is `OfferStatus` with 9 values: Szkic, Oczekuje zatwierdzenia, Aktywna, Odrzucona, Wygasła, Zarezerowana, Archiwalna, Sprzedana, Usunięta. Production uses 6: Aktywna 1409, Sprzedana 317, Usunięta 288, Archiwalna 23, Zarezerowana 5, Odrzucona 4. Szkic, Oczekuje and Wygasła have 0 rows today. (Wygasła has no code path that sets it; see Contradictions.)
- Side effects of an offer write are broad. Every `Offer` update fires `OfferUpdatedEvent` (Otomoto advert update, if Active and an advert exists) and `afterUpdateSyncAction` (WooCommerce product update). Delete sets status "Usunięta" and fires `OfferDeletedEvent`. Sold, Archived and Deleted events deactivate the Otomoto adverts. Touching the `eurocode_offer` pivot touches the Offer, so it also fires these. `OfferEventServiceProvider` is registered in `bootstrap/providers.php` (the registration line in `AppServiceProvider` is commented out).
- Offer rows are written outside the anchors in 5 places: the WooCommerce product import (`FetchWooProducts`), the profile `company_account` change, `BulkPriceUpdateSyncJob`, the legacy command `eurocodes:sync-models` and the admin-only Submission review "Dodaj eurokod". A Woo import creates `OfferSale` "Status sprzedaży z wordpress" or `OfferReservation` "Status rezerwacji z wordpress" rows.
- Census: 118 rows. Unreachable or dead: `FullListOfferTable` Livewire (no page references its wrapper view), `UserOfferCreateForm`, `OfferExtras`, `OfferResourcesSeeder`, `ListUsersNotifications` (partly; see the row), `UpdateStatusOfExpiredOffers` (deleted).
- Production aggregate counts are in "Production observations". They contradict some of r03 (legacy `car_offer` has 1531 rows).

## Findings

### No scheduled job expires or archives offers; the V1 expiry command is gone
- Tag: confirmed · Impact: high (state)
- What happens: no `Schedule::` line touches offers. `Offer.expires_at` is still a column. It is editable in the dead `UserOfferCreateForm` (`DatePicker expires_at`), shown read-only in `AdminTab` (TextEntry), and excluded when an offer is replicated (`Offer.php:619`). Nothing reads it to change a status. Git history: `UpdateStatusOfExpiredOffers` was created in f282532 and its `Schedule::command(...)->everyMinute()` line was commented out and then removed in 968f8c4. The file itself is not present in `HEAD`. Prod: 0 offers have `expires_at` set, so nothing is stuck. The `OfferStatus::Expired` (Wygasła) value has no producer in code (grep `OfferStatus::Expired` found nothing outside the enum label/colour; not exhaustively checked for the enum's own helpers).
- Evidence: `routes/console.php:7,12-30` (import of a nonexistent class; only the Otomoto command and queue:work scheduled); `git show f282532:app/Console/Commands/UpdateStatusOfExpiredOffers.php` (archived if `expires_at < now()` and status != Archiwalna, set `archived_date`, notified admins "Oferty [{$count}] zostały zarchiwizowane"); `git show 968f8c4 -- routes/console.php`; `app/Filament/Clusters/Offers/Resources/OfferResource/Components/AdminTab.php:73`; prod query `expires_at IS NOT NULL` = 0.

### Every offer update synchronises to WooCommerce and to Otomoto via model hooks
- Tag: confirmed · Impact: high (external systems)
- What happens: `Offer::booted` `updated` fires `OfferUpdatedEvent`. `UpdateAdvertAfterOfferChange` (queued) runs only if the offer has an `OtomotoOffer` and status is Active; then `UpdateCoreAdvertData` runs for each advert. `afterUpdateSyncAction` returns early for Pending and for statuses outside an allowed list, and otherwise calls `WoocommerceApiService::updateProduct` synchronously. It does nothing if the offer has no `wooSync`; a 400 "woocommerce_rest_product_invalid_id" deletes the local `wooSync` row. `deleted` (soft) sets status "Usunięta" with `saveQuietly`, fires `OfferDeletedEvent` and the Woo sync action. `restored` sets status Pending (and saves, which re-fires `updated`). `EurocodeOffer` pivot `$touches = ['offer']`, so changing eurocodes also fires all of this. `OfferSoldEvent`/`OfferArchivedEvent` are dispatched only from the Journal actions `OfferFullySoldAction` and `OfferArchivedAction`. All three delete/sold/archived events have the same listener `DeactivateAdvertListener` (queued): it deactivates every Otomoto advert of the offer.
- Evidence: `app/Models/Offer.php:156-187, 201-226`; `app/Models/EurocodeOffer.php:14-22`; `app/Services/Offer/OfferEventServiceProvider.php`; `bootstrap/providers.php:9`; `app/Services/OtomotoService/Listeners/{DeactivateAdvertListener,UpdateAdvertAfterOfferChange}.php`; `app/Services/ApiService/WoocommerceApiService.php:100-165`; `app/Filament/Actions/Journal/OfferFullySoldAction.php:40`, `OfferArchivedAction.php:33`.

### A company_account change in the profile re-syncs the user's Woo products
- Tag: confirmed · Impact: high (external systems)
- What happens: when a user changes the "company_account" flag on the profile page, the page sets a 30-minute cache lock `company_account_lock_{id}`, writes a `UserNotification` and notifies all admins (the message "Uzytkownik {name} zmienił rodzaj konta na: Firmowe|Prywatne"), then runs `OfferUpdateService::handleUsersPublishedOffer`. That dispatches `OfferUpdateOffersChunk` for the user's offers that have a `wooSync`, in chunks of 10. Each offer then gets a Woo `updateProduct` (price is recomputed by `OfferPriceService::getAdjustedOfferPrice`, which depends on the user's account type; not examined here). No Offer row is saved by this path, so no `updated` hook fires.
- Evidence: `app/Filament/Pages/ProfilePage.php:112-124`; `app/Services/Offer/OfferUpdateService.php:12-20`; `app/Jobs/Offer/OfferUpdateOffersChunk.php:28-31`; `app/Services/User/UserNotificationService.php:11-35`; `app/Services/ApiService/DTO/WooProductDTO.php:59`.

### Offers are written by the WooCommerce product import, with sale/reservation rows made from stock status
- Tag: confirmed · Impact: high (state)
- What happens: per Woo product the status is: `outofstock` or quantity 0 → Sprzedana (quantity forced to 1) and an `OfferSale` with label "Status sprzedaży z wordpress"; else ACF `reservation` → Zarezerowana and an `OfferReservation` (user = setting `woo-offers-default-user`, else 1) labelled "Status rezerwacji z wordpress"; else Aktywna. Price from Woo `price`, `usage_status` from ACF `status` (default OfferUsageStatus new). A `wooSync` row is created per imported offer. The Woo export also derives stock state from the offer. Imported offers keep SKUs from Woo.
- Evidence: `app/Services/ApiService/Jobs/FetchWooProducts.php:94-160, 177`; `app/Services/ApiService/DTO/WooProductDTO.php:54-80`.

### Admin can force-assign a Eurocode from the Submission review, which replaces the offer's eurocodes
- Tag: confirmed · Impact: high (state)
- What happens: described in vehicles r03 (not repeated). Offer-relevant point: it calls `$offer->eurocodes()->sync(...)`, so it touches the Offer (Woo/Otomoto sync fires) and changes `eurocode_offer`.
- Evidence: `app/Filament/Resources/SubmissionResource/Pages/ViewSubmission.php:143-215` (per vehicles r03).

### Bulk price change goes through a queue job that saves each offer
- Tag: confirmed · Impact: high (money)
- What happens: the bulk action "bulk_price_change" (in `ChangePriceBulkAction`, an anchor file) dispatches `BulkPriceUpdateSyncJob` with chunks. The job loads each offer by id, sets the price and saves, which fires the `updated` hooks above. (Details belong to r01/r02.)
- Evidence: `app/Filament/Clusters/Offers/Resources/OfferResource/Components/ChangePriceBulkAction.php:120`; `app/Jobs/Offer/BulkPriceUpdateSyncJob.php:29-42`.

### Price is computed by one singleton and consumed in 12 places outside the offer form
- Tag: confirmed · Impact: high (money)
- What happens: `OfferPriceService::getAdjustedOfferPrice(price, user)` is registered as singleton (`AppServiceProvider:25`). It is used for the displayed gross price (ViewOffer, tables, `FullListOffer`, `MyOffersTableSchema`, `ReservedOffersTableSchema`, `AdminReservedOffersTable`, `OfferEditForm`), the Woo regular price (`WooProductDTO:59`) and the Otomoto price (`UpdateAdvertAction`, `UpdateCoreAdvertData`, `BulkCreateAdvertsAction`, `OtomotoSynchronizationTab`). Rules in the service itself are r01/r02's job.
- Evidence: grep results listed in the ledger; `app/Providers/AppServiceProvider.php:25`.

### FK cascades and orphan rules on offer-linked tables
- Tag: confirmed · Impact: high (deletion)
- What happens: offers are soft-deleted (`2024_12_07_205358`), so cascades only run on force delete. DB cascade on offer delete: `submissions.offer_id`, `otomoto_offers.offer_id`, `offer_inquiries.offer_id`, `woo_commerce_syncs.offer_id` (changed to cascade by `2025_04_02_205040`), `eurocode_offer.offer_id`. No FK (orphans possible): `offer_reservations.offer_id`, `offer_sales.offer_id`, `user_journals.offer_id` (nullable), `car_offer`, `offer_offer_equipment`, and `offers.offer_producer_id/offer_color_id/offer_type_id` (`foreignIdFor` without `constrained`). `user_journals.user_id` cascades on user delete; `offers.user_id` has no FK. `Offer::forceDeleted` clears the media collection. Prod: 38 `user_journals` rows point at no existing offer (orphans exist; likely from force delete, not confirmed).
- Evidence: `database/migrations/2024_11_10_145443_create_submissions_table.php:19`; `2025_04_05_180614_create_otomoto_offers_table.php:17`; `2025_11_15_153812_create_offer_inquiries_table.php:17-19`; `2025_04_02_205040_update_woo_commerce_syncs_foreign_key.php:15-21`; `2025_08_30_131935_create_eurocodes_table.php:24-25`; `2025_01_26_5555235_create_offer_reservations_table.php:18`; `2025_01_26_5555235_create_offer_sales_table.php:17`; `2024_12_01_180306_create_user_journals_table.php:17-18`; `2024_10_06_151733_create_offers_table.php:46-50`; prod orphan-journal COUNT.

### Per-user offer statistics and offer lists appear on the dashboard and user pages
- Tag: confirmed · Impact: normal
- What happens: Dashboard widget `OfferStatsOverview` shows "Moje oferty:" (count plus "W tym miesiącu: N"), "Aktywne oferty" and "Oczekujące..." counts and a rejected-offers stat for the current user. Widget `LatestOffers` ("Ostatnie oferty") lists the user's offers with a "Zobacz" link and header buttons ("Zobacz pełny widok"/"Moje oferty", "Dodaj ofertę"). The admin User view shows total offers, active offers and the sum of offer prices (all statuses, unit price, not quantity). `User::getTotalSalesAmount` and `getTotalQuantitySold` sum `OfferSale.quantity * offer.price` (current offer price, not the price at sale time; usage not traced). Admin relation managers list offers on a User and on a Car with status/usage filters.
- Evidence: `app/Filament/Resources/OfferResource/Widgets/OfferStatsOverview.php`; `.../LatestOffers.php`; `app/Providers/Filament/AdminPanelProvider.php:93-98`; `app/Filament/Resources/UserResource/Pages/ViewUser.php:118-133`; `app/Models/User.php:110-130`; `app/Filament/Resources/Shared/OffersRelationManager.php:35-150`; `app/Filament/Resources/CarResource/RelationManagers/OffersRelationManager.php`.

### Taxonomy rows (type, producer, colour, equipment) show offer counts and are deletable
- Tag: confirmed · Impact: normal
- What happens: taxonomy lists show `offers_count`; taxonomy view shows the offers. `DeleteAction`/`DeleteBulkAction` are offered in the shared trait; with no FK on the offer side, deleting a used taxonomy leaves offers pointing at a missing row (inference from the migrations; policies for deletion were not examined).
- Evidence: `app/Filament/Traits/IsOfferTaxonomy.php:57-59, 103-116`; `IsOfferTaxonomyView.php:163`; migration lines in the FK finding.

### Offer-side effects of ShippingMethod
- Tag: confirmed · Impact: normal
- What happens: `ShippingMethod hasMany Offer`; `offers.shipping_method_id` is nullOnDelete (r03). Admin-only form field; printed in Otomoto descriptions.
- Evidence: `app/Models/ShippingMethod.php:11`; `database/migrations/2025_08_17_55555_create_shipping_methods_table.php`.

### Notification entities: panel notifications, database notifications, and "UserNotification"
- Tag: confirmed · Impact: normal
- What happens: there are 3 channels. (a) `PanelNotification`/`OfferNotificationHandler` (anchor), (b) Filament database notifications (`->databaseNotifications()`, `notifications` table; 7153 rows in prod), sent to all admins by `User::getAdmins()`, and (c) `UserNotification` rows ("user_notifications", 8 rows in prod) created by `UserNotificationService::createUserNotification` and shown by Livewire `users.list-users-notifications` (view `filament/users/list-users.blade.php`). The only caller of the service found is the profile page company-account change. No page or route references `filament/users/list-users.blade.php` (grep), so the `UserNotification` list is likely unreachable; runtime check would settle it.
- Evidence: `app/Services/User/UserNotificationService.php`; `app/Filament/Pages/ProfilePage.php:122`; `app/Livewire/Users/ListUsersNotifications.php`; `resources/views/filament/users/list-users.blade.php:4`; `app/Providers/Filament/AdminPanelProvider.php` (`->databaseNotifications()`); prod counts.

### Version history ("Wersje"/revisions) of an Offer is a package feature
- Tag: confirmed · Impact: normal
- What happens: `Offer` uses the versionable package with `VersionStrategy::SNAPSHOT`. `OfferRevisionsPage` (Mansoor `RevisionsPage`) and `RevisionsAction` on `EditOffer` expose it. `versions` table has 2923 rows. A second audit trail is the `ChangesLog` module (`HasChangesLog`, table `changes_logs`, 5996 rows, relation manager `ChangesRelationManager` hidden on EditOffer, admin-only). Revert/restore behaviour is the package's (vendor; not examined).
- Evidence: `app/Models/Offer.php:146 ($versionStrategy)`; `app/Filament/Clusters/Offers/Pages/OfferRevisionsPage.php:9-11`; `EditOffer.php:34`; `app/Filament/Resources/Shared/ChangesRelationManager.php:25,39`; `app/Modules/ChangesLog/**`.

## Production observations (read-only aggregates, allglass_v1_prod)
- Offers 2046, of which 288 soft-deleted. By status: Aktywna 1409, Sprzedana 317, Usunięta 288, Archiwalna 23, Zarezerowana 5, Odrzucona 4.
- Offers with `expires_at`: 0. Offers without any `eurocode_offer` row: 37. `car_offer` rows (legacy pivot, r03 had no data): 1531. Sub-tables: `offer_reservations` 14, `offer_sales` 318, `offer_inquiries` 20, `submissions` 1213, `user_journals` 3571 (38 orphaned from offers), `user_notifications` 8, `otomoto_offers` 1800, `woo_commerce_syncs` 2155, `changes_logs` 5996, `versions` 2923.
- `failed_jobs` 473 in total: `FullDataAdvertUpdateJob` 332 (2025-07 to 2026-10-13), `BulkSynchProductToWooAction` 69 (2025-07 to 2025-12), `PublishAdvertsJob` 50, `GenerateResponsiveImagesJob` 18, `UpdateAdvertAfterOfferChange` 1, `GenerateAdvertsDescriptionJob` 1, `Notification` 2. No `OfferUpdateOffersChunk`, `BulkPriceUpdateSyncJob` or `SynchOemCodeFromWordpressJob` failures. `job_batches`: 3 rows. (The `BulkSynchProductToWooAction` class is used as a queued job although its dispatch in `AcceptOffersBulkAction` is commented out; the failures show it was used in the past.)
- The "Otomoto" failures are recent (2026-10), so the Otomoto jobs are in active use today.

## Entry points
Kinds: Page = Filament page, Resource = Filament resource page, Action = table/header/bulk action, Job, Listener, Hook = model hook, Cmd = artisan, Sched = schedule, Widget, Livewire. Reachable = reachable from the UI/scheduler/queue today. "A" = anchor file; Otomoto rows = dropped from V2.

| # | Kind | Path | Does | Reachable |
|---|---|---|---|---|
| 1 | Resource | `Filament/Clusters/Offers/Resources/OfferResource.php` + `Pages/ListOffers` (A) | Offers list (index), create button | yes (view guarded by `OfferPolicy::viewAny`, admins only on `admin/glass/offers`) |
| 2 | Resource | `.../OfferResource/Pages/CreateOffer.php` (A) | Create offer; promotes/cleans tmp taxonomy rows | yes |
| 3 | Resource | `.../Pages/EditOffer.php` (A) | Edit offer, delete, revisions, eurocode copy | yes (owner or admin) |
| 4 | Resource | `.../Pages/ViewOffer.php` (A) | View; main photo, edit-offer-user, restore, force delete, eurocode reorder | yes |
| 5 | Page | `Filament/Pages/FullListOffer.php` (A) "Wszystkie oferty" | Admin table of all offers; tabs; bulk actions | yes, admin only |
| 6 | Action | `FullListOffer` bulk delete (soft) | Soft-delete offers (fires Deleted event + Woo update) | yes admin |
| 7 | Action | `FullListOffer` bulk force delete | Hard delete offers; cascades to submissions, inquiries, adverts, woo syncs | yes admin |
| 8 | Action | `FullListOffer` bulk: closure at line 163 | Delete the products from WooCommerce (`deleteProductFromWoo`) | yes admin |
| 9 | Action | `Components/ChangePriceBulkAction.php` "bulk_price_change" (A) | Bulk price change; dispatches `BulkPriceUpdateSyncJob` | yes |
| 10 | Action | `Components/AcceptOffersBulkAction.php` "accept-offers" (A) | Accepts pending offers via `OfferStatusAggregate::canOfferBeAccepted` | yes admin |
| 11 | Action | `Components/WooSyncBulkAction.php` "bulk-woo-sync" (A) | Pushes offers to WooCommerce | yes |
| 12 | Action | `OtomotoOfferResource/Components/BulkAdvertCreateAction.php` | Create Otomoto adverts for offers (also in FullListOffer) | yes — dropped from V2 |
| 13 | Action | `OtomotoOfferResource/Components/BulkRemoveFromWpAction.php` "bulk-wp-remove" "Wycofaj z wordpressa" | Remove offers from WordPress/Woo | yes |
| 14 | Page | `Filament/Pages/MyOffers.php` "Moje oferty" (A) | User's offers; create, "Aktywuj" (draft to pending), delete bulk, "Podgląd" | yes (non-admin view; admins see variants) |
| 15 | Page | `Filament/Pages/UserReservedOffersPage.php` "Zarezerwowane" (A) | User's reservations; non-admin only | yes |
| 16 | Page | `Filament/Pages/AdminReservedOffersPage.php` "Zarezerwowane" (A) | Admin reserved offers; admin only | yes |
| 17 | Page | `Filament/Pages/UserInquiriesPage.php` "Zapytania ofertowe" (A) | Non-admin inquiry list; accept/reject | yes |
| 18 | Resource | `Resources/OfferInquiryResource.php` + `Pages/ListOfferInquiries` (A) | Inquiries index only (create/edit routes commented out) | yes |
| 19 | Action | `OfferInquiryResource/Actions/{Create,Accept,Reject}InquiryAction.php` (A) | Create, accept, reject an inquiry | yes |
| 20 | Action | `Tables/UserInquiriesTable` "accept-inquiry", "reject-inquiry", "podgląd", bulk delete (A) | User inquiry table actions | yes |
| 21 | Action | `Tables/AdminInquiriesTable` "podgląd", force delete, bulk delete/force (A) | Admin inquiry table | yes |
| 22 | Resource | `Resources/SubmissionResource.php` (A) | Submissions list/view only; "set_as_read", bulk "read_selected", bulk delete | yes (`SubmissionPolicy` permissions) |
| 23 | Action | `SubmissionResource/Pages/ViewSubmission.php` (A) | Review: "Dodaj eurokod" (replaces eurocodes), back, delete | yes |
| 24 | Resource | `Resources/UserJournalResource.php` (A) | Journal list/view; "set_as_read", "podgląd", "delete", bulk delete | yes (`UserJournalPolicy` is commented out) |
| 25 | Hook | `Models/Offer.php::booted` updated/deleted/forceDeleted/restored (A) | Events, Woo update, status Usunięta/Oczekuje, media cleanup | yes (any save) |
| 26 | Listener | `OtomotoService/Listeners/UpdateAdvertAfterOfferChange` | Update advert after offer update (Active + advert) | yes — dropped from V2 |
| 27 | Listener | `OtomotoService/Listeners/DeactivateAdvertListener` | Deactivate adverts on Sold, Archived, Deleted | yes — dropped from V2 |
| 28 | Hook | `Models/EurocodeOffer.php` `$touches=['offer']` | Pivot changes touch the Offer | yes |
| 29 | Action | `Actions/Journal/OfferFullySoldAction` etc. (A) (9 Journal actions) | Status transitions plus journal; dispatch Sold/Archived events | yes |
| 30 | Action | `Actions/Stock/OfferStockChangeAction`, `OfferReservationChangeAction` (A) | Stock and reservation changes | yes |
| 31 | Action | `Filament/Actions/OfferCreatedByUserAction`, `OfferEditedByUserAction` (A) | Post-save: submission, notification, eurocode match | yes |
| 32 | Page | `Components/StockSaleTab`, `UserReservationSection`, `AdminTab` (A) | In-offer sale/reservation/admin sections ("InfoAction" buttons) | yes |
| 33 | Job | `Jobs/Offer/BulkPriceUpdateSyncJob` (A) | Sets price per offer and saves | yes (queue) |
| 34 | Job | `Jobs/Offer/OfferUpdateOffersChunk` (A) | Woo `updateProduct` per offer | yes (via row 35) |
| 35 | Page | `Filament/Pages/ProfilePage.php` | Company-account change: user notification, admin notification, re-sync Woo products of the user | yes |
| 36 | Job | `Jobs/Offer/SynchOemCodeFromWordpressJob` (A) | Fills `offers.oem` from Woo ACF | yes (via row 37) |
| 37 | Cmd | `Console/Commands/GetOemsFromWordpressOffers` "wordpress:sync-oems" | Selects offers with null oem and dispatches row 36 on queue `oem_codes` | manual only |
| 38 | Cmd | `Console/Commands/GenerateEurocodesAndRelationFromOffers` ("eurocodes:sync-models", deprecated) | Rebuild eurocodes and offer links from offers | manual only; deprecated |
| 39 | Cmd | `Console/Commands/MigrateOemCodesToJsonCommand` | Eurocode data only (r03); no offer rows | manual only |
| 40 | Sched | `routes/console.php:16` `UpdateFullDataOfActiveAdvertsCommand` | Twice daily 06:00/16:00 updates the Otomoto adverts of active offers | yes — dropped from V2 |
| 41 | Sched | `routes/console.php:22` `queue:work --queue=otomoto,default --stop-when-empty` every minute | Drives all queued offer jobs and listeners | yes |
| 42 | Cmd (dead) | `routes/console.php:7` import of `UpdateStatusOfExpiredOffers` | Command deleted; no schedule | unreachable |
| 43 | Page | `Filament/Clusters/Settings/Pages/ApiSettings.php` "Pobierz Oferty z Woocommerce" | Starts `FetchWordpressDataOrchestrator`, imports offers | yes admin |
| 44 | Job | `ApiService/Jobs/FetchWooProducts` | Creates offers, sales, reservations, `wooSync`, taxonomy links | yes (via row 43) |
| 45 | Job | `ApiService/Jobs/FetchWordpress{Cars,Colors,Producers,Equipment}`, `FetchWooOfferTypes`, `FetchWordpressDataOrchestrator` | Create taxonomy rows used by offers (r03) | yes |
| 46 | Action | `ApiService/Actions/SyncProductToWordpressAction`, `UpdateProductToWordpressAction`, `BulkSynchProductToWooAction`, `FetchProductsFromWooAction`, `SyncProductCategoryAction`, `UpdateProductCategoryAction` | Create/update products and categories in Woo from offers | yes (WooCommerce internals out of scope) |
| 47 | Service | `ApiService/WoocommerceApiService` `syncProduct/updateProduct/deleteProductFromWoo` | Woo calls; error 400 invalid id removes local `wooSync` | yes |
| 48 | Resource | `Resources/OtomotoOfferResource.php` + Pages (List, Create, Edit, View, `OtomotoOptions`, `OtomotoQueueMonitor`) | Otomoto advert UI per offer | yes — dropped from V2 |
| 49 | Action | `OtomotoOfferResource/Components/{BulkActivate,BulkPublish,BulkUpdate,BulkRegenerateDescriptions,BulkAdvertCreate}Action`, `StatusActionResolver` ("Opublikuj ogłoszenie", "Aktualizuj Otomoto", "Aktywuj ofertę", "Deaktywuj ofertę") | Advert lifecycle | yes — dropped from V2 |
| 50 | Job | `OtomotoService/Jobs/{ActivateAdverts,FullDataAdvertUpdate,GenerateAdvertsDescription,PublishAdverts,RegenerateAdvertsDescription,UpdateAdvertsData}Job` | Advert jobs (332 `FullDataAdvertUpdateJob` failures in prod) | yes — dropped from V2 |
| 51 | Page | `Filament/Pages/UsersOtomotoOffers.php` | User's own adverts (`otomoto-user-offers` view) | yes — dropped from V2 |
| 52 | Widget | `Resources/OfferResource/Widgets/OfferStatsOverview` | Dashboard counts of the user's offers | yes (registered in `AdminPanelProvider:96`) |
| 53 | Widget | `Resources/OfferResource/Widgets/LatestOffers` "Ostatnie oferty" | Dashboard list of user's offers | yes |
| 54 | Resource | `Resources/UserResource.php`, `Pages/ViewUser.php` | Offer counts and value per user; relation manager | yes admin |
| 55 | RelMgr | `Resources/Shared/OffersRelationManager`, `UserResource/RelationManagers/OffersRelationManager` | Offers of a User, status/usage filters | yes |
| 56 | RelMgr | `Resources/CarResource/RelationManagers/OffersRelationManager`, `CarResource.php`, `ListCars`, `ViewCar` | Offers of a Car (legacy `car_offer` via `Car::offers()`) | yes (shows legacy pivot; prod 1531 rows) |
| 57 | Resource | `Clusters/Offers/Resources/OfferTaxonomies/{OfferType,OfferColor,OfferProducer,OfferEquipment}Resource` + pages (16 pages) | Taxonomies; offer counts; delete | yes |
| 58 | Resource | `Clusters/Offers/Offers/Resources/EurocodeResource` + 3 pages; `ShippingMethodResource` + 3 pages | Reference data; offer link | yes |
| 59 | Page | `Clusters/Offers/Pages/OfferRevisionsPage` | Version history of offers | yes |
| 60 | RelMgr | `Resources/Shared/ChangesRelationManager` | Offer change log (admin; hidden on EditOffer) | yes |
| 61 | Livewire | `Livewire/Users/ListUsersNotifications` | UserNotification list; delete | likely unreachable (no page refers to its wrapper view) |
| 62 | Livewire | `Livewire/FullListOfferTable` (+ view `full-list-offer-page`) | Duplicate of `FullListOffer` with "toggle_layout" | unreachable: no page references the view `full-list-offer-page.blade.php` (grep) |
| 63 | Form (dead) | `OfferResource/Components/UserOfferCreateForm.php` | Old user create form with `expires_at` | unreachable (not referenced; r03) |
| 64 | Model (dead) | `Models/OfferModels/OfferExtras.php`, `database/factories/OfferExtrasFactory.php`, migration `2024_10_10_192433_create_offer_extras_table` | Legacy table | unreachable |
| 65 | Seeder | `database/seeders/OfferResourcesSeeder.php` | Seeds taxonomies; call commented out in `DatabaseSeeder` | unreachable |
| 66 | Policy | `Policies/OfferPolicy.php` (+ `OfferColor/Producer/Type/Equipment` policies, and duplicates under `Policies/OfferPolicies/`) | `viewAny` (admin only on `admin/glass/offers`), `view/update` (admin or owner), `create` (`create_offer` permission) | yes |
| 67 | Policy | `Policies/SubmissionPolicy`, `UserJournalPolicy` | Submission uses permissions; journal policy is commented out | yes |
| 68 | Provider | `Services/Offer/OfferEventServiceProvider` (bootstrap/providers.php) | Maps Deleted/Sold/Archived to Deactivate, Updated to Update advert | yes |
| 69 | Provider | `Providers/AppServiceProvider.php:25` | Registers `OfferPriceService` singleton; registers `ChangesLogServiceProvider` (module) | yes |
| 70 | Provider | `Providers/Filament/PanelNotificationProvider` | Registers `OfferNotificationHandler` singleton | yes |
| 71 | Table-view | `OfferResource/Components/Tables/{MyOffersTableSchema,ReservedOffersTableSchema,AdminReservedOffersTable}`, `Filters/ReservedOfferFilters`, `Partials/*` (A) | Table schemas for rows 5, 14, 15, 16 | yes |
| 72 | Routes | `routes/web.php`, `routes/api.php` | `/` redirects to `admin`; `/login` redirects to `/admin/login`; `/api/user` returns the sanctum user. No offer API route | yes |
| 73 | Test | `tests/**` | 14 Fortify/Jetstream tests and 1 example; no offer test | n/a |
| 74 | Factory | `database/factories/OfferFactory.php`, `ProducerFactory`, `OfferExtrasFactory` | Not used by any test or seeder (grep `factory()` found only `User::factory()` in tests) | unreachable |

(Rows 29-32 and 71 cover multiple files; the individual Journal action classes are listed in the ledger.)

## Contradictions and defects
- `bootstrap/providers.php:6` lists `App\Providers\ChangesLogServiceProvider`, but no such class exists (`app/Modules/ChangesLog/ChangesLogServiceProvider` is the real one and is registered from `AppServiceProvider:26`). Tag: defect? — `git ls-files` shows `bootstrap/providers.php` tracked. Whether Laravel boots with it is not established; production clearly runs, so the file in production may differ. Runtime check would settle it.
- `routes/console.php:7` imports a command class that does not exist (deleted). Harmless at runtime (an import is not resolved), but it shows the expiry feature was removed without removing `expires_at` or `OfferStatus::Expired`. Tag: defect?
- `OfferStatus::Expired` (Wygasła) exists in the enum but nothing assigns it; it is only a possible filter/label value. Tag: unknown on exact usage (not exhaustively grepped in all enum helpers).
- `OfferEventServiceProvider` is registered in `bootstrap/providers.php`; the commented `register` line in `AppServiceProvider` is therefore not a gap (no double registration).
- `Offer::restored` sets status Pending and saves, so a restored offer re-enters the approval flow and fires update hooks (Woo skip for Pending). Tag: confirmed.
- `OfferPolicy::viewAny` reads `request()->route()->uri`, so it fails outside HTTP (queue, console). Tag: defect?  (`app/Policies/OfferPolicy.php:19-24`).
- Duplicate policy files exist: `Policies/OfferColorPolicy.php` and `Policies/OfferPolicies/OfferColorPolicy.php` (same for Producer and Type). Which is registered was not traced (auto-discovery by namespace suggests the top-level ones). Tag: ambiguous.

## Leads outside scope
- `OfferPriceService::getAdjustedOfferPrice` depends on the user's account type: it is needed to understand the company/private price rule (r01/r02 territory).
- `DB::table()` on offer tables appears in `Models/Car.php:37-54` only (vehicles r03 covered it).
- `Setting` keys used with offers: `woo-offers-default-user`, `woocommerce_save_as_draft`, `journal_created`, `journal_accepted`, `journal_rejected` (MessagesSettings). Settings module discovery covers these.
- `BulkActionTracker` / `OtomotoBulkAction` store `offer_ids` JSON (Otomoto, dropped from V2).
- 473 failed jobs in prod, mostly Otomoto and Woo bulk sync; V2 should decide what to do about failed sync handling.
- The 38 orphaned `user_journals` rows may indicate force deletes; the Journal module might need the "offer deleted" rule.

## Could not establish
- Whether `bootstrap/providers.php` with the nonexistent `ChangesLogServiceProvider` boots (needs running the app; not allowed).
- Whether anything sets `OfferStatus::Expired`: grep for the enum case outside the enum found none in the files I examined; an exhaustive check of dynamic assignment (e.g. from Woo imports with `OfferStatus::from`) was not done.
- Whether `ListUsersNotifications` is reachable through a route or page I missed. I searched `@livewire`, `<livewire`, the class name and the view name; found only the wrapper view `filament/users/list-users.blade.php`. A runtime check would settle it.
- Which of the duplicate policy classes Laravel uses.
- The full set of statuses allowed by `Offer::afterUpdateSyncAction` (only the head of the method was read).
- `OfferPriceService` rules, `OfferStatusAggregate` rules and the Journal actions: out of scope (r01–r04).

## Coverage ledger
- Searched (grep over `app routes config database resources lang tests bootstrap composer.json`; case-insensitive unless noted): `offer` (and `Oferty`), `App\Models\Offer`, `offers` (table), `offer_id`, `OfferStatus` and each Polish value (via the enum and `OfferStatus::`), `OfferUsageStatus`, `usage_status`, `quantity`, `price`, `OfferPriceService`, `OfferUpdateService`, `OfferStatusAggregate`, `OfferUpdatedEvent`, `OfferSoldEvent`, `OfferArchivedEvent`, `OfferDeletedEvent`, `OfferUpdateOffersChunk`, `BulkPriceUpdateSyncJob`, `SynchOemCodeFromWordpressJob`, `OfferReservation`/`offer_reservations`, `OfferSale`/`offer_sales`, `OfferInquiry`/`offer_inquiries`, `Submission`/`submissions`, `UserJournal`/`user_journals`, `JournalType`, `UserNotification`/`notifications`, `tmp_session`, `sku`, `Versionable`/`versions`/`RevisionsPage`, `HasMedia`/`offer-gallery`, `Zgłoszeni`, `Rezerwacj`, `Dziennik`, `Zapytania`, `expires_at`/`expired`, `Schedule::`, `DB::table`, `wooSync`/`wordpress_id`, `HasChangesLog`/`LoggableModel`, `ChangesLogServiceProvider`, `HasSalesRecords`, `@livewire`/`<livewire`, `factory()`, `cascade`/`constrained`/`softDeletes` in migrations, `Action::make(`/`BulkAction::make(` in anchors.
- Also: `git log -S UpdateStatusOfExpiredOffers`, `git show f282532`, `git show 968f8c4 -- routes/console.php`; production aggregate COUNT/GROUP BY on `offers`, `failed_jobs`, `jobs`, `job_batches`, and the offer-linked tables.
- Examined: `routes/console.php`, `routes/web.php`, `routes/api.php`, `bootstrap/providers.php`, `app/Providers/{AppServiceProvider,Filament/AdminPanelProvider,Filament/PanelNotificationProvider}.php`, `app/Services/Offer/{OfferEventServiceProvider,OfferUpdateService}.php`, `app/Jobs/Offer/OfferUpdateOffersChunk.php`, `app/Models/{User,Offer (lines 140-230),ShippingMethod,OtomotoOffer,EurocodeOffer,WooCommerceSync (grep)}.php`, `app/Services/User/UserNotificationService.php`, `app/Filament/Pages/ProfilePage.php (100-140)`, `app/Filament/Resources/OfferResource/Widgets/*`, `app/Console/Commands/GetOemsFromWordpressOffers.php`, `app/Services/OtomotoService/Listeners/*`, `app/Services/ApiService/WoocommerceApiService.php (100-186)`, `app/Services/ApiService/Jobs/FetchWooProducts.php (94-160)`, `app/Policies/OfferPolicy.php (head)`, `SubmissionPolicy` and `UserJournalPolicy` (grep), taxonomy traits (grep), relation managers (grep), migrations (grep of FK lines), `resources/views` (grep), `lang/` (directory listing), tests and factories (grep). The vehicles census `.scratch/catalogue/discovery/reports/r03-cross-repo-census.md` was reused.
- Matched, not examined (read only through grep lines, the anchor-excluded list): the 16 taxonomy resource pages, `EurocodeResource` and `ShippingMethodResource` pages, `OtomotoOfferResource` and its components/pages, `OtomotoService/**` (Actions, Jobs, DTO, Services; Otomoto internals out of scope), `ApiService/Actions/*` and `DTO/*` except the lines cited, `Fetch*` jobs other than `FetchWooProducts`, `UserResource` pages, `CarResource`, `Policies/*` other than `OfferPolicy`, `Resources/Shield`, `Settings` pages, seeders (`ShieldSeeder*`), `config/otomoto.php`, all other migrations listed in the grep (taxonomy tables, `otomoto_*`, `shipping`), `app/Livewire/CustomProfileComponent`, `AlpineSidebarHideEvent`, `PanelConfig`. They contain offer references but belong to other modules or were censused at the row level only.
