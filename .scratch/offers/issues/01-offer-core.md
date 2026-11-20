# 01 - Offers module and the Offer: create, edit, view, own lists

Status: resolved
Blocked by: catalogue 09 (the Glass model picker slide-over)

Spec: FR-01, FR-02, FR-03, FR-04, FR-05 (descriptions and private notes; photos are 02), FR-06 (Draft saving), FR-15, RULE-01, RULE-02, RULE-07, RULE-11, RULE-13, §2, §3, §8 Offer, §9 Catalogue and Audit. Decisions: D-01, D-02, D-08, D-09, D-10, D-16, D-17, D-24, D-25, D-29, D-38, D-40, D-46, D-49. ADR 0009.

## What

- The **Offers module** (skills: `module-layout`) with its Permissions, translations and navigation section "Oferty".
- The **Offer** as a Draft: owner (the creator, never changes), offer number "AG" + 8 digits (assigned once, unique), Glass model id **or** identification hint (never both, never glass parameters), producer (Catalogue's producer list), year, condition (Nowa / Używana / Outlet), rating 1–5 only for Używana and Outlet (Nowa stores none and is shown with five stars), public description, private notes, entered price.
- **One form**, no wizard, used by sellers and admins: "Szyba" section on top with "Wybierz model szyby" (catalogue 09's slide-over; the section then shows the model's card) and "Nie znam modelu" (hint field), then the piece, private notes, price. Saving keeps the Offer a Draft; sending for approval is 03.
- **View page**: the piece, the Glass model summary from Catalogue's module interface (current data, withdrawn models still shown), private notes only to the owner and admins.
- **Plain lists**: seller "Moje oferty" (own only), admin "Wszystkie oferty" (all). Tabs, filters and stat cards are 10.
- Register the Offer as auditable with Audit (Change history).
- A seller never sees another seller's Offer (lists, view, edit → refused).

## Done when

- A seller and an admin can create and edit a Draft with a Glass model or a hint; the owner is the actor; the number is assigned once.
- Refusals: Glass model and hint together; rating on Nowa; another seller's Offer.
- Test-first entity, value object and handler tests, mapper and repository tests, a Livewire test per page; `composer check` passes.

## Comments

### Worker — 2026-11-20
- **Built:** the Offers module (scaffolded, `OffersPermission` Create for User and Admin, ManageAll for Admin; sidebar group "Oferty"). Offer entity as Draft only (FR-01..FR-06 Draft saving, RULE-01/02/07/13): `GlassReference` VO (Glass model id XOR hint, RULE-02), `Rating`, `EnteredPrice` (grosze), `OfferNumber` ("AG"+8 digits, unique index, drawn by `OfferNumberGenerator`), `OfferContent` (rating only for Używana/Outlet, FR-04), `OfferDomainPolicy` (rating applies, five stars for Nowa D-46, `canBeSeenBy` RULE-11). `CreateOffer`/`UpdateOffer` commands: authorise, owner = actor, handler re-checks the picker id and producer through Catalogue's `GlassModelSummaries` / `CatalogueDictionaries` only (exists and not withdrawn; a withdrawn value the Offer already has survives). Migration, model, factory, mapper, repository, `OfferQueries::visibleTo`, observer + registered as auditable record `offers.offer` (FR §9 Audit). One Filament form ("Szyba" with Catalogue's slide-over `PickGlassModelAction` and "Nie znam modelu"), view page (Glass model summary from Catalogue, withdrawn still shown, private notes owner/admin only), plain list "Moje oferty" / "Wszystkie oferty" (title by permission). Seller never reaches another seller's Offer (query scoped: list, view, edit are 404; handler refuses `not_yours`).
- **Deviations:** (1) A Draft takes every field empty (FR-06 "any content"), so condition, price, producer are nullable; only owner and number are required. (2) Price stored as `price_grosze`; the form types złoty. (3) Glass model id and producer id have no DB foreign key across the module line (Catalogue's own guards own deletion). (4) `OfferStatus` enum holds only `Draft`. (5) No domain events emitted (nothing in this issue's spec needs one). (6) Added `UserInterface\Filament\GlassModelPicker\` to `ModuleBoundaryScanner::PUBLIC_SURFACE` and one row to the `module-layout` skill table, otherwise the picker seam of catalogue 09 would count as a boundary violation. (7) No History tab (admin only, ticket 03/FR-12). (8) List cells call `GlassModelSummaries::of` per row (no batch yet, 10 will need one).
- **Seams left:** statuses and transitions, photos (02), sending for approval and FR-06 send checks (03), tabs/filters/stat cards/bulk actions (10), "N ofert" and in-use answers for Catalogue (11), Journal entries, price/VAT.
- **Open questions:** none.
- **Check:** composer check green (1165 tests). Also: updated `AdminPanelProviderTest` for the new "Oferty" group; copied the gitignored `public/build` into the worktree so Vite-dependent tests run.

### Worker — fix 1 — 2026-11-20
- **Built:** `price_grosze` now shows as money in the Change history: new `ValuePresenter::moneyInGrosze()` (kind `MoneyGrosze`) in Audit's ModuleApi, rendered by `ChangePresenter` as grosze / 100 through the existing money formatting; registered for `price_grosze` in `OffersModuleServiceProvider`. Test `test_a_price_in_grosze_is_shown_in_zloty` in `ChangePresenterTest` (120000 -> "1 200 zł", 12345 -> "123,45 zł"). Removed `extraInputAttributes(['class' => 'max-w-48'])` from the price field in `OfferForm` (no matching variant).
- **Deviations:** none
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green (1166 tests)

### Review — 2026-11-20
- **Verdict:** approved after 1 fix round (history showed price in grosze; Tailwind utility on a schema component)
- **Notes:** Draft takes any content (condition, price, producer nullable); price stored as `price_grosze`, history uses the new Audit `ValuePresenter::moneyInGrosze()`. Handlers re-check the picker id and producer through Catalogue's ModuleApi; a withdrawn value already on the Offer survives a save. Catalogue's `UserInterface/Filament/GlassModelPicker/*` added to the public surface (boundary scanner + module-layout skill). Left for later: list reads `GlassModelSummaries::of` per row (batch read in 10); the history shows `glass_model_id` as a raw id (04). Hand-check the slide-over picker in the browser.
