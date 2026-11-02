# Otomoto — V1 Scan

> To be written by SCAN agent at the start of Phase 3b.
> Do not start until Offers module is ✅ and Offers domain events exist.
> V1 source: `/home/kp/laravel/projekty/AllGlass/`

**Scope to scan:**
- `app/Services/OtomotoService/` (entire directory)
- `app/Models/OtomotoOffer.php`, `app/Models/OtomotoData.php`
- `app/Models/OtomotoModels/{DonorCar,DonorGeneration,DonorMake,DonorModel}.php`
- `app/Filament/Resources/OtomotoOfferResource.php` and sub-components
- `app/Filament/Pages/UsersOtomotoOffers.php`
- `app/Console/Commands/FetchAllDonorGenerationsCommand.php`, `FetchDonorDataCommand.php`
- `database/migrations/` — otomoto_offers, donor tables
- Note: this is an Integration, not a Module — lives under `app/Integrations/Otomoto/`
