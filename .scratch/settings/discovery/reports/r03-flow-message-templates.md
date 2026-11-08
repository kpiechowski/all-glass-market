# r03 — flow: MessagesSettings "message templates"

Question: What are the "messages" on MessagesSettings, and does anything in V1 use them? · V1 commit: 511ce3b

## Summary
The page ("Ustawienia powiadomień") stores 6 settings: a title and a body for each of 3 offer events (created, accepted, rejected). **Nothing in V1 reads them. All 6 keys are dead.** The journal entries and notifications that the page's description refers to use texts hard-coded in PHP classes. The page has been a write-only store since the commit that created it. It has no placeholders, no substitution and no fallback.

## Findings
### Six message keys, edited and saved but never consumed
- Tag: confirmed · Impact: normal
- Keys: journal_created_title, journal_created_desc (fieldset "Oferta utworzona"); journal_accepted_title, journal_accepted_desc ("Oferta zaakceptowana"); journal_rejected_title, journal_rejected_desc ("Oferta odrzucona"). Field labels: "Tytuł" (TextInput) and "Treść" (Textarea). The section is "Powiadomienia dziennika", described as 'Treść powiadomień dla zapisów w zakładce "Mój dziennik"'.
- The keys come from a hard-coded array inside journalMessagesForm() (3 types). Each key is built as "{$type}_title" and "{$type}_desc". There is no enum, config or loop beyond that array.
- Evidence: app/Filament/Clusters/Settings/Pages/MessagesSettings.php:62-88 (array and keys), :93-94 (section labels).

### Save behaviour
- Tag: confirmed · Impact: normal
- The "Zapisz" button writes every form field through Setting::setValue (upsert on `key`) and shows the notification "Zapisano". There is no validation, and no placeholder help or hint text.
- Setting::setValue has type `string $value`. An emptied field arrives as null, which would throw a TypeError under non-strict typing only if null coercion fails. In practice, null passed to a string parameter in a non-strict-types file raises a TypeError for user functions. So clearing a field may fail on save. This is inferred from PHP semantics and was not run.
- Tag for the null point: inferred
- The page's mount() calls $this->journalMessagesForm->fill() with no data. The defaults come from ->default(Setting::value(...)). The `create()` method is empty.
- Evidence: MessagesSettings.php:42-49, :107-121; app/Models/Setting.php:setValue (signature `string $value`). The Setting model also uses LogsActivity on key and value, so each save is logged to the activity log (Setting.php).

### Actual journal and notification texts are hard-coded
- Tag: confirmed · Impact: high (this is the place a rewrite would otherwise assume the settings drive)
- Trigger: an admin accepts or rejects an offer (AdminTab actions at lines 121, 130, 149 and 163; the bulk accept in AcceptOffersBulkAction.php:89; and ViewSubmission). The offer owner then gets a user-journal entry plus a database notification (title, an "zobacz" button linking to the journal, and a coloured icon). Created is triggered from OfferCreatedAction.
- Texts are class properties:
  - OfferCreatedAction: "Dodałeś nową ofertę" / "Świetnie! Dodałeś kolejną ofertę! Po sprawdzeniu jej poprawności, trafi do katalogu allGlass". Also sets a 1-minute cache flag pending_offer_status_notice_{id} for non-admins.
  - OfferAcceptedAction: "Oferta została zaakceptowana!" / "Twoja oferta została zweryfikowana i zatwierdzona przez administratora. Nowy status oferty: Aktywna". Sets the offer status to Active.
  - OfferRejectedAction: "Oferta została odrzucona" / "Niestety nie możemy opublikować Twojej oferty. Powód odrzucenia: " with the admin's reason appended ($info). Sets the status to Rejected.
- The only "placeholder" mechanism is concatenation of the rejection reason onto the end of the content. There are no tokens.
- Recipient: User::find($record->user_id). If the user is missing, no entry is made. There are 10 JournalAction subclasses, all with hard-coded texts (stock, reserved, sold, archived, draft and so on). They have no settings counterpart at all.
- Evidence: app/Filament/Actions/Abstracts/JournalAction.php:21-35 (uses $this->entryTitle/$this->entryContent . $info); app/Filament/Actions/Journal/OfferCreatedAction.php:22-24, OfferAcceptedAction.php, OfferRejectedAction.php; app/Models/User.php:156-175 createJournalEntry.

### Consumers were never removed
- Tag: confirmed · Impact: normal
- `git log -S` on journal_created, journal_accepted, journal_rejected and `_desc"` each returns only c28e600 "Journal action" (2024-12-05), the commit that added MessagesSettings.php and, in the same commit, JournalAction. That commit's diff contains no other reference to these keys (grep of the diff for "journal_" and "Setting::" hits only MessagesSettings). `git log -S"Setting::value" -- app/Filament/Actions` returned nothing. So no consumer existed, and none was deleted. The wiring was apparently never finished.
- Tag: confirmed (history); the intent that it was "unfinished" is inferred.

## Contradictions and defects
- defect? The page promises that the text of "Powiadomienia dziennika" is configurable. Saving changes nothing visible to users. Admins who edit it get a success notification "Zapisano" for no effect.

## Leads outside scope
- app/Services/ApiService/Actions/SyncProductToWordpressAction.php imports Setting but has no call to it (grep shows only the use line).
- SettingsSchemaBuilder (fromGroup) is imported as SBuilder in MessagesSettings.php:21 but never used there. It builds one TextInput per key that autosaves on blur and ignores empty values. No other file references it (grep of app, resources, routes, config, database).
- The Setting model logs changes via spatie activitylog.

## Coverage ledger
- Searched (app, resources, routes, config, database, lang, tests; php, blade, js, json): `journal_(created|accepted|rejected)`, `Setting::(value|get|where|all|pluck|query|first)`, `Models\Setting`, `SettingsSchemaBuilder|SBuilder`, `setting(`, `table('settings`, `DB::table(.settings`, `_title'`, `_desc'`, dynamic `{$...}_title`; `pending_offer_status_notice`; `createJournalEntry`; callers of the Offer*Action classes. Also git log -S for the four terms above.
- Examined: MessagesSettings.php, SettingsSchemaBuilder.php, Setting.php, JournalAction.php, OfferCreated/Accepted/RejectedAction.php, JournalType.php, messages-settings.blade.php, User::createJournalEntry, c28e600 diff.
- Matched, not examined: the other 7 Journal/*Action classes (their texts are hard-coded; confirmed only by the grep for `entryTitle`, and the Setting grep shows nothing for them). Also AdminTab/ViewSubmission beyond the call sites, and the WooCommerce description code (no Setting message keys found there).
- Not covered: runtime database content (a production settings table may hold values for these keys, but nothing reads them). A direct DB read outside the code path (for example a raw SQL export or an external system) cannot be ruled out from code alone.
