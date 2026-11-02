# r06 — flow: Account type change (private <-> company) on own profile

Question: What exactly happens, end to end, when a user switches account type on their profile page, incl. WooCommerce/WordPress effects and readers of the flags? · V1 commit: 511ce3b

## Summary
- Any logged-in user saves the profile form with a different "Rodzaj konta". In order: validate, set 30-min cache lock, write a `user_notifications` row + database notification to all admins, queue Woo update jobs for the user's Woo-synced offers (chunks of 10), THEN save the user. No transaction.
- The jobs re-load offers from DB at run time (SerializesModels) and `$offer->user` is lazily loaded then, so the resync reads the NEW account type unless a worker runs in the milliseconds before `$user->update()` (race; queue is `database`, not after-commit).
- Effect in WooCommerce: only `regular_price` can change (net for company, net x 1.23 for private). The whole product is re-PUT (images, ACF, categories, status from setting). No visibility/catalog flag depends on account type anywhere in V1.
- The "offers visible only to you and allGlass staff" text is UI copy only; no query/scope/Woo status implements it (see finding). Non-Woo-synced offers are untouched; gross is computed on read everywhere (no stored `price_gross`).
- Effective company status is `company_account && has_accepted_terms`; a company switch without accepted terms still behaves as private.

## Findings
### Precondition: only a changed value triggers the flow; form validation gates it
- Tag: confirmed · Impact: normal
- What happens: Page open to every authenticated user (`canAccess()` true). `update()` runs `$this->validate()` and `form->getState()`. Flow starts only if `company_account` key is present in form state AND differs (bool cast) from the stored value. Other profile edits skip the block entirely. Company fields `company_nip`, `company_address` are required and `has_accepted_terms` (label "Oświadczenie Sprzedawcy") must be ticked (`required()->accepted()`), but these are hidden/disabled when private is selected, so not validated/dehydrated then.
- Evidence: app/Filament/Pages/ProfilePage.php:42-45 canAccess; :111-127 update(); :213-236 company_nip/address/has_accepted_terms hidden+required; :193 toggle `->disabled(Cache::has(lock))`.

### Switching is rate-limited by a 30-minute lock, per user
- Tag: confirmed (lock set/read); inferred (server-side enforcement) · Impact: normal
- What happens: `Cache::put("company_account_lock_{id}", now()+30min, now()+30min)`. While present, the toggle renders disabled with helper text "Zmiana rodzaju konta jest zablokowana do: HH:MM" (value = stored expiry, formatted H:i). Because `form()` is rebuilt on each Livewire request, a disabled field is not dehydrated, so a locked user's save should not contain `company_account` (inferred from Filament 3 behaviour, not runtime-tested). Lock is not cleared by anything else; expires by TTL. Cache store = database (.env.example CACHE_STORE=database).
- Evidence: ProfilePage.php:121, :193-199; .env.example:39.

### Reader list of the three cache keys
- Tag: confirmed · Impact: normal
- `company_account_lock_{userId}`: written ProfilePage.php:121; read only ProfilePage.php:193 (disabled) and :195-197 (helper text). No other readers (grep of app/, resources/, routes/).
- `pending_offer_status_notice_{offerId}`: NOT related to account type. Written 1-min TTL by OfferCreatedAction::processAction (app/Filament/Actions/Journal/OfferCreatedAction.php:32-33, skipped for admin owners); read ViewOffer.php:142-146 to show "Świetnie! Dodałeś kolejną ofertę! Po sprawdzeniu jej poprawności, trafi do katalogu allGlass." Keyed by offer id, not user id as the brief assumed.
- `draft_offer_status_notice_{offerId}`: also unrelated. Written DraftOfferCreatedAction.php:32-33; read ViewOffer.php:154-158 ("Świetnie! Dodałeś szkic oferty. Możesz ją aktywować dokonując edycji na liście swoich ofert"). Not set by the account-type flow.

### Modal informing private users about VAT
- Tag: confirmed · Impact: normal
- What happens: When the toggle's new state is the string "0" (private), `afterStateUpdated` dispatches `open-modal` id `account-info-modal`. The modal (heading "Pamiętaj!") says prices will be increased by VAT before being added to the public catalog; button "Rozumiem" only closes it. Purely informational, fires on toggle click, before save; toggling to private and not saving still shows it. `getAccountInfoModal()` (ProfilePage.php:290-299) is a Filament Action with `->call()` that is not referenced by the blade or elsewhere; the blade modal is what is actually shown. The wiring is dead code.
- Evidence: ProfilePage.php:174-179, :290-299; resources/views/filament/pages/profile-page.blade.php:16-29; grep getAccountInfoModal -> only its definition.

### Step: user notification + admin notification
- Tag: confirmed · Impact: normal
- What happens: Title "Uzytkownik {name} zmienił rodzaj konta na: Firmowe|Prywatne" (sic, no "ż"). `UserNotification` row saved (title, user_id) — shown in admin "Aktywność użytkowników" list (ListUsersNotifications). Then every user with role `Admin` or `Super Admin` gets a Filament database notification (title, info) via `notifyNow` (synchronous, no queue). Note "Firmowe" is based on the toggle only, not on terms acceptance.
- Evidence: ProfilePage.php:119-122; app/Services/User/UserNotificationService.php:11-34; User.php:143-148 getAdmins; app/Livewire/Users/ListUsersNotifications.php:33.

