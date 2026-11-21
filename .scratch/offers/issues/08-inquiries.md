# 08 - Inquiries

Status: resolved
Blocked by: 07

Spec: FR-21, FR-21a, FR-22, FR-23, §7 Inquiry states, §8 Inquiry, FR-34 (Inquiry, answer, Outdated), FR-32 page "Zapytania". Decisions: D-22, D-35, D-41, D-65, D-66.

## What

- An admin sends an Inquiry about an **Active** Offer: optional channel, note; the sending admin is recorded; before sending the admin sees how many are open and from whom. Any number may be open.
- States Sent → Read (owner opens) → Accepted / Rejected; Sent or Read → **Outdated**.
- **Accept** (owner, only while Active): creates the Reservation with the note, in the name of the sending admin (so locked for the seller), linked to the Inquiry. **Reject**.
- Whenever an Offer stops being Active by any route (reserved, sold, withdrawn, deleted, rejected, sent back to Pending), its open Inquiries become Outdated.
- Journal: to the owner on a new Inquiry; to the sending admin on accept, reject, Outdated.
- Seller page "Zapytania"; admin list of all Inquiries; permanent delete removes them.

## Done when

- Accept creates an admin-locked Reservation; a second open Inquiry becomes Outdated; every exit from Active closes open Inquiries.
- `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** FR-21, FR-21a, FR-22, FR-23, FR-26 (cascade), FR-32 "Zapytania", FR-34 (Inquiry parts), §7 Inquiry states, §8 Inquiry. **"Stops being Active" is implemented once:** `Offer` routes every status change through a private `moveTo()` (and `moveToTrash` while Active) which sets a flag; `EloquentOfferRepository::save()` pulls it (`Offer::pullLeftActive()`) and dispatches the domain event `OfferStoppedBeingActive(offerId, actor)` (Actor from `ActorStack`, in the command's transaction). One synchronous listener `OutdateInquiriesWhenOfferStopsBeingActive` sends `OutdateInquiriesOfOffer`, which outdates every open Inquiry and writes Journal entries to the senders (not to the actor). No handler calls it; reject, withdraw, reserve, sell, delete (Trash), a seller's edit back to Pending and later the System's withdraw (12) are all covered, tested per route. Entity `Inquiry` (+ `InquiryState`, rules in `OfferDomainPolicy::canReceiveInquiry`), port `InquiryRepository`, mapper, read-only `InquiryModel`, factory, observer, Audit type with presenters, `InquiryQueries` (`visibleTo`, `openOfOffer`). Commands `SendInquiry` (admin, Active and not in Trash; owner gets a Journal entry via `OwnerJournal`), `MarkInquiryRead` (owner opens; the view page sends it on mount), `AcceptInquiry` (owner only; saves the Inquiry first, then `Offer::reserve` through the 07 path, then a `Reservation` with `made_by_admin` true in the sender's name with `inquiry_id`; the others outdate through the reaction), `RejectInquiry`, `OutdateInquiriesOfOffer` (reaction only). Journal to the sending admin on accept, reject and outdated through the new `InquirySenderJournal`. `DeleteOfferPermanently` removes Inquiries after Reservations. Migration: `offer_inquiries` + `offer_reservations.inquiry_id`; Reservation audit shows the inquiry. UI: `InquiryResource` (list "Zapytania" in the Oferty group, seller sees own, admin all; view page with Zaakceptuj and Odrzuć) and the "Wyślij zapytanie" action (in "⋮" on the Offer view page and row), whose modal first says how many are open and from whom.
- **Deviations:** (1) The Inquiry's channel reuses `SaleChannel` (panel/WooCommerce/OLX/other); spec says only "optional channel". (2) The Inquiry note is required (max 500), because accepting copies it into the Reservation whose note is required. (3) `answered_at` is set on accept/reject only; Outdated has no own date. (4) The reaction's command checks no permission: it is sent only by the listener with the Actor of the already authorised command. (5) Only the owner accepts or rejects, an admin who is not the owner cannot; an admin owner may send to themselves (no Journal entry then). (6) The domain event is dispatched by the repository adapter from an entity flag, not by a handler, as asked; module-events says handlers dispatch. (7) Sending the Inquiry is only offered in "⋮" (the page already has reserve and sell as secondary actions).
- **Seams left:** 13 may listen to `OfferStoppedBeingActive` too; 10 reads the Inquiry state for any tab or stat card it wants.
- **Open questions:** none
- **Check:** composer check green (1539 tests); needs COMPOSER_PROCESS_TIMEOUT=0 here, the suite now exceeds composer's 300 s default

### Review — 2026-11-22
- **Verdict:** approved after 0 fix rounds. The manager made one change inline: `EloquentOfferRepository` gets `ActorStack` by constructor injection instead of `app()`. Offers tests were re-run and are green.
- **Notes:**
  - **"Stops being Active", implemented once:** `Offer::moveTo()` and `moveToTrash` while Active set a flag. `EloquentOfferRepository::save()` pulls the flag and dispatches `OfferStoppedBeingActive` with the command's Actor. One listener sends `OutdateInquiriesOfOffer`.
  - **Accepted deviation from `module-events`:** that skill says a handler dispatches every business event. The issue requires a single reaction, not one per handler. Flagged to the user, who may want the skill to name this pattern.
  - **Accept:** the owner only. It reserves through `Offer::reserve`, in the sender's name (`made_by_admin` true), linked by `offer_reservations.inquiry_id`.
  - **Mark read:** sent on mount of the Inquiry view, by the owner only.
  - **Other accepted deviations:**
    - the channel reuses `SaleChannel`;
    - the note is required, up to 500 characters;
    - `OutdateInquiriesOfOffer` checks no permission (internal, carries the original Actor).
  - **Seams:** 13 can listen to `OfferStoppedBeingActive`. A permanent delete removes Inquiries.
  - **Suite runtime:** it now exceeds composer's 300 s timeout. Run `composer check` with `COMPOSER_PROCESS_TIMEOUT=0`.
