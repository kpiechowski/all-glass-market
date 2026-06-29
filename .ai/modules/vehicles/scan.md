# Vehicles — V1 Scan

> To be written by SCAN agent at the start of Phase 2a.
> Do not start this module until all Phase 1 modules are ✅ in refactor-plan.md.
> V1 source: `/home/kp/laravel/projekty/AllGlass/`

**Scope to scan:**
- `app/Models/Car.php`
- `app/Models/CarModels/CarBrand.php`
- `app/Models/Eurocode.php`
- `app/Models/EurocodeOffer.php` (pivot)
- `app/Filament/Resources/CarResource.php`
- `app/Console/Commands/GenerateEurocodesAndRelationFromOffers.php`
- `database/migrations/` — cars, car_brands, eurocodes tables
- Cross-module bleeds: Offer references to Car/Eurocode
