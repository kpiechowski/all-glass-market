# AllGlass Market

A panel where AllGlass staff and external sellers list used glass for sale, and where AllGlass runs the sale through its shop and marketplaces.

## Users and access

**User**:
A person with an account in the panel. AllGlass staff and external sellers are both Users and differ only by Role.
_Avoid_: account (for the person), customer, member

**Deactivated User**:
A User who can no longer sign in but whose data, history and offers remain. A User is never deleted from the panel.
_Avoid_: deleted user, archived user, banned user

**Role**:
A named set of Permissions held by a User, defined in code by a module. Each User holds exactly one Role.
_Avoid_: group, tier

**Super Admin**:
The Role that passes every Permission check. It can be granted only outside the panel.
_Avoid_: root, owner

**Permission**:
The right to perform one kind of action. Every module declares its own Permissions, and they are granted to Users through Roles.
_Avoid_: ability, gate, policy

**Extra Permission**:
A Permission granted to one User beyond their Role, only by a Super Admin.
_Avoid_: bonus permission, direct permission

**Account type**:
Whether a User sells as Private or Company. A User is Company only after accepting the seller declaration.
_Avoid_: rodzaj konta (in code and docs), company flag

**Seller declaration**:
The User's statement that they are an active VAT payer selling as part of a business. Required for a Company account type.
_Avoid_: oświadczenie (in code and docs), terms

**Journal**:
A User's personal record of things that happened to their affairs, written by any module and read only by that User.
_Avoid_: inbox, dziennik (in code and docs), notifications

**Journal entry**:
One message in a Journal. It has a kind, which names the area it concerns (for example "offer").
_Avoid_: notification, message

## Audit

**Actor**:
Who is behind an action: a User, or the System with a named reason. Work a User started stays theirs even when it finishes in the background.
_Avoid_: causer, author, performer

**Change history**:
The permanent-until-expiry record of changes to the records that modules have registered as auditable. It is the single history for every module.
_Avoid_: activity log, changes log, dziennik zmian (in code and docs), revisions, versions

**Change entry**:
One item in the Change history: a record was created, changed, deleted or brought back, with the old and new value of each changed field or relation, by which Actor and when. It cannot be edited or deleted, only expire.
_Avoid_: log entry, activity, revision

**Restore**:
Setting a record's fields back to the old values held in one Change entry, exactly as recorded and nothing more.
_Avoid_: rollback, revert, przywróć poprzednie (in code and docs)

**Technical log**:
The application's own record of errors, warnings and integration runs, kept in daily files and readable in the panel. It is about the system, not about business records.
_Avoid_: change history, logi (for the change history)

## Glass catalogue

**Glass model** (Model szyby):
One kind of replacement glass in the catalogue, with the parameters every piece of it shares and the vehicles it fits. It is known by one or more Eurocodes. Glazing accessories sold under a Eurocode, such as seals and mouldings, are Glass models too.
_Avoid_: preset, product, glass definition, catalogue glass

**Eurocode** (Eurokod):
A catalogue code of a glass, belonging to exactly one Glass model. Several Eurocodes name the same Glass model when that glass is sold under different codes, for example in twin vehicle models.
_Avoid_: alternative eurocode, eurokod alternatywny, glass code

**Main eurocode** (Eurokod główny):
The Eurocode that names its Glass model wherever the model is shown. Every Glass model has exactly one.
_Avoid_: primary eurocode, priority 0

## Sales channels

**Advert** (Ogłoszenie):
The copy of an offer on one external sales channel, such as the shop or a marketplace. The panel publishes it and keeps it in step; nothing flows back from the channel.
_Avoid_: listing, publication, product (for the channel copy)

## Offers

**Offer** (Oferta):
One physical Glass put up for sale in the panel by a seller, with its price and status. It is the panel's single source of truth for that piece; its Adverts follow it.
_Avoid_: listing, ogłoszenie (for the offer), product, stock item

**Glass** (Szyba):
The physical piece of glass an Offer describes. It exists only inside its Offer.
_Avoid_: piece of glass model, glass model (for the piece), item

**Identification hint** (Wskazówka identyfikacyjna):
What a seller knows about a Glass whose Glass model is not yet known, such as any code, an OEM number or a description of the vehicle. It helps an admin find the Glass model and is not a parameter of the Glass.
_Avoid_: eurocode (for the seller's text), without-eurocode data, informacje o pojeździe

**Approval** (Zatwierdzenie):
An admin's acceptance of a seller's Offer before it may be sold or published. An Offer is approved only once it has a Glass model.
_Avoid_: submission, zgłoszenie, ticket, verification

**Reservation** (Rezerwacja):
A hold on an Offer for a buyer, so that nobody else may buy it until it is released or turned into a Sale.
_Avoid_: booking, partial reservation

**Sale** (Sprzedaż):
The fact that an Offer was sold, at a price, on a date, through a channel. A return ends it and puts the Offer up for sale again.
_Avoid_: order, transaction

**Inquiry** (Zapytanie):
A request to a seller to reserve their Offer for a buyer. Accepting it makes a Reservation.
_Avoid_: zapytanie ofertowe, message, question
