# 06 - Withdraw, resume, delete, Trash, restore, delete permanently

Status: resolved
Blocked by: 03

Spec: FR-24, FR-25, FR-26, §7 rows Withdrawn and Trash, FR-34 (withdrawn or deleted by an admin), RULE-06. Decisions: D-30, D-42, D-43, D-67.

## What

- Status **Withdrawn**: owner or admin withdraws a Pending, Rejected or Active Offer. "Wznów sprzedaż" → Active when approved before and unchanged since except entered price and private material; an admin's own Offer resumes whenever it has a Glass model; otherwise editing sends it to Pending (03's rule extended to Withdrawn).
- **Delete** (admin only, any status except Reserved — the guard is completed in 07): the Offer goes to the **Trash**, keeps its status, leaves every list except the admin's Trash tab. **Restore** returns it with its status.
- **Delete permanently**, only from the Trash: removes the Offer with its photos and everything belonging to it (07 and 08 add Reservations, Sales, Inquiries to the cascade).
- Journal to the owner when an admin withdraws or deletes their Offer.

## Done when

- Transitions and refusals tested (resume of a changed Offer refused; seller delete refused; permanent delete outside the Trash refused).
- `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** FR-24: status Withdrawn; `Offer::withdraw()` (Pending/Rejected/Active) and `Offer::resume()` ("Wznów sprzedaż": Active when approved before and not changed since; an admin owner's Offer whenever it has a Glass model, like sending; the approval dates stay); `Offer::edit()` extended to Withdrawn through the same `changeNeedsReapproval` rule; a seller's substantive edit sets the persisted mark `changed_since_approval` (cleared on approval), which is what "unchanged since" means (price, private notes and private photos never set it, an admin edit never does). FR-25/FR-26: Trash as `trashed_at` (not a status, status kept), `moveToTrash` / `restoreFromTrash` / `ensureCanBeDeletedPermanently`; an Offer in the Trash is frozen (send, approve, reject, withdraw, resume, edit refuse with `is_in_trash`). Rules in `OfferDomainPolicy` (`canBeWithdrawn`, `canBeResumed`, `resumesWithoutApproval`, `canBeTrashed`, `canBeRestored`, `canBeDeletedPermanently`), used by entity and UI. Commands `WithdrawOffer` (owner or admin), `ResumeOffer`, `DeleteOffer`, `RestoreOffer`, `DeleteOfferPermanently` (admins; removes the row and both photo lists through the new port `OfferPhotoStorage`). FR-34: Journal entries through `OwnerJournal` for withdraw and delete (never for one's own action). Migration `trashed_at` + `changed_since_approval`; both audited with labels and presenters (`date`, `boolean`). UI: view-page actions "Wznów sprzedaż" / "Przywróć" (primary where they apply), "Wycofaj", "Usuń", "Usuń trwale" in "⋮"; same actions in the list row group; admin tab "Kosz", "Wszystkie" and "Oczekujące" leave the Trash out, a seller never sees a trashed Offer, the Edit action and page are closed for an Offer in the Trash; "W koszu od" on the admin's view.
- **Deviations:** (1) "Unchanged since the last approval" is a persisted mark on the Offer, not a comparison with Change history: Audit holds readable lines, not values the domain could compare, and the mark reuses `changeNeedsReapproval`. A change that is reverted still counts as changed (conservative). (2) A seller's edit of a Withdrawn Offer that cannot be resumed (never approved, or marked changed) goes to Pending on any change, even price only; otherwise such an Offer would have no way back (§7 "Withdrawn to Pending after editing"). (3) Draft cannot be withdrawn by the owner (FR-24 lists Pending/Rejected/Active; Draft is the System's, FR-35 in 12). (4) The Reserved refusal of delete/withdraw (RULE-06) is not built: Reserved does not exist yet; `OfferDomainPolicy::canBeTrashed` takes the status for it and 07 completes it. (5) The Trash uses a plain `trashed_at` column and not Laravel SoftDeletes, so repositories and the Audit observer need no global scope. (6) No domain events (13 adds them). (7) Bulk withdraw/delete are 09; Journal entries for resume/restore are not written (FR-34 lists none). (8) Copied the gitignored `public/build` into the worktree for the Styleguide tests.
- **Seams left:** 07 adds Reserved to the delete/withdraw guard and its Reservations/Sales to the permanent-delete cascade (`DeleteOfferPermanentlyCommandHandler`); 08 adds Inquiries and closing them on withdraw/delete (FR-23); 09 bulk withdraw/delete; 10 uses `OfferQueries::outsideTrash/onlyTrash` and the Withdrawn tab; 11 counts Offers outside the Trash (`trashed_at`); 12 uses `withdraw`; 13 announces withdrawn/deleted/resumed/restored.
- **Open questions:** none
- **Check:** composer check green (1395 tests)

### Worker — fix 1
- **Built:** Finding 1 (RULE-04): `Offer::reject()` marks the Offer changed since approval, so Active, rejected, withdrawn does not resume; the rationale now sits in the method's PHPDoc, not an inline comment. Entity, resume handler and view-page tests cover it (including a seller's edit sending it to Pending and an admin owner's own Offer resuming, FR-13); `OfferActionVisibility::canResume` reads the same `changed_since_approval` mark, so it agrees. Finding 2: `ApproveOffersCommandHandler` already skips Offers in the Trash (tested, counted as skipped), so the bulk action needed no change; added a Filament test that approve, reject and approve-with-Glass-model are hidden on a trashed Pending Offer in the Trash tab row (the view-page test already existed).
- **Deviations:** none
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green (1400 tests)

### Review — 2026-11-22
- **Verdict:** approved after 1 fix round. Round 1 fixed two problems: a rejected Offer that the seller withdrew could be resumed straight to Active, bypassing the rejection (RULE-04); and approve, reject and send were still offered on Offers in the Trash. The round was split across two workers because the user stopped the first one at the end of the day.
- **Notes:**
  - **"Unchanged since approval":** a persisted mark (`changed_since_approval`), not a read of the Change history. A seller's substantive edit sets it, and so does a rejection. Approval clears it. An admin's edit never sets it.
  - **Withdrawn and not resumable:** any seller edit sends it to Pending.
  - **Trash:** a plain `trashed_at` column, not SoftDeletes. A trashed Offer is frozen in the entity, hidden from sellers, and skipped by bulk approve.
  - **Permanent delete:** removes the files of both current photo lists through the `OfferPhotoStorage` port. Files removed earlier stay orphaned on disk.
  - **Seams:** 07 adds the Reserved guard (delete and withdraw, RULE-06) and its cascade in `DeleteOfferPermanentlyCommandHandler`. 08 closes Inquiries. 09: a duplicate must copy the photo **files**, not share their paths, or a permanent delete of the original removes the copy's photos. 10 uses `OfferQueries::outsideTrash` / `onlyTrash`.
