# r03 — local: User journal and user notifications

Question: What are the user journal and user notifications, who/what creates entries, who sees/acts, business meaning, ownership (Users vs Offers)? · V1 commit: 511ce3b

## Summary
Two unrelated things share the "user notification" name.
1. "Mój dziennik" (UserJournal) is a per-user inbox of messages about the user's own offers. It is written only by offer-lifecycle actions (created, activated, accepted, rejected, reserved, sold, archived). The owner only reads, marks as read and deletes entries.
2. UserNotification is an admin-facing activity feed ("Aktywność użytkowników"). Its only writer seen is a user switching account type (private/company). Admins see and delete it.
Ownership view: the journal is addressed to a user but written entirely by offer-lifecycle code and tied to an offer. I judge it an Offers (offer-lifecycle) record that is delivered to the user. UserNotification is a Users-side audit/feed (account-type change).

## Findings

### The journal is created only by offer lifecycle transitions, addressed to the offer's owner
- Tag: confirmed · Impact: high (state change coupled with the write)
- What happens: each `JournalAction` subclass first changes the offer status (`processAction`), then loads the offer owner (`User::find($record->user_id)`). It writes a journal entry for that owner with a fixed title and content plus optional extra text. If the owner is not found, no entry is written. The status change is still applied.
- Evidence: app/Filament/Actions/Abstracts/JournalAction.php:20-33 `__invoke`; app/Models/User.php:156-173 `createJournalEntry`.

