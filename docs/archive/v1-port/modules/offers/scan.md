# Offers — V1 Scan

> To be written by SCAN agent at the start of Phase 2b.
> Do not start until Vehicles module is ✅.
> V1 source: `/home/kp/laravel/projekty/AllGlass/`

**Scope to scan (large module — be thorough):**
- `app/Models/Offer.php` (658 lines — read in full)
- `app/Models/OfferModels/{OfferColor,OfferProducer,OfferType,OfferExtras}.php`
- `app/Models/OfferEquipment.php`, `app/Models/ShippingMethod.php`
- `app/Models/OfferReservation.php`, `app/Models/OfferSale.php`, `app/Models/OfferInquiry.php`
- `app/Filament/Clusters/Offers/` (entire cluster — resources, pages, actions)
- `app/Filament/Actions/Journal/` (all JournalAction subclasses — note which status they correspond to)
- `app/Services/Offer/` (OfferUpdateService, OfferPriceService, OfferEurocodeRelationService, events)
- `app/Filament/Enums/OfferStatus.php`, `OfferUsageStatus.php`, `ResourceStatus.php`
- `app/Policies/` — OfferPolicy, any offer-related policies
- `database/migrations/` — offers and all related tables
- Cross-module bleeds: any reference to Offer from Services, Jobs, Listeners outside this scope
