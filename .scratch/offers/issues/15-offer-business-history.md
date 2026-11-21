# 15 - Business history of an Offer (bonus)

Status: needs-info
Blocked by: —

Bonus, outside the agreed spec. Raised by the user on 2026-11-21 while reviewing the open questions after issues 10–14. **Grill the user before triage**: nothing here is decided yet.

## Why

Today an Offer's business events live in different places:
- Reservations, Sales and Inquiries are their own Audit types. They show only in the global Change history, not on the Offer's History tab (review of 07/08).
- The rejection reason is stored only in the owner's Journal entry, not on the Offer (review of 03).

An admin who wants to follow an Offer's course has to piece it together.

## Idea (user's words, to be refined)

- A separate section or slide-over on the Offer that shows its **business log**: approved, rejected (with reason), reserved and released, sold and returned, Inquiries, withdrawn and resumed, Trash. It is meant for an admin and reads more clearly than the field-level Change history.
- The **rejection reason** belongs in it. That probably needs its own storage (a table of business events, or a column/table for rejections). Decide during the grilling.

## Questions for the grilling

- Who sees it: the admin only, or the owner too?
- What does it list, and which entries carry a note or reason?
- Is it built from existing data (Audit, Reservations, Sales, Inquiries, Journal), or written as its own record when each fact happens?
- Is it a new table, or a projection over the existing ones? What about the history from before it exists?
- Where does it sit: a tab, a slide-over from the header, or a section on the view page?
- How does it relate to the History tab ("Zmiany od ostatniego zatwierdzenia")?

## Decisions already given (user, 2026-11-21)

- Sale date stays the moment of the click.
- A VAT rate change is not audited.
