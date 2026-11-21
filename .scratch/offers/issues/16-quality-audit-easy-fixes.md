# 16 - Easy fixes from the quality audit

Status: ready-for-agent
Blocked by: none

Source: `.scratch/quality-audit/2026-11-21-offers.md` (finding ids G…). Business behaviour does not change: every item below either moves a rule to its proper home, hardens a guard, or adds a test. Where an item would change what a user sees or what a command decides, stop and log it under **Open questions**.

## What

### A. Ownership is a domain rule (G4, G7)
- Add `OfferDomainPolicy::isOwnedBy(int $ownerId, ?int $actorId): bool` and an entity guard `Offer::ensureOwnedBy(int $actorId)` that throws `OfferRefused::notYours()`.
- Replace the raw `if ($offer->ownerId() !== $userId)` in `SendOfferForApprovalCommandHandler.php:31`, `AcceptInquiryCommandHandler.php:44`, `RejectInquiryCommandHandler.php:36` and `MarkInquiryReadCommandHandler.php:33` with the entity guard.
- UI: `OfferActionVisibility.php:26`, `InquiryActionVisibility.php:28` and `Inquiries/Pages/ViewInquiry.php:32` call the policy. Every `auth()->id()` in `app/Modules/Offers/UserInterface` becomes `CurrentActor::get()->id`, `PrivatePhotoController.php:29` included.
- `ViewInquiry.php:32` `state === InquiryState::Sent`: a policy method that the Inquiry entity's mark-read also calls.

### B. Visibility conditions on record fields call a policy (G6, G7)
- `OfferActionVisibility.php:38` (`glass_model_id === null`), `:127` (`status === Reserved`), `:154` (`trashed_at !== null`), `OfferResource.php:75` (`canEdit`, `trashed_at === null`) and `OfferInfolist.php:55` (Pending + `last_approved_at`): each one becomes an `OfferDomainPolicy` method, or reuses an existing one, and the entity calls the same method where it judges the same thing.
- G6: one policy method for "an admin's own Offer with a Glass model goes live without review", called by `Offer.php:92` and `:366` and by `OfferActionVisibility.php:62-63`.
- `canWithdraw`, `canDuplicate`, `canReserve`, `canRelease`, `canSell`, `canResume` (`OfferActionVisibility.php:47-116`) and `InquiryActionVisibility::canAnswer`/`canReject` add `CurrentActor::can(OffersPermission::Create)`, the permission their handlers ensure.
- Leave `ownerManagesAll()` (`:164-167`, which builds `Actor::user($offer->owner_id)`) as it is. It is deferred (see Out of scope).

### C. Handler refusals into the domain (G5, G10)
- `SendInquiryCommandHandler.php:42`: `Inquiry::send()` receives the fact (whether the Offer can receive an Inquiry) and refuses itself, as `Offer::reserve(bool $hasCurrentReservation)` does.
- `ApproveOffersCommandHandler.php:37`: wrap `$offer->approve()` in try/catch of `OfferRefused` and count a skip, like the other four bulk handlers. Keep the `null` skip. Prove with a test that the done and skipped counts are unchanged for trashed, non-approvable and missing Offers.
- `SellOfferCommandHandler.php:68`: `OwnerAccountType::tryFrom(...) ?? throw OfferRefused::<new key>` (key in `lang/pl/exceptions.php`), so an unknown account type is a refusal, not a `ValueError`.

### D. Persistence robustness (G11, G12, G13, indexes)
- `OfferMapper.php:26-39` and `SaleMapper.php:25-26`: a row that cannot become a valid entity throws `UnexpectedValueException` naming the record id. Follow `Catalogue/.../Mappers/GlassModelMapper.php:32`.
- One new migration in `Infrastructure/migrations/`:
  - at most one unreturned Sale per Offer, using the same nullable-unique pattern as `offer_reservations.is_current` (for example `is_open` true while not returned, null after; `unique(offer_id, is_open)`), with existing rows backfilled. `EloquentSaleRepository.php:28` then reads by that column instead of `latest('id')`;
  - indexes on `offer_sales.sold_at` and `offers.first_approved_at`.
- `StorageOfferPhotoStorage::deleteAll` defers the file delete until after commit (`DB::afterCommit`), so a rolled-back permanent delete keeps the files.

