# 03 - Approval: send, approve, reject, re-approval

Status: resolved
Blocked by: 02, catalogue 09 (in-place Glass model creation)

Spec: FR-06 (sending), FR-07, FR-08, FR-09, FR-10, FR-11, FR-13, RULE-03, RULE-04, RULE-14 (bulk approve), §7 rows Draft/Pending/Rejected/Active, FR-34 (approve, reject, admin change). Decisions: D-19, D-20, D-31, D-37, D-40, D-42, D-44, D-45, D-49.

## What

- Statuses **Draft, Pending approval, Rejected, Active** and only the §7 transitions between them (later issues add the others).
- **Send for approval** (seller): requires a Glass model or hint, entered price ≥ 1, condition, rating for Używana/Outlet, at least one public photo; refusal names the missing fields.
- **Approve** (admin, Pending or Rejected → Active, only with a Glass model) and **reject** (Pending or Active → Rejected, reason required). Bulk approve skips Offers without a Glass model and reports counts.
- In approval the admin opens the catalogue slide-over **prefilled from the hint** and may create a Glass model in place (catalogue 09); it is selected for the Offer.
- **Admin's own Offer:** Active on sending when it has a Glass model, else Pending.
- **Re-approval rule:** a seller's change of entered price, private notes or private photos keeps the status; any other change to an Active, Rejected (or later Withdrawn) Offer → Pending. An admin's edit never changes the status, in any status.
- Record first and last approval dates.
- **Journal** to the owner: approved, rejected (with reason), an admin changed their Offer.
- Pending tab with its counter (the queue); no bell to all admins.

## Done when

- Every transition and refusal above behaves as specified (approve without Glass model, reject without reason, seller price-only edit stays Active, photo edit → Pending).
- Test-first entity (status machine) and handler tests, Livewire tests for the actions; `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** statuses Draft, Pending, Rejected, Active and the §7 rows between them, as the `Offer` entity (RULE-03, RULE-04): `send()` (Draft/Rejected to Pending, or Active for an admin's own Offer with a Glass model, FR-13; FR-06 refusal names the missing fields via `OfferDomainPolicy::missingForSending`), `approve()` (Pending/Rejected to Active, Glass model required, a model chosen while approving replaces the hint, first and last approval dates), `reject()` (reason required), `edit()` (replaces `revise`): the entity compares old and new content and public photos (`OfferDomainPolicy::changeNeedsReapproval`, public photo order included); a seller's edit of an Active or Rejected Offer goes to Pending unless only entered price, private notes or private photos changed; an admin's edit never changes the status; returns whether anything changed. Commands `SendOfferForApproval`, `ApproveOffer` (optional chosen Glass model, FR-10), `ApproveOffers` (bulk, returns approved/skipped counts, RULE-14), `RejectOffer`; `UpdateOffer` now goes through `edit()`. Journal entries (approved, rejected with reason, admin changed the Offer, only when it really changed and never for one's own action) through `OwnerJournal` over Users' `JournalWriter`; `offer` subject resolver registered with `JournalSubjects`. Migration for `first_approved_at`/`last_approved_at` (+ status index), audit labels and value presenters for both, status labels/colours. UI: view page actions "Wyślij do zatwierdzenia" / "Zatwierdź" / "Wybierz model i zatwierdź" (Catalogue slide-over prefilled from the hint, in-place creation comes with it) as the primary, "Odrzuć" (reason form) in "⋮"; same approve/reject in the list row group, bulk "Zatwierdź zaznaczone" with a counts notification; "Oczekujące" tab with counter (admins only, no bell); notice when a seller's save sends the Offer back to Pending; approval dates on the admin's view.
- **Deviations:** (1) `Offer::revise()` is replaced by `edit()` (existing tests moved over), so no caller can edit past the re-approval rule; `arrangePhotos()` stays for the new Draft. (2) Only the owner sends (an admin who is not the owner is refused `not_yours`). (3) A seller's edit that would send an Active/Rejected Offer to Pending must also meet FR-06 (e.g. emptying all public photos is refused); a Rejected Offer is also resent with the send action when only price/private material changed. (4) Rejected admin-owned Offer: send is hidden, the admin approves it. (5) No domain events emitted; Journal entries are written by the handlers through `OwnerJournal`. (6) The rejection reason is not stored on the Offer, only in the owner's Journal entry. (7) Selecting a Glass model while approving drops the hint (RULE-02). (8) `OfferJournalSubject` lives in `Application/Providers` (wiring layer) because deptrac forbids UserInterface from using Users' `JournalSubjectResolver`. (9) `OfferRefused::missingForSending` carries a lazily translated `Stringable` so the domain stays translator-free. (10) Copied the gitignored `public/build` into the worktree for the Vite-dependent tests.
- **Seams left:** other statuses (Reserved, Sold, Withdrawn, Trash) and `edit()`'s "Withdrawn to Pending" row (ticket for withdraw/resume); "Zmiany od ostatniego zatwierdzenia" and the History tab (04, read `last_approved_at`); bulk "Zmień model szyby" (FR-14) can reuse `GlassModelPickerSlideOver` on a bulk action; filters/stat cards (10).
- **Open questions:** none
- **Check:** composer check green (1294 tests)

### Review — 2026-11-21
- **Verdict:** approved after 0 fix rounds
- **Notes:** status machine in `Offer` (`send`, `approve`, `reject`, `edit` replacing `revise`); re-approval decided by `OfferDomainPolicy::changeNeedsReapproval` (public photo order counts). Accepted deviations: only the owner sends; a seller's edit that would send an Active/Rejected Offer to Pending must meet FR-06; the rejection reason lives only in the owner's Journal entry (not on the Offer — §8 lists no reason; flagged to the user); no domain events yet (13 adds them); `OfferJournalSubject` in `Application/Providers` (Wiring layer). Seams: `last_approved_at` for 04; Withdrawn rows for 06.