### Step: resync dispatch
- Tag: confirmed · Impact: high (external system)
- What happens: `OfferUpdateService::handleUsersPublishedOffer($user)` selects `$user->offers()` that have a `wooSync` row (any offer status, incl. sold/archived/draft if a sync row exists), chunk(10), dispatches one `OfferUpdateOffersChunk` per chunk to the default queue. Despite the name it does not filter on "published".
- Evidence: app/Services/Offer/OfferUpdateService.php:11-18.

### Resync reads the NEW account type (normally)
- Tag: observed (code path) · Impact: high
- What happens: Job uses `SerializesModels` (via Illuminate\Foundation\Queue\Queueable), so the Collection of Offers is stored as ids and re-queried when the worker runs; `user` and `wooSync` relations were not loaded at dispatch and are lazy-loaded fresh in the worker. `WooProductDTO::fromOffer` calls `getAdjustedOfferPrice($offer->price, $offer->user)` -> `isCompanyAccount()`. Since `$user->update()` happens right after dispatch in the same request, the worker sees the new flag unless it picks up the job before the update commits (database queue, `after_commit` false at config/queue.php:43 etc.; no transaction around). Race window is tiny but real; the outcome would be a Woo price from the OLD type with no retry/repair. Not runtime verified.
- Evidence: Queueable.php:12 (SerializesModels, vendor); OfferUpdateOffersChunk.php:10-30; Offer.php:401-404, :543; WooProductDTO.php:59; OfferPriceService.php:13-20; config/queue.php:16 default `database`, .env.example:37.
- Note: a company switch with `has_accepted_terms` false still yields private pricing (x1.23) — price does not change then.

### What changes in WooCommerce
- Tag: confirmed · Impact: high
- What happens: Job calls `WoocommerceApiService::updateProduct($offer)` per offer -> `UpdateProductToWordpressAction`: builds full DTO, uploads any images lacking `wordpress_id` to WP, syncs color/brand/vehicle/equipment terms, syncs category, `PUT products/{wordpress_id}`; on success sets `wooSync.last_update = now()`. Account-type-dependent field: `regular_price` only = offer net price (company) or round(net x 1.23, 2) (private). `status` is `draft` or `publish` from Setting `woocommerce_save_as_draft`, not from account type; stock_status from offer status. So WooCommerce "visibility" does not vary by owner type; a private owner's offers already in Woo stay public there (only price differs). A switch to company lowers regular_price back to net.
- Evidence: UpdateProductToWordpressAction.php:25-52; WooProductDTO.php:55-70; OfferPriceService.php:15-19; grep isCompanyAccount in app/Services/ApiService -> none.

### Private-account notice vs real visibility logic
- Tag: confirmed (absence) · Impact: high
- What happens: Text "Korzystając z konta prywatnego Twoje oferty będą widoczne tylko dla Ciebie i pracowników allGlass. Zmień typ konta na firmowe, aby wyświetlać oferty w katalogu publicznym" is shown when private selected. No scope/policy/Woo status in V1 hides private owners' offers from the public catalog; Woo sync/create (`syncProduct`, `canBeWooSynch`) checks only offer status and fields. Contradicts the modal wording, which says private prices are raised by VAT "przed dodaniem ich do katalogu publicznego" — i.e. private offers DO go to the public catalog. Whether the public Woo site hides them is outside V1 (WordPress theme/plugins) -> see Could not establish.
- Evidence: ProfilePage.php:244-247; grep of isCompanyAccount/company_account across app/ (full result list in ledger) shows only price, labels and admin hints; Offer.php:294-310 canBeWooSynch.
- Tag: decide — which of the two statements is the intended rule for V2.

### Non-Woo-synced offers
- Tag: confirmed · Impact: normal
- What happens: Not touched by the flow (filter `whereHas('wooSync')`). No stored gross price: `price_gross` is only a form/display field computed from `price` and current owner type (OfferEditForm.php:277,285-294; OfferCreateForm.php:244-259; ViewOffer.php:240-245; table columns in MyOffers/Reserved/FullList). So displays change immediately on save for all offers. Offers never synced to Woo get the new price when first synced (DTO built at sync time). Otomoto adverts also compute price via the same service at create/update time (BulkCreateAdvertsAction.php:52, UpdateAdvertAction.php:27, UpdateCoreAdvertData.php:30, OtomotoSynchronizationTab.php:205) but are NOT re-pushed by this flow, so already-published Otomoto adverts keep the old price until separately updated (lead).

