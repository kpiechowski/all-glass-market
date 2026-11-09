# The panel only publishes to sales channels and never pulls from them

V1 pushed every offer save to WooCommerce and also imported from it: products became offers, and sold or reserved products became sales and reservations under a default owner, so the truth about a piece was split between the panel and the shop. In V2 the panel is the single source of truth for every Offer. Each external channel (the WooCommerce shop, OLX, any future one) holds only an Advert, a copy the panel publishes and keeps in step, and nothing flows back: a sale made on a channel is recorded by an admin in the panel, and every Advert of that Offer then follows. Adverts, channel ids and mappings belong to the integrations, never to the domain.

## Consequences

- No integration may create or change an Offer, a Reservation or a Sale. A channel that reports orders automatically would need a new decision that keeps the panel the owner of the status.
