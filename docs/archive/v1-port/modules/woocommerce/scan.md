# WooCommerce — V1 Scan

> To be written by SCAN agent at the start of Phase 3a.
> Do not start until Offers module is ✅ and Offers domain events exist.
> V1 source: `/home/kp/laravel/projekty/AllGlass/`

**Scope to scan:**
- `app/Services/ApiService/` (entire directory — WoocommerceApiService, WordpressApiService, Actions, Jobs, DTOs)
- `app/Models/WooCommerceSync.php`, `app/Models/WordpressSync.php`
- `app/Http/Controllers/ApiController.php`
- `database/migrations/` — wordpress_syncs, woo_commerce_syncs tables
- Look for: any Offer model hooks that call WoocommerceApiService (anti-pattern to document)
- Note: this is an Integration, not a Module — lives under `app/Integrations/WooCommerce/`