### E. UI fixes (G14, G15, G16, nits)
- G14: Audit publishes its value partial as a component: `app/Modules/Audit/UserInterface/resources/views/components/value.blade.php`, used as `<x-audit::value :value="…" />`. Audit's own `change-details.blade.php` and Offers' `changes-since-approval.blade.php` use it, and `partials/value.blade.php` goes away. Check how Catalogue registers `<x-catalogue::…>` and do the same for Audit if needed.
- G15: `PrivatePhotoController.php:25` reads the Offer through an `OfferQueries` method instead of `OfferModel::query()`.
- G16: `ReservationsTable.php:46` uses `OfferListGlass` (one batch per page) instead of `OfferGlassLabel::of`. Delete `OfferGlassLabel` when nothing else uses it.
- `ViewOffer.php`: add `hasCombinedRelationManagerTabsWithContent()` and `getContentTabLabel()` as in `Users/.../ViewUser.php:20-28`, so the infolist is tab 1 and History is tab 2 (template C). Layout (b) is out of scope.
- `OffersTable.php:102`: columns move to `Tables/OffersTableColumns::make()` (11 columns).
- Hard-coded "zł" (`OffersTable.php:110`, `OfferForm.php:224`, `SellOfferAction.php:45`, `ChangePriceOffersBulkAction.php:53`) becomes one translation key. `OffersTableFilters::grosze` reuses `OfferFormData::grosze`.
- `OfferForm.php:219`: the price field in the "Cena" section gets `->hiddenLabel()`.
- `Domain/Enums/InquiryState.php:35`: `Accepted` becomes `gray`, as its docblock says.
- `OffersTableFilters.php:147`: owner options come from an `OfferQueries` method (owners who have Offers), not from `->relationship('owner', 'name')`.
- `OfferQueries.php:260`: `'new'` becomes `OfferCondition::New->value`. Remove the dead `OfferObserver::restored()` (`:39`; the model has no SoftDeletes).

### F. Missing tests (G17)
- Assert `translationKey` in these refusal tests: `AcceptInquiry…Test:131`, `SendOfferForApproval…Test:117`, `DuplicateOffer…Test:90`, `WithdrawOffer…Test:95`, `ChangeVatRate…Test:63`, `ChangePriceOfOffers…Test:79`, `RejectInquiry…Test:66`; `OfferTest.php:79,91,124,133`; `OfferContentTest:49,56`, `GlassReferenceTest:52`, `EnteredPriceTest:21`, `OfferNumberTest:40`, `PriceChangeTest:43,50`, `RatingTest:31`, `VatRateTest:27,33`. Add a test that asserts `producer_cannot_be_picked`, and a policy test for `canReceiveInquiry`.
- Add an unauthorized-actor test to the AcceptInquiry, RejectInquiry, MarkInquiryRead, ChangePriceOfOffers, WithdrawOffers, UpdateOffer and DuplicateOffer handler tests.
- Add repository include/exclude tests for `idsOfOwner`, `isCandidate`, `candidateIdsOfGlassModel`, `candidateIdsOfProducer`, `pricedOffers` and `pricedOffersOfOwner`, and `OfferQueries` tests for `countByGlassModel`, `onlyStatus`, `ofGlassModels` and `withoutGlassModel`.
- Add observer tests for Reservation, Sale and Inquiry, and a "save without change records none" case for Offer.
- Add a Livewire create test with every optional field empty (`CreateOfferTest`), and assert the notification title in `CreateOfferTest.php:90` and `ViewOfferApprovalTest.php:54`.

## Out of scope (deferred by the user, 2026-11-21)
- G1/G2: how domain events are emitted (repository dispatch, fallback System actor, Service and listener dispatch). This needs a design decision.
- G3: `BuyersPrices` used by Offers' own UI. G9: read port for `pricedOffers*`.
- G8: `__()` inside `OfferRefused::missingForSending` (needs a Core change in `CommandSender`).
- `ownerManagesAll()` building another user's actor. `PrivatePhotoController` panel middleware. Layout (b) on ViewOffer. `date('Y')` in `OfferContent`. Port names (`OfferPhotoStorage`, `VatRateSetting`). The `ChangeGlassModelOfOffers` double refusal. `WithdrawOfferAction` colour.

## Done when
- Each item in A–D starts with a test that fails for the right reason: the guard in the entity, the policy method, the refusal key, the bad-row exception with its id, the duplicate open Sale refused by the DB, and the files kept after a rollback. Then it goes green.
- E keeps the existing Livewire tests green and adds one for the History tab on ViewOffer.
- `grep -rn "auth()" app/Modules/Offers/UserInterface` finds nothing. `grep -rn "audit::partials" app/` finds nothing.
- The full `composer check` passes. This is a single-issue run, and the diff touches Audit (`module-testing`, Which check closes the work). Report the migration file and remind the user to run `dartisan migrate`.

## Comments
