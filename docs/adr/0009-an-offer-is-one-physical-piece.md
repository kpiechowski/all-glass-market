# An Offer is one physical piece, with no quantity and no separate stock item

V1 gave the offer a quantity and derived its stock and status from reservation and sale rows, but in production 2030 of 2046 offers had quantity 1, every sale was one piece and no partial reservation or sale ever happened, while the derived status was wrong in several paths. So in V2 an Offer describes exactly one physical Glass: there is no quantity, identical pieces are separate Offers (duplicate), and a Reservation or a Sale applies to the whole Offer. The 16 V1 offers with a larger quantity are split by the migration.

We also chose not to separate the physical piece (a stock item a seller keeps in the panel) from its Offer (the piece put up for sale), although the trade separates them: in V1 the piece never existed outside its offer (the draft status has no rows, no piece was relisted), and keeping one record keeps the admin's V1 flow. A Glass therefore exists only inside its Offer.

## Consequences

- Revisit this when workshops or sellers need to keep unlisted stock in the panel, or one piece must be listed more than once. The split is mechanical: each Offer becomes one stock item plus one Offer.
