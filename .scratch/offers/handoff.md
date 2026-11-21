# Offers delivery: handoff to the next manager

You take over the Offers delivery at issue **10**. Run it with the `module-delivery` skill. `delivery.md` beside this file is the run log, and it is current. This file holds what the log and the skill do not say: the user's working rules, the seams the merged issues left, and a risk note for each remaining issue.

## Resume

1. Read `delivery.md`. Catalogue 09 and Offers 01–09 are merged into branch `catalog`. No worktree is open.
2. Dispatch the remaining issues one at a time in this order: 10 → 11 → 12 → 13 → 14. Each issue's `Blocked by` is met by the issues before it.
3. Before each brief, read the Review notes in the Comments of the issues it builds on. They record the decisions and deviations that were accepted.

## The user's rules

- **One worker at a time.** Dispatch the next issue only after the current one is merged or stopped.
- **Every fix round goes to a fresh `module-worker`.** Brief it with the issue path, its Worker log and the numbered findings. Tell it to keep the uncommitted work and touch nothing else. If a fix worker is stopped mid-work, check `git diff` for what it already did and brief the next one with "finish or verify".
- **Review relentlessly.** On a big issue, read every changed file, not only the diff stat. The user asked for this explicitly, because a worker with a full context does weaker work. Run `composer check` yourself.
- **Stop leftover background tasks.** A worker sometimes hands back while a background shell of its own is still running, and its notification then says "waiting on its own background work". Stop it with `TaskStop`. Also end every brief with "finish any background shell command before you hand back".
- **The user may stop for the day.** Then stop the running worker, write the exact state (what is built, which findings are open, what the next brief says) into `delivery.md` "Next action", and resume from there on the next session.
- **Talk to the user in Polish.** The issues and the code stay in English.
- **The user clicks the panel by hand.** List what needs a hand check, and send no agent to click through the panel.

## Practical notes

- **The test suite takes longer than 300 s.** Run `run.sh <worktree> "COMPOSER_PROCESS_TIMEOUT=0 composer check"`, or composer kills it. Start it in the background with output to a scratchpad file, then read the code while it runs. Do not edit the worktree until it finishes.
- Each worker copies the gitignored `public/build` into its worktree so the Vite-dependent tests run. That is expected; it is not a finding.
- `worktree.sh remove` also deletes the branch.
- Small cosmetic fixes (an injection instead of `app()`, a PHPDoc) the manager may make inline before committing. Re-run the affected tests and note it in the Review.

## Lessons from reviews

Add these to every brief's Focus line:

- Use our variant classes on schema components (`admin-ui-design`). Tailwind utilities belong only in our own Blade views.
- Give every new audited field a readable value presenter. Audit's `ValuePresenter` has `moneyInGrosze()`, `fileCount()`, `boolean()`, `date()`, `enum()`, `reference()` and `resolved(Closure)`.
- Read the Change history through Audit's ModuleApi only. If a read is missing, add the smallest one to Audit, with its own test.
- Hide every action whose entity method would refuse, the Trash included. The visibility helpers live in `OfferActionVisibility` and reuse `OfferDomainPolicy`. Review 06 found approve/reject still shown on trashed Offers.
- Check every status path against §7 and RULE-04, including indirect routes. Review 06 found that a rejected Offer could be withdrawn and then resumed straight to Active.

## Seams the merged issues left

- **Status machine:** the `Offer` entity, with `send`, `approve`, `reject`, `edit`, `withdraw`, `resume`, `reserve`, `release`, `sell`, `returnSale`, `moveToTrash`, `restoreFromTrash`, `changePrice`, `changeGlassModel` and `duplicate`. Every status change goes through the private `moveTo()`. Rules live in `OfferDomainPolicy`.
- **Resume:** governed by the persisted `changed_since_approval` mark. A seller's substantive edit and a rejection set it; approval clears it.
- **Trash:** a `trashed_at` column, not SoftDeletes. A trashed Offer is frozen. Use `OfferQueries::outsideTrash` and `onlyTrash`.
- **"Stopped being Active":** `Offer` sets a flag in `moveTo()` (and in `moveToTrash` while Active). `EloquentOfferRepository::save()` dispatches `OfferStoppedBeingActive(offerId, actor)` inside the command transaction. The listener `OutdateInquiriesWhenOfferStopsBeingActive` reacts to it. This is an accepted deviation from `module-events` ("the handler dispatches"); it is listed under "Open for the user".
- **Reservations and Sales:** their own aggregates (`Reservation`, `Sale`), each with its own repository.
  - `offer_reservations.is_current` is 1 or null, with a unique index per Offer (RULE-05). `made_by_admin` is a snapshot that locks the seller out. `inquiry_id` links a Reservation to the Inquiry it came from.
  - A Sale stores the sale price, entered price, VAT rate (basis points) and buyer's price at sale, plus `sold_at`, the channel (`SaleChannel`) and returned/reason/by.
  - Read relations: `OfferModel::currentReservation`, `OfferModel::sales`.
