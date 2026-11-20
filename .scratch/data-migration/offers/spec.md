# Offers — V1 data migration spec

<!-- Written in Phase 9. V1 side: tables and columns with evidence. V2 side: the information named in
     the functional spec's terms, never V2 column names; whoever builds the import maps those. -->

- **Functional spec:** `.scratch/offers/functional-spec.md` §8 · **Discovery:** `.scratch/offers/discovery/discovery.md` (decisions D-59–D-68)
- **Ticket:** `ticket.md` beside this file
- **Facts:** production dump `allglass_v1_prod` (last offer 2026-10-01), aggregate queries of 2026-11-20. Re-run the counts before the import.

## 1. Sources

| V1 table | Defined in | Rows | Owner of the fate |
|---|---|---|---|
| `offers` | `2024_10_06_151733_create_offers_table.php`, `2024_12_07_205358_add_soft_delete_for_offers.php`, later column additions | 2046 | this spec |
| `media` (rows with an Offer `model_type`, collections `offer-gallery`, `offer-private-gallery`) | `2024_10_06_194939_create_media_table.php` | 8390 + 297 | this spec |
| `offer_reservations` | `2025_01_26_5555235_create_offer_reservations_table.php` | 14 | this spec |
| `offer_sales` | `2025_01_26_5555235_create_offer_sales_table.php` | 318 | this spec |
| `offer_inquiries` | `2025_11_15_153812_create_offer_inquiries_table.php` | 20 | this spec (dropped, D-63) |
| `submissions` | `2024_11_10_145443_create_submissions_table.php`, `2025_09_06_55555_add_read_status_to_submission.php` | 1213 | this spec (dropped, D-20) |
| `eurocode_offer`, `car_offer`, `offer_offer_equipment`, `offer_colors`, `offer_types`, `offer_producers` | Catalogue sources | — | Catalogue migration (`.scratch/data-migration/catalogue/spec.md`); this import reads its hand-over |
| `user_journals`, `notifications`, `user_notifications` | Users | — | Users migration ticket |
| `versions`, `changes_logs`, `activity_log` | Audit | — | Audit migration spec (all dropped) |
| `woo_commerce_syncs`, `wordpress_syncs`, `otomoto_*` | integrations | — | WooCommerce discovery; Otomoto dropped |
| `shipping_methods` | shipping | — | pending (D-55) |

## 2. Mapping

### `offers`