### Readers of the account flags (the effect surface)
- Tag: confirmed · Impact: normal
- `isCompanyAccount()` (User.php:177): price net/gross (OfferPriceService, used by Woo DTO, Otomoto, tables, edit/create forms); hides `price_gross` fields for company owners (OfferEditForm.php:294, OfferCreateForm.php:259, ViewOffer.php:245); admin label "Firmowe"/"Prywatne" (AdminTab.php:60, UserResource.php:90-93); badge color info/warning for owner (AdminReservedOffersTable.php:127, AdminInquiriesTable.php:95, StockSaleTab.php:88, FullListOffer.php:455, ChangesTableSchema.php:40); admin hint "Uzytkownik tej oferty nie zaznaczył oświadczenia sprzedawcy." when owner not company (ViewOffer.php:131); navigation badge "i" on "Mój profil" for non-company (ProfilePage.php:51-54). Note FullListOffer.php:435 treats offers without owner as company (no markup).
- Raw `company_account` is only read in ProfilePage; `has_accepted_terms` also edited in admin EditProfile.php:67.

### Failure paths
- Tag: confirmed (code) / unknown (runtime) · Impact: high
- No DB transaction. Lock + `user_notifications` row + admin notifications are committed before the jobs and before `$user->update()`; if dispatch or update throws, lock/notification remain while the flag is unchanged (user locked 30 min, admins told of a change that did not happen). `$user->update()` uses global `Model::unguard()` (AppServiceProvider.php:40) so all form keys incl. password (when filled, hashed) are mass-assigned. User model has LogsActivity (audit entry, inferred from trait).
- Job: no `tries`, `backoff`, `failed()` defined (grep app/Jobs). `HttpClientException` is caught in `updateProduct` -> logs, sends a Filament notification (no session in a worker, effect unknown), returns false; job completes "successfully", no retry. If the error is 400 `woocommerce_rest_product_invalid_id`, the `wooSync` row is deleted (offer treated as unsynced). Other exceptions (e.g. image upload via WordpressApiService) fail the job; default worker `--tries` is not configured in the repo, so likely 1 attempt then `failed_jobs`. If Woo credentials settings are empty, `updateProduct` silently returns (client null) after notifier error. Failures never surface to the profile user, who has already seen "Zapisano dane profilu". Offers in a chunk are processed sequentially; a throw stops the remaining ones in that chunk.
- Evidence: WoocommerceApiService.php:117-141; OfferUpdateOffersChunk.php:25-29.

## Entry points
| Kind | Path | Does | Reachable |
|---|---|---|---|
| Filament page | app/Filament/Pages/ProfilePage.php update() | the flow | yes, nav "Mój profil", header action "Zapisz" and form submit button |
| Queue job | app/Jobs/Offer/OfferUpdateOffersChunk.php | Woo update of up to 10 offers | only via OfferUpdateService |
| Service | OfferUpdateService::handleUsersPublishedOffer | sole caller is ProfilePage (grep) | yes |

## Contradictions and defects
- defect? Lock/notification/jobs happen before `$user->update()` with no transaction (see Failure paths).
- defect? Rapid worker pickup can price with old type (race).
- defect? `getAccountInfoModal()` unused; blade modal duplicates its text.
- defect? Title typo "Uzytkownik". Switching to company without ticking terms is impossible (required) but switching to private leaves `has_accepted_terms` as is in DB (hidden field not dehydrated), so a later company switch sees it already true (pre-filled) — inferred.
- Contradiction: private notice says offers visible only to owner+staff vs modal saying they enter public catalog with VAT; code implements only the latter's pricing.

## Leads outside scope
- Otomoto adverts are not repriced on account change (OtomotoService actions).
- Woo-side (WordPress/theme) visibility of private-owner products; V2 must define it.
- Offer approval flow ("po sprawdzeniu trafi do katalogu") determines when offers get a wooSync row.
- Who runs `queue:work` in production (no supervisor config in repo).

## Could not establish
- Whether any WordPress-side logic hides private-owner products: not in V1 repo; needs WP site access/business answer.
- Whether a locked toggle truly cannot be submitted (Filament disabled-field dehydration): needs runtime.
- Worker tries/timeouts in production: deployment config absent; runtime/ops answer.
- Behaviour of `Notification::send()` inside a queue worker (Notifier in updateProduct): runtime.

## Coverage ledger
- Searched: company_account_lock_, pending_offer_status_notice_, draft_offer_status_notice_, account-info-modal, getAccountInfoModal, handleUsersPublishedOffer, OfferUpdateOffersChunk, isCompanyAccount, company_account, has_accepted_terms, getAdjustedOfferPrice, price_gross, unguard, QUEUE/CACHE env, tries/backoff/failed in Jobs, UserNotification.
- Examined: ProfilePage.php, profile-page.blade.php, OfferUpdateService, OfferUpdateOffersChunk, UserNotificationService, UserNotification, WoocommerceApiService, UpdateProductToWordpressAction, WooProductDTO, OfferPriceService, User.php (relevant parts), Offer.php (wooSync, user, canBeWooSynch), OfferCreatedAction, DraftOfferCreatedAction, ViewOffer (notices), config/queue.php, .env.example, AppServiceProvider, vendor Queueable trait.
- Matched, not examined: the table/column files listed under "Readers" (only the matching lines seen); Otomoto actions; SyncProductToWordpressAction (create path; shares the DTO); ListUsersNotifications beyond its query.