### Events that write journal entries (title / type → status effect)
- Tag: confirmed · Impact: high
- "Dodałeś nową ofertę" (Success). OfferCreatedAction only sets a 1-minute cache flag `pending_offer_status_notice_{id}` for non-admins and does not change status.
- "Dodałeś nowy szkic" (Success). DraftOfferCreatedAction sets a similar cache flag `draft_offer_status_notice_{id}`.
- "Aktywowałeś ofertę!" (Success). ActivateFromDraftAction sets status Active if the user is admin, else Pending.
- "Oferta została zaakceptowana!" (Success). OfferAcceptedAction sets status Active.
- "Oferta została odrzucona" (Rejection). OfferRejectedAction sets status Rejected. Content is "Niestety nie możemy opublikować Twojej oferty. Powód odrzucenia: " followed by the admin-entered reason.
- "Rezerwacja Twojej oferty!" (Info). OfferAmountReservedAction sets status Active and clears sold_date.
- "Twoja oferta została zarezerwowana!" (Info). OfferFullyReservedAction sets status Reserved.
- "Sprzedaż Twojej oferty!" (Success). OfferAmountSoldAction sets status Active.
- "Twoja oferta została sprzedana!" (Success). OfferFullySoldAction sets status Sold.
- "Oferta została wycofana" (Info). OfferArchivedAction sets status Archived.
- Evidence: app/Filament/Actions/Journal/*.php, `entryTitle`, `entryContent`, `entryType` and `processAction` in each file.
- Detail: for OfferCreatedAction and DraftOfferCreatedAction I read only the `processAction` body of OfferCreatedAction (lines 25-33). The Draft variant is inferred from the grep of its cache key. The remaining status effects come from grep lines. I did not read the full file bodies of those, so any additional side effects in them (e.g. Woo API calls, since OfferAmountReservedAction has a `$wooApi` property) are unconfirmed.

### Callers (one hop out)
- Tag: confirmed · Impact: normal
- Admin accepts or rejects an offer: AdminTab.php:121,130,149,163 (reject passes `$data['reason']`). Bulk accept: AcceptOffersBulkAction.php:89. Submission review: ViewSubmission.php:483,486. In ViewSubmission:483 the reason is also appended to the Accepted entry.
- Owner activates a draft: MyOffers.php (imports ActivateFromDraftAction).
- Offer creation: OfferCreatedByUserAction.php (imports OfferCreatedAction and DraftOfferCreatedAction).
- Stock sales and reservations: OfferStockChangeAction.php, OfferReservationChangeAction.php. Archive: StockSaleTab.php.
- Evidence: the files listed above (import and call lines from grep). Not exhaustive; r05 covers all callers.

### The owner also gets a Filament database notification with each entry
- Tag: confirmed · Impact: normal
- What happens: after the entry is saved, `notifyNow` sends the owner a database notification. It has the entry title, the entry-type colour, an icon, and a "zobacz" button linking to the journal list. It is sent only if the entry was created.
- Evidence: JournalAction.php:35-52 `sendNotification`.

### Journal entry creation guard
- Tag: confirmed · Impact: normal
- What happens: `createJournalEntry` returns false, with no entry, if `offer_id` is empty or the type is not one of Info/Success/Warning/Rejection. So an entry can't be created without an offer from this method. The DB column is nullable though.
- Evidence: User.php:158-163; migration `offer_id` nullable, `type` enum from `JournalType::toArray()`, `is_read` default false; entries cascade-delete with the user.

### Journal types are only presentation (colour and icon)
- Tag: confirmed · Impact: normal
- Evidence: app/Filament/Enums/JournalType.php Info=primary, Success=success, Warning=warning, Rejection=danger. `Warning` is not used by any journal action seen.

### "Mój dziennik": each user sees only their own entries
- Tag: confirmed · Impact: high (data isolation)
- What happens: the resource query is restricted to `user_id = Auth::id()`, so even admins see only their own. The nav badge shows the user's unread count, coloured with the Pending offer-status colour. Create and edit pages are disabled, since only the `index` page is registered.
- Evidence: UserJournalResource.php:57-66 (badge), :68-72 `getEloquentQuery`, :178-184 `getPages`.

### Owner actions: mark as read, open details, go to the offer, delete
- Tag: confirmed · Impact: normal
- Mark as read: click the eye icon (tooltip "Oznacz jako przeczytaną", or "Wiadomośc odczytana" once read). Opening "Szczegóły" also marks the entry as read, and the modal shows the content as unescaped HTML (`{!! $record->content !!}`). The admin reject reason is embedded in that content. See the defect note below.
- The modal button "Przejdź do oferty" goes to ViewOffer. If the offer no longer exists or has status Deleted, it shows the warning "Brak możliwości zobaczenia oferty" / "Oferta została usunięta lub nie istnieje" and does not navigate. The offer column shows the primary Eurocode, or "Oferta nie istnieje" if the offer is gone.
- Delete ("Usuń", with confirmation) is allowed only for the owner, otherwise 403. A bulk delete is also exposed.
- No filters, no mark-all-read.
- Evidence: UserJournalResource.php:77-176.

### Journal permissions: policy is inert; access comes from Shield permissions
- Tag: inferred · Impact: high
- What happens: UserJournalPolicy is fully commented out. The Shield seed grants the "User" role `view_user::journal` and `delete_user::journal`, and Super Admin all `*_user::journal`. How Filament authorizes without an active policy is not established (r02 scope).
- Evidence: UserJournalPolicy.php (all commented); database/seeders/ShieldSeeder.php:15.

### UserNotification: admin feed of user activity, written on account-type change
- Tag: confirmed · Impact: high (it accompanies a change of account type)
- What happens: when a user changes the account type in the profile, the system saves a UserNotification titled "Uzytkownik {name} zmienił rodzaj konta na: Firmowe|Prywatne" and sends the same text as a database notification (via `notifyNow`, as an info notification) to every admin. It also sets a 30-minute cache lock `company_account_lock_{id}` and calls `OfferUpdateService::handleUsersPublishedOffer`. The last two are outside this scope; I did not follow them.
- Evidence: app/Filament/Pages/ProfilePage.php:112-125; UserNotificationService.php:12-33 (`$sendMessage=false` skips the admin push but still saves the row).
- It is the only caller of `createUserNotification` I found (grep of app/).

### Admins view and delete the activity feed on the user list
- Tag: confirmed · Impact: normal
- What happens: the user list page embeds a table headed "Aktywność użytkowników", ordered newest first. Each row shows time and relative time, the user's name linking to the user view (or "Konto usunięte" if the user_id is empty), and the title. Rows can be deleted singly or in bulk. Empty state: "Brak powiadomień". It shows all users' rows, with no ownership filter. The column label "Dodał" is misleading, since it shows the subject user.
- Evidence: ListUsersNotifications.php:28-95; resources/views/filament/users/list-users.blade.php:4; ListUsers.php:13. Who may open the user list is r02 territory.
- The migration has `user_id` nullable with no FK constraint. That fits the "Konto usunięte" display: rows survive user deletion, unlike journal rows, which cascade. I did not check how the user_id gets cleared.

### OfferNotificationHandler::notifyOfferCreatedByUser is a stub
- Tag: confirmed · Impact: normal
- What happens: it sends a hard-coded English demo notification ("Saved successfully"/"Changes to the post have been saved.", with view/undo buttons that have no URLs) to the current user. No caller was found in grep (only imports in CreateOffer.php and EditOffer.php, and the singleton registration in PanelNotificationProvider.php:18). I did not read those two files for use of the handler.
- Evidence: OfferNotificationHandler.php:21-40.

## Ownership view: Users vs Offers
- Journal: it belongs to Offers (offer lifecycle). Evidence:
  - every writer is an offer status transition class under Actions/Journal;
  - every entry requires `offer_id`;
  - titles and content are all about the offer, and the modal's single next step is "Przejdź do oferty";
  - the "user" aspect is only the recipient (`user_id`), and the cascade delete with the user.
- Reading side (own inbox, unread badge, mark-read, delete) is a user-facing view. It could live in Users as "the user's inbox" but it is built entirely from offer events.
- Suggested cut: the Offers module emits "offer status changed" events carrying the owner and offer. The journal is a projection or listener that stores entries for the owner. That is a design suggestion for the manager (decide), not a V1 fact.
- UserNotification: it belongs to Users (account-type change feed for admins). It has one source only, with no read state and no offer link.

## Contradictions and defects
- Tag: defect? · Journal content is output unescaped (`{!! !!}`) and includes admin free-text reasons (the reject or accept reason is concatenated in). Admin input appears in the user's view. HTML injection risk is low but real if reasons are rendered raw. Evidence: journalEntryModal.blade.php:3; JournalAction.php:31.
- Tag: defect? · In the "Szczegóły" action the label closure checks `$record->status == OfferStatus::Deleted` on the journal record, which has no `status` attribute, so the submit label is likely always "Przejdź do oferty". Evidence: UserJournalResource.php (`modalSubmitActionLabel`).
- Tag: defect? · `createJournalEntry` is called with 5 args but accepts 4, so the extra one is ignored. Also `JournalType::isAllowed($type)` is called with the string value. This works because actions pass `->value`. Evidence: JournalAction.php:31, User.php:156.
- Tag: defect? · The URL `ListUserJournals::getUrl(['resource_id' => ...])` passes a parameter nothing consumes, as far as seen. It just goes to the list. Evidence: JournalAction.php:47.
- Tag: defect? · AdminTab.php imports `Journal\OfferReservedAction`, which does not exist in the Journal folder (only OfferAmountReservedAction and OfferFullyReservedAction). Harmless if unused.
- Tag: ambiguous · The OfferCreated and DraftOfferCreated cache flags (1 minute) have no reader in my scope. Their purpose, probably to suppress or time a "pending" notice, is not established.
- Tag: confirmed · Typos in the UI strings (kept verbatim): "Uzytkownik", "Częśc", "Wiadomośc".

## Leads outside scope
- ProfilePage.php:112-125: the account-type change triggers `OfferUpdateService::handleUsersPublishedOffer`, which changes the user's published offers. This belongs to r01 or the Offers module.
- Offer status machine (Pending/Active/Rejected/Reserved/Sold/Archived/Deleted): driven by the Journal actions. Its owner is Offers.
- Filament database notifications (bell) used for admins and owners: the general notification infrastructure (PanelNotification, Notifier). Not examined.
- Shield permission seeding for `*_user::journal`: r02.
- OfferAmountReservedAction has a `$wooApi` property (WooCommerce). Possible external-system link, not examined.

## Could not establish
- Whether any code ever creates a `Warning` journal entry: none seen. Full-repo grep is r05's.
- Whether the empty UserJournalPolicy is why access depends on Shield or Filament defaults: runtime or r02 would settle it.
- Retention or cleanup of journal entries and notifications: no scheduled jobs searched.
- Whether admin users get journal entries for their own offers: they do, since the owner is the offer's user, whoever the actor is. Whether this is intended is a business question.

## Coverage ledger
- Searched: createJournalEntry, createUserNotification, UserNotificationService, ListUsersNotifications, list-users, UserJournal, OfferNotificationHandler, notifyOfferCreatedByUser, JournalAction, `Journal\` imports, user::journal in app and seeders.
- Examined: app/Models/UserJournal.php, UserNotification.php; both migrations; UserJournalResource.php and its Pages (Create, Edit, List); UserJournalPolicy.php; JournalType.php; ListUsersNotifications.php and its Blade view; resources/views/filament/components/journalEntryModal.blade.php; UserNotificationService.php; User.php (userNotifications, createJournalEntry); OfferNotificationHandler.php; Actions/Abstracts/JournalAction.php; Actions/Journal/OfferRejectedAction.php, OfferAmountReservedAction.php, OfferCreatedAction.php (partial); ProfilePage.php lines 95-135.
- Matched, not examined (full): the other Actions/Journal/*.php files (read by grep for title, content, type and status only); AdminTab.php, AcceptOffersBulkAction.php, ViewSubmission.php, MyOffers.php, OfferCreatedByUserAction.php, OfferEditedByUserAction.php, OfferStockChangeAction.php, OfferReservationChangeAction.php, StockSaleTab.php (callers, grep only, r05); CreateOffer.php, EditOffer.php (import only); PanelNotificationProvider.php, AppServiceProvider.php (registration only); ShieldSeeder.php (grep only).
