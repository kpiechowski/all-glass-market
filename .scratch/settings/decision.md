# Settings — outcome of discovery

- **Date:** 2026-11-08
- **Outcome:** no Settings module in V2 for now (D-01). The rule for where settings live is ADR 0007.
- **Evidence:** `discovery/discovery.md` (sections B–M) and `discovery/reports/r01–r05`.
- **Data migration:** none. The V1 `settings` table is not imported; credentials are entered once into the production `.env` (D-06).

## Map: V1 setting → V2 home

| V1 key / setting | V1 status | V2 home | Decision |
|---|---|---|---|
| `woocommerce_site_url` | live | `.env` → `config/services.php`, owned by the WooCommerce integration (WordPress uses the same URL today; whether it gets its own is decided in WooCommerce discovery) | D-04 |
| `woocommerce_client_key` | live, plaintext in DB | `.env` → `config/services.php` (WooCommerce) | D-04 |
| `woocommerce_secret_key` | live, plaintext in DB | `.env` → `config/services.php` (WooCommerce) | D-04 |
| `wordpress_username` | live | `.env` → `config/services.php` (WooCommerce/WordPress) | D-04 |
| `wordpress_password` | live, plaintext in DB | `.env` → `config/services.php` (WooCommerce/WordPress) | D-04 |
| `woocommerce_save_as_draft` | live | business option of the WooCommerce integration; config vs admin-editable decided in WooCommerce discovery | D-05 (pending) |
| `woo-offers-default-user` | live, falls back to user 1 | business option of the WooCommerce import; whether a configured owner is needed at all (vs the actor who runs the import) decided in WooCommerce discovery | D-05 (pending) |
| `site_name`, `site_description` | dead | dropped; the panel name comes from config and translations | D-03 |
| `journal_{created,accepted,rejected}_{title,desc}` (6 keys) | dead, never wired | dropped; offer notification texts are Offers translations | D-03 |
| Settings screen access (one hard-coded admin e-mail) | live | dropped with the screen | D-03 |
| `settings.user_id` | never used | dropped | D-03 |

## Settings found outside the V1 table

| V1 setting | V2 home | Decision |
|---|---|---|
| VAT multiplier 1.23 (`OfferPriceService`) and its company exemption | business constant of Offers, rules settled in Offers discovery | D-06 |
| `WP_DEFAULT_IMAGE_ID` (env) | `.env` → config of the WooCommerce integration | D-06 |
| `APP_ENV` switches (placeholder images, `vehicle`/`vehicles` post type) | config of the WooCommerce integration | D-06 |
| Otomoto: `OTOMOTO_*` env, `config/otomoto.php`, `otomoto_data` table | not mapped: Otomoto is out of V2; a new integration comes at the end | D-02 |

## Carried to later discoveries

- **WooCommerce:** D-05 (draft option, default owner, Q-03), Q-04 (connection test), whether WordPress gets its own URL.
- **Offers:** VAT and its exemption; hard-coded notification texts of offer created/accepted/rejected (JournalAction subclasses in V1).
