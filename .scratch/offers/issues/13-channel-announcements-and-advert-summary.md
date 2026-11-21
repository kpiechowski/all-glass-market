# 13 - Announcements for channels and the offer summary for adverts

Status: resolved
Blocked by: 07, 08

Spec: §10, RULE-12, §9 Catalogue events. Decisions: D-05, D-26, D-51. ADR 0010. Skill: `module-events`.

## What

- Public Offers events, each carrying the Offer id: became a candidate for adverts (approved, resumed, returned, released, restored), changed while a candidate (incl. buyer's price change from 05 and a Glass model change), reserved, sold, stopped being a candidate (rejected, back to Pending, withdrawn, deleted).
- React to Catalogue's *Glass model changed / withdrawn / restored* and *Producer changed / withdrawn* by announcing "changed while a candidate" for the affected Active/Reserved Offers.
- A module-interface read: the **offer summary for adverts** — offer number, Glass model summary, producer, year, condition, rating (Nowa flagged for five stars), public description, public photos in order, buyer's price, reservation flag. Candidates are Active or Reserved Offers.
- No channel code here; integrations consume this later.

## Done when

- Each transition raises the right event (tested); the summary is tested through the module interface; `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** §10, RULE-12. Emission mechanism: the **entity records, the repository dispatches** (08's pattern), for every transition without exception. `Offer::pullAnnouncements()` compares the candidate state (Active or Reserved, outside the Trash) the Offer had when loaded (or last asked) with the one it has at save, so every route is covered in one place (approve, bulk approve, resume, return, release, restore, reserve incl. accepted Inquiry, sell, reject, edit back to Pending, withdraw single/bulk/System, Trash single/bulk). `EloquentOfferRepository::save()` maps the result to new public Domain events, each carrying only the Offer id: `OfferBecameCandidate`, `OfferChangedWhileCandidate`, `OfferReserved`, `OfferSold`, `OfferStoppedBeingCandidate` (08's `OfferStoppedBeingActive` is untouched). "Changed while a candidate" is flagged by `edit` (only fields adverts show: not private notes or private photos), `changePrice`, `changeGlassModel`, `arrangePhotos`. Listeners: `AnnounceCandidatesWhenBuyersPriceChanges` (filters 05's event down to candidates; new port `OfferRepository::isCandidate`), `AnnounceCandidatesWhenGlassModelChanges` (Catalogue changed/withdrawn/restored) and `AnnounceCandidatesWhenProducerChanges` (changed/withdrawn): one query each (`candidateIdsOfGlassModel/OfProducer`), then one event per Offer. ModuleApi `OfferAdvertSummaries::of($offerId)` returns `OfferAdvertSummary` DTO (offer id, number, Catalogue `GlassModelSummary`, producer id and name via `CatalogueDictionaries`, year, condition value, rating, `showsFiveStars` for Nowa, description, public photo paths in order, buyer's price via `BuyersPrices`, `reserved`) or null for a non-candidate. Tests: entity unit tests; `OfferAnnouncementsTest` (every route through the command bus, a non-candidate raises nothing, private-notes/no-op edits raise nothing); listener tests; ModuleApi test.
- **Deviations:** (1) A sale raises `OfferSold` only, not also `OfferStoppedBeingCandidate` (spec lists "sold" separately from the stop list); consumers treat sold as the end of the advert. (2) Release (Reserved to Active) raises `OfferBecameCandidate` as §10 lists "released" there, although the Offer was a candidate throughout. (3) Edits of private notes or private photos only raise nothing (adverts do not show them). (4) The buyer's-price listener does one cheap `exists` per event (the event is per Offer; a VAT change raises one per priced Offer). (5) The listeners dispatch the public event directly (they change no state, so no command). (6) Accepted: module-events says handlers dispatch; the repository-adapter pattern from 08 is used for consistency, as the issue allows. (7) Photos in the summary are storage paths, not URLs.
- **Seams left:** integrations listen to the five events and call `OfferAdvertSummaries::of`; a null answer means "take the advert down".
- **Open questions:** none
- **Check:** composer check:module offers green (599 tests)

### Review — 2026-11-21
- **Verdict:** approved after 0 fix rounds.
- **Manager's inline change:** the candidate rule (Active or Reserved, outside the Trash) was written in three places. It now lives once, in `OfferDomainPolicy::isCandidate` and `CANDIDATE_STATUSES`, and the entity, the repository and `OfferAdvertSummaries` all use it. A policy unit test was added. `composer check:module offers` was re-run: green, 600 tests counting the new one.
- **Emission:** 08's pattern for every route. `Offer::pullAnnouncements()` compares the candidate state at load with the state at save, plus a "changed for adverts" flag. `EloquentOfferRepository::save()` dispatches inside the command transaction.
- **Public events:** `OfferBecameCandidate`, `OfferChangedWhileCandidate`, `OfferReserved`, `OfferSold`, `OfferStoppedBeingCandidate`, each carrying the Offer id only. 08's `OfferStoppedBeingActive` is unchanged.
- **Accepted deviations:**
  - A sale raises `OfferSold` only. A consumer treats it as the end of the advert.
  - Release raises `OfferBecameCandidate`, as §10 lists it.
  - Edits to private notes or private photos only announce nothing.
  - Listeners on Catalogue events and on `OfferBuyersPriceChanged` dispatch `OfferChangedWhileCandidate` directly.
- **Seam for integrations:** `OfferAdvertSummaries::of($offerId)` returns an `OfferAdvertSummary` DTO, or null for a non-candidate. Photos are storage paths; turning them into URLs is the integration's job.