| V1 column | Meaning in V1 | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `id` | offer id | transform | (import key only) | kept in the import's V1 → V2 map for idempotency and for photos, sales, reservations |
| `eurocode` | seller's typed code | transform | identification hint | only when the Offer gets no Glass model; joined into the hint text (D-10, D-60) |
| `sku` | offer number "AG…" or WooCommerce SKU | keep | offer number | unchanged, all 2046 distinct (D-29, BR-24) |
| `price` | net price | keep | entered price | as stored; all ≥ 1 (D-33) |
| `user_description` | "Informacje o pojeździe" (without-eurocode text) | transform | identification hint | only without a Glass model, appended to the hint (D-60); 5 filled |
| `description` | "Krótki opis szyby" | keep | public description | as stored (D-49); 1146 filled |
| `alt_eurocodes` | alternative eurocodes text | drop | — | Catalogue joins eurocodes (D-03, D-13) |
| `oem` | offer-level OEM text | transform | identification hint | Catalogue takes it into OEM codes; for an Offer without a Glass model also appended to the hint (D-60) |
| `vim` | VIN | transform | identification hint | only without a Glass model, appended to the hint as "VIN: …" (D-16, D-60) |
| `quantity` | pieces | transform | one Offer per piece | quantity > 1 → split after the client review (D-02, D-68, §4) |
| `usage_status` | Nowa / Używana / Outlet | keep | condition | same three values |
| `rating` | 1–5 | transform | rating | dropped for Nowa (D-46); kept for Używana and Outlet |
| `year` | production year text | transform | year | 4-digit values kept (586); malformed values dropped (D-64) |
| `image` | id of the main photo in `media` | transform | order of public photos | that photo first (529 of 532 match a gallery photo; a non-matching value is ignored) |
| `gallery` | unused | drop | — | always empty (D-64) |
| `offer_producer_id` | producer | transform | producer | through Catalogue's producer map |
| `offer_color_id`, `offer_type_id` | colour, glass type | drop | — | Glass model parameters (Catalogue, D-07) |
| `user_id` | owner | keep | owner | through the Users map; never empty in prod (D-40) |
| `without_eurocode` | flag "no eurocode" | drop | — | implied by "no Glass model" (D-10) |
| `status` | V1 status | transform | status, Trash | Aktywna → Active (Pending when no Glass model, D-60); Zarezerowana → Reserved; Sprzedana → Sold; Archiwalna → Withdrawn; Odrzucona → Rejected; Usunięta → Withdrawn in the Trash (D-59); Szkic → Draft, Oczekuje zatwierdzenia → Pending, Wygasła → Withdrawn (no rows today) |
| `expires_at` | expiry | drop | — | never written (D-36) |
| `archived_date` | withdrawal date | drop | — | never written (D-64) |
| `sold_date` | full-sale date | drop | — | the Sale's date is the source (D-64) |
| `created_at` | creation | keep | created | as stored (chart edge case, D-58) |
| `updated_at` | last change | transform | last changed; first and last approved | first/last approved = `updated_at` for Active, Reserved, Sold, Withdrawn (not in Trash and in Trash alike); empty for Rejected, Pending, Draft (D-64) |
| `notes` | "Notatki własne" | keep | private notes | as stored (D-49); 29 filled |
| `attachments` | unused | drop | — | always empty |
| `deleted_at` | soft delete | transform | in Trash, since | set → in the Trash since that date (D-43, D-59) |
| `shipping_method_id` | shipping | drop | — | 2 rows; pending (D-55) |

### `media` (Offer rows)

| V1 column | Meaning | Fate | V2 information | Rule |
|---|---|---|---|---|
| `model_id` | offer | transform | Offer | through the offer map; split pieces get copies (§4) |
| `collection_name` | `offer-gallery` / `offer-private-gallery` | transform | public / private photo | public beyond 10 → private (D-64; 2 offers, up to 14) |
| `order_column` | order | transform | photo order | main photo (`offers.image`) first, then by order |
| `file_name`, `disk`, `mime_type`, `size`, `name` | the file | keep | the photo file | the file is copied |
| `uuid`, `conversions_disk`, `manipulations`, `custom_properties`, `generated_conversions`, `responsive_images` | library internals | drop | — | V2 regenerates its own |
| `wordpress_id` | WooCommerce media id | drop | — | integration's mapping (D-26) |
| `id`, `model_type`, `created_at`, `updated_at` | technical | drop | — | — |

### `offer_reservations`

| V1 column | Meaning | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `offer_id` | offer | transform | Reservation's Offer | orphan (offer gone) → row dropped (4 rows, D-62) |
| `user_id` | author | keep | author | through the Users map |
| `label` | free note (may hold buyer contact) | keep | "for whom" note | as typed (D-62) |
| `quantity` | pieces | drop | — | one piece (D-02) |
| `reserved_at` | when | keep | when made | — |
| `sold_by` | unused | drop | — | always empty |
| (derived) | — | transform | how it ended | offer Zarezerowana → current; offer Sprzedana → ended as sold at the Sale's date; offer Usunięta → ended as released at the deletion date (D-62) |
| `id`, `created_at`, `updated_at` | technical | drop | — | — |

### `offer_sales`

| V1 column | Meaning | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `offer_id` | offer | transform | Sale's Offer | — |
| `quantity` | 1 = sold, 0 = returned | transform | returned or not | 0 → returned Sale (4 rows) (D-61) |
| `label` | WooCommerce marker or return text | transform | channel; return reason | "Status sprzedaży z wordpress" → channel WooCommerce; on a returned row → return reason; otherwise channel "other" (D-61) |
| `sold_at` | sale date | keep | date | — |
| `sold_by` | unused | drop | — | always empty; marked by System (migration) (D-61) |
| (none) | — | transform | entered price, VAT rate, buyer's price | V1 offer price; VAT by the owner's current Account type; Sale marked "migrated from V1" (D-61) |
| `id`, `created_at`, `updated_at` | technical | drop | — | — |