- **Inquiries:** the `Inquiry` entity and `InquiryState` (Sent, Read, Accepted, Rejected, Outdated). Accept reserves the Offer in the sender's name. The senders' journal is `InquirySenderJournal`.
- **Journal:** `OwnerJournal` writes to Users' `JournalWriter` and never for the actor's own action. Existing kinds: approved, rejected, changed_by_admin, glass_model_changed, withdrawn, deleted, reserved, released, sold, returned, inquiry_sent.
- **Bulk actions:** handlers return `['done' => n, 'skipped' => n]`; approve still uses its 04 keys. The UI reports through `Support/BulkActionResult`. Existing bulk commands: ApproveOffers, ChangePriceOfOffers, ChangeGlassModelOfOffers, WithdrawOffers, DeleteOffers.
- **Buyer's price:**
  - The `BuyersPrices` ModuleApi: `prime(ownerIds)` once per page, then `forOffer`.
  - The rule itself is `BuyersPricePolicy::buyersPriceGrosze`, in integer grosze rounded half up, with the VAT rate in basis points, applied according to the owner's Account type.
  - `OfferBuyersPriceChanged` is raised for priced Offers in **any** status.
- **Catalogue:**
  - Picker: `GlassModelPickerSlideOver::configure(Action|BulkAction, search:, variant:)`, with the chosen id in `$data['glass_model_id']`.
  - A handler always re-checks the id through `GlassModelSummaries`: the model must exist and not be withdrawn.
  - `GlassModelSummaries::of` is still called once per row.
- **History:** `OfferModel::AUDIT_KEY`. Reservation, Sale and Inquiry are their own Audit types and show in the global Change history, not on the Offer's History tab.
- **Photos:** ordered path lists in `public_photos` and `private_photos`. A permanent delete removes the files of both current lists through the `OfferPhotoStorage` port. A duplicate copies no photos.

## Risk note for each remaining issue

- **10 Lists:**
  - Use custom stat cards with an icon, not stock Filament widgets, and load `admin-ui-design`.
  - Sorting or filtering by buyer's price needs a SQL expression that matches `BuyersPricePolicy`, rounding included. Test it against the policy.
  - `GlassModelSummaries::of` per row needs a batch read added to Catalogue's ModuleApi, with its own test.
  - The Glass-model filter must accept a URL parameter, because 11's "N ofert" link targets it.
  - "Sold this month" counts Sales, not returned ones.
  - Every tab except Trash uses `outsideTrash`, and a seller never sees a trashed Offer.
- **11 Catalogue uses:**
  - deptrac must show no dependency from Catalogue to Offers.
  - Offers implements Catalogue's `GlassModelUsage` / `DictionaryValueUsage` contracts and registers them with `GlassModelUsages` / `DictionaryValueUsages`. Read how Catalogue registers `VehicleDictionaryUsage` in `CatalogueModuleServiceProvider`.
  - The contracts sit in Catalogue's `Domain/Contracts`. Check they are on Catalogue's public surface (boundary scanner, `module-layout`), and add them if not.
  - Trashed Offers count as a use, but the "N ofert" count leaves them out.
- **12 Deactivated seller:**
  - A listener on Users' `UserDeactivated` acts as `Actor::system`.
  - §7 allows **Draft → Withdrawn by the System only**. Today `Offer::withdraw()` refuses a Draft, so add a separate, explicit system path; do not loosen the owner's rule.
  - Reserved Offers stay as they are.
  - Inquiries get outdated through the existing reaction, for free.
  - The owner gets **one** Journal entry in total, not one per Offer.
- **13 Announcements:**
  - "Candidate" means Active or Reserved. `OfferStoppedBeingActive` is not "stopped being a candidate", because Active → Reserved keeps the Offer a candidate. Do not reuse it blindly.
  - Decide with `module-events` how to emit the public events. Handlers dispatching them, or the same entity-flag pattern as 08, are both possible. Log the choice and stay consistent.
  - Filter `OfferBuyersPriceChanged` down to candidates.
  - Write no channel code.
- **14 Activity chart:**
  - Load the `dataviz` and `admin-ui-design` skills.
  - "Dodane do sprzedaży" is based on `first_approved_at`. "Sprzedane" counts Sales by `sold_at`, without returned ones. The Trash is excluded.
  - A month with no data shows zeros.

## Open for the user

Bring these up when the user is reachable; do not block on them:

- Should `module-events` name the 08 pattern (the entity flags a fact and the repository dispatches it on save), or should it be reworked into handlers?
- Reservations, Sales and Inquiries show only in the global Change history, not on the Offer's History tab. Is that enough?
- Should the sale date be the moment of the click (today's behaviour), or entered by hand?
- Should the admin picker keep the "with withdrawn" toggle? It is currently kept, off by default.
- The rejection reason is stored only in the owner's Journal entry, not on the Offer. Should it also be stored on the Offer?
- Should a VAT rate change be audited? It is not today.
- Hand checks still owed:
  - the picker slide-over;
  - photo upload and the private photo gallery;
  - 06: withdraw/resume, Trash/restore/delete permanently;
  - 07: reserve/release/sell, the admin-reservation lock, return, the "Rezerwacje" page;
  - 08: send/accept/reject an Inquiry, auto-Outdated, the "Zapytania" page;
  - 09: duplicate, bulk price, bulk Glass model, bulk withdraw/delete.
