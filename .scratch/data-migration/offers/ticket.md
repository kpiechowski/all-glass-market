# Offers — migrate V1 data

Status: needs-triage
Blocked by: Offers delivery (issues in `.scratch/offers/issues/`, not cut yet); the Catalogue import (`.scratch/data-migration/catalogue/ticket.md`); the Users import

Spec: `.scratch/data-migration/offers/spec.md`. Functional spec: `.scratch/offers/functional-spec.md` §8. Decisions: D-02, D-20, D-29, D-46, D-49, D-59–D-68 in `.scratch/offers/discovery/discovery.md`.

## What

A repeatable import of the V1 offers into V2, as an Artisan command:

- **Offers** with owner, number, condition, rating, year, description, private notes, entered price, status and Trash per the spec's mapping; Glass model from the Catalogue hand-over, otherwise an identification hint.
- **Split** of offers whose V1 quantity is > 1 into one Offer per piece, with copied photos (after the client review, `zweryfikwoania_klientem.md` §9).
- **Photos:** public and private, main photo first, public beyond 10 moved to private.
- **Sales** with channel, prices, VAT rate and returns; a Sale for the Sold offers without one.
- **Reservations:** current and ended; orphans dropped.
- A **run report**: every default applied, the open V1 Inquiries to resend, the counts of spec §5, no personal data.

## Done when

- The command is idempotent (keys in spec §5) and tested against a fixture of V1 rows covering: an offer with quantity 3, a deleted offer, an Active offer without eurocode, a returned sale, a WooCommerce sale, a Sold offer without a sale row, a current admin reservation, an orphan reservation, an offer with 12 public photos, a Nowa offer with a rating, a malformed year.
- A dry run on the current V1 dump produces the counts of spec §4–§5 and the run report.

Status is `needs-triage`: the source connection, the run procedure and the client's answer to the review item are not settled yet.
