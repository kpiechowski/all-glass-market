# 12 - Offers of a deactivated seller

Status: resolved
Blocked by: 08

Spec: FR-35, WF-09, §7 rows to Withdrawn by System. Decisions: D-50, D-66.

## What

- On Users' *User deactivated*: the seller's Active, Pending and Draft Offers → Withdrawn by the System ("seller deactivated"); Reserved stay; open Inquiries on the withdrawn Offers become Outdated; one Journal entry to the owner.
- *User reactivated* restores nothing.

## Done when

- Listener tested with a mix of statuses; `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** FR-35/WF-09: listener `WithdrawOffersWhenSellerIsDeactivated` on `UserDeactivated` sends `WithdrawOffersOfDeactivatedSellerCommand` as `Actor::system('seller deactivated')`; the handler refuses a non-system Actor (AuthorizationException), withdraws Active/Pending/Draft through new `Offer::withdrawBecauseSellerDeactivated()` + `OfferDomainPolicy::canBeWithdrawnBecauseSellerDeactivated()` (`withdraw()` and its policy still refuse Draft, both tested). Reserved, Sold, Rejected, Withdrawn and trashed Offers stay. New `OfferRepository::idsOfOwner()`. FR-34: one Journal entry (`seller_deactivated`, Warning, no subject) with the count, none when nothing was withdrawn. Inquiries on Active Offers outdate via 08's reaction (tested end to end); Pending/Draft have none open. `UserReactivated` is not listened to (tested: restores nothing).
- **Deviations:** the reason "seller deactivated" is the System actor's reason (no column exists for a withdrawal reason; Audit records the actor); Rejected Offers stay as they are (spec lists only Active, Pending, Draft).
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check:module offers green (539 tests)

### Review — 2026-11-21
- **Verdict:** approved after 0 fix rounds. The manager made one change inline: in the Journal text, "robocze" became "szkice", the glossary term for Draft. The handler tests were re-run and are green.
- **Notes:**
  - **Separate System path:** `Offer::withdrawBecauseSellerDeactivated()` and `OfferDomainPolicy::canBeWithdrawnBecauseSellerDeactivated` cover Draft, Pending and Active. The owner's `withdraw()` still refuses a Draft, and both paths are tested.
  - **Handler:** it refuses any non-System Actor. The listener sends the command as `Actor::system('seller deactivated')`.
  - **Journal:** one Warning entry with the count, none when nothing was withdrawn.
  - **Accepted deviations:**
    - The reason "seller deactivated" lives only as the System actor's reason in Audit; there is no reason column.
    - Rejected, Reserved, Sold, Withdrawn and trashed Offers stay as they are.
  - **Inquiries:** outdated through 08's reaction, tested end to end.
  - **Seam:** `OfferRepository::idsOfOwner`.
  - **Check:** `composer check:module offers` green, 539 tests.
