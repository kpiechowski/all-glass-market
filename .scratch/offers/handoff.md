# Offers delivery: handoff to the next manager

You take over the Offers delivery at issue **06**. Run it with the `module-delivery` skill. `delivery.md` beside this file is the run log, and it is current. This file holds what the log and the skill do not say: the user's working rules, the seams the merged issues left, and a risk note for each remaining issue.

## Resume

1. Read `delivery.md`. Catalogue 09 and Offers 01–05 are merged into branch `catalog`. No worktree is open.
2. Dispatch the remaining issues one at a time in this order: 06 → 07 → 08 → 09 → 10 → 11 → 12 → 13 → 14. Each issue's `Blocked by` is met by the issues before it.
3. Before each brief, read the Review notes in the Comments of the issues it builds on. They record the decisions and deviations that were accepted.

## The user's rules

- **One worker at a time.** Dispatch the next issue only after the current one is merged or stopped.
- **Every fix round goes to a fresh `module-worker`.** Brief it with the issue path, its Worker log and the numbered findings. Tell it to keep the uncommitted work and touch nothing else.
- **Review relentlessly.** On a big issue, read every changed file, not only the diff stat. The user asked for this explicitly, because a worker with a full context does weaker work. Run `composer check` yourself.
- **Stop leftover background tasks.** A worker sometimes hands back while a background shell of its own is still running, and its notification then says "waiting on its own background work". Stop it with `TaskStop`. Also end every brief with "finish any background shell command before you hand back".
- **Talk to the user in Polish.** The issues and the code stay in English.
- **The user clicks the panel by hand.** List what needs a hand check, and send no agent to click through the panel.

## Lessons from reviews

Add these to every brief's Focus line:

- Use our variant classes on schema components (`admin-ui-design`). Tailwind utilities belong only in our own Blade views.
- Give every new audited field a readable value presenter. Audit's `ValuePresenter` now has `moneyInGrosze()`, `fileCount()` and `resolved(Closure)`.
- Read the Change history through Audit's ModuleApi only. If a read is missing, add the smallest one to Audit, with its own test.

Each worker copies the gitignored `public/build` into its worktree so the Vite-dependent tests run. That is expected; it is not a finding.

## Seams the merged issues left

- **Statuses:** the `Offer` entity is the status machine, with `send`, `approve`, `reject` and `edit`. `edit` holds the re-approval rule through `OfferDomainPolicy::changeNeedsReapproval`. Issue 06 adds the Withdrawn rows; extend `edit` and the policy, never bypass them. `RULE-04`: a status moves only along the §7 transitions.
- **Journal:** `Application/Services/OwnerJournal` writes to Users' `JournalWriter`. It never writes an entry for the actor's own action. Add new kinds there, such as withdrawn, deleted, reservation and inquiry.
- **Photos:** `OfferPhotos` holds ordered path lists in the json columns `public_photos` and `private_photos`. Removed files are still on disk. A permanent delete (06, FR-26) must delete the files of both lists.
- **Buyer's price:** `BuyersPrices` ModuleApi (`prime(ownerIds)`, then `forOffer`) and `BuyersPricePolicy`. A Sale (07) records the entered price, `VatRateSetting::current()` and the buyer's price at that moment.
- **Events:** `OfferBuyersPriceChanged` is raised for priced Offers in **any** status. Issue 13 filters it to the candidates (Active and Reserved).
- **Catalogue:** the picker is `GlassModelPickerSlideOver::configure(Action|BulkAction, search:, variant:)`, and the chosen id arrives in `$data['glass_model_id']`. A handler always checks the id again through `GlassModelSummaries`: it must exist and not be withdrawn.
- **Lists:** `GlassModelSummaries::of` is called once per row. Issue 10 needs a batch read.
- **History:** `OfferModel::AUDIT_KEY`. The "Zmiany od ostatniego zatwierdzenia" block uses `RecordChangesAfter::lastChangeOf(..., 'last_approved_at', ['status'])`.

## Risk note for each remaining issue

- **06 Withdraw/Trash:** "Wznów sprzedaż" may go straight to Active only when the Offer was approved before and is unchanged since, apart from entered price and private material. Compare against the content at `last_approved_at`; the Change history or the `edit` rule can tell what changed. The Trash is not a status: use a soft delete that keeps the status.
- **07 Reservations and Sales:** each step is indivisible. A Reservation made by an admin locks the seller out. A Reserved Offer refuses edit, withdraw and delete, so complete 06's guards.
- **08 Inquiries:** "an Offer stops being Active by any route" means its open Inquiries become Outdated. Implement it once, as a reaction to the status change, not in each handler.
- **09 Duplicate and bulk actions:** the copy's owner is whoever duplicates it, an admin included. The user decided this; V1 got it wrong. Bulk actions report done and skipped counts (RULE-14).
- **10 Lists:** custom stat cards with an icon, not stock Filament widgets. Load `admin-ui-design`. Sorting or filtering by buyer's price needs a SQL expression.
- **11 Catalogue uses:** deptrac must show no dependency from Catalogue to Offers. Offers registers with Catalogue's `GlassModelUsages` and `DictionaryValueUsages`. Read how Catalogue's existing usages (Vehicle) are registered.
- **12 Deactivated seller:** a listener on Users' *User deactivated*, acting as `Actor::system`.
- **13 Announcements:** public Offers events plus the advert-summary read, with no channel code.
- **14 Activity chart:** load the `dataviz` and `admin-ui-design` skills.

## Open for the user

Bring these up when the user is reachable; do not block on them:

- Should the admin picker keep the "with withdrawn" toggle? It is currently kept, off by default.
- The rejection reason is stored only in the owner's Journal entry, not on the Offer. Should it also be stored on the Offer?
- Should a VAT rate change be audited? It is not today.
- Hand checks still owed: the picker slide-over, photo upload and the private photo gallery.