### `offer_inquiries` — all dropped (D-63)

`id`, `offer_id`, `user_id` (the owner, not the sender), `title`, `content`, `status`, `created_at`, `updated_at`, `deleted_at`: drop. The run report lists the Inquiries still open (Dostarczone or Przeczytane) on Active offers for the admin to resend (3 today).

### `submissions` — all dropped (D-20)

`id`, `title`, `user_id`, `offer_id`, `original_data`, `message`, `is_read`, `created_at`, `updated_at`: drop.

## 3. New information

| V2 information | Start value |
|---|---|
| Glass model of an Offer | from the Catalogue hand-over (V1 offer → Glass model); none → identification hint (D-60) |
| Identification hint | V1 `eurocode`, `oem`, `user_description`, `vim` joined, only when no Glass model |
| First / last approved | `updated_at` for Active, Reserved, Sold, Withdrawn; empty otherwise (D-64) |
| Sale channel, prices, VAT rate, marked by | D-61 |
| Reservation end | D-62 |
| VAT rate setting | 23% |
| "Migrated from V1" marker on Sales | yes |

## 4. Data quality

- **Quantity > 1:** 16 offers (11 Nowa up to 13, 5 Używana up to 4), all Active, none with a sale or reservation: 35 extra pieces as stored. On the client review list (`zweryfikwoania_klientem.md` §9); the client fixes the quantity in V1 before the import. Whatever quantity stands then is split: the original keeps its number, each copy gets a new number, the same Glass model, description, price and copied photos, status Active (D-02, D-68).
- **Statuses:** Aktywna 1409, Sprzedana 317, Usunięta 288 (all soft-deleted), Archiwalna 23, Zarezerowana 5, Odrzucona 4; Szkic, Oczekuje, Wygasła 0.
- **Without eurocode link:** 37 offers (Active 8, Rejected 3, Archiwalna 1, deleted 25); 35 have hint text. The final count follows the Catalogue run.
- **Sales:** 318 rows on 317 offers; 314 sold, 4 returns (3 on now Active offers, 1 on an offer sold again); 3 Sold offers have no sale row → a Sale dated `updated_at`, channel "other" (D-61); 35 from WooCommerce.
- **Reservations:** 14 rows: 5 current (all by admins), 3 on sold, 2 on deleted, 4 orphans.
- **Inquiries:** 20 (8 soft-deleted), sender never recorded.
- **Photos:** 8390 public, 297 private; 2 offers over 10 public; `image` points to a gallery photo in 529 of 532 cases.
- **Years:** 590 filled, 4 malformed ("22", "200", "-" twice); range 1988–2026.
- **Personal data:** reservation labels hold buyer phone numbers and names as free text; they move as notes and must not appear in run reports.
- **Not checked:** media files present on disk for every media row.

## 5. Run plan

- **Order:** Users → Catalogue (its hand-over: V1 offer → Glass model, producer map, hint material) → Offers. Audit has nothing to import.
- **Idempotency key:** V1 offer id (+ piece index for split pieces); V1 sale id; V1 reservation id; V1 media id (+ piece index). A re-run updates what changed and creates nothing twice.
- **Verify counts:** Offers = 2046 + extra pieces after the review; in Trash = 288; per status as §4 after D-59 and D-60; Sales 318 + 3 (4 returned); current Reservations 5, ended 5; public photos 8390 (+ copies), private 297 (+ moved beyond-10 photos).
- **Run report:** every default applied (status moved to Pending, hint built, Sale created for a Sold offer, photo moved to private, year dropped), the open V1 Inquiries to resend, counts above. No personal data.
- **Afterwards (user/admin):** check the Pending tab (the Active offers without a Glass model), resend the listed Inquiries, then let the WooCommerce integration publish.

## 6. Open questions

- Client review §9 (quantity > 1) — blocks only the split step; without an answer the stored quantity is split.
- Shipping (D-55) — `shipping_method_id` dropped until decided.
