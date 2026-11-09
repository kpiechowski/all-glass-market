# A Glass model is a catalogue entry known by several Eurocodes, and Offers refer to it

V1 stored a eurocode as a bare code with no glass parameters and rebuilt a glass's position, colour and equipment each time by copying them from another offer with the same code, while sellers attached "alternative eurocodes" to single offers. The client assigns glass parameters by eurocode, and the data agrees: within one eurocode the position is the same in 99.6% of groups, the colour in 97.5%, the equipment in 93.3%. Yet the eurocode cannot be the key of the catalogue entry: 89% of V1's primary/alternative pairs are the same glass under another vehicle prefix (`8613AGSCHMVZ1L` / `7814AGSCHMVZ1L`). So the catalogue holds Glass models: one Glass model carries the parameters, the vehicle fitment and the OEM codes, and has one or more Eurocodes, exactly one of them main. Each Eurocode belongs to exactly one Glass model and is unique across the catalogue; an OEM code is only a lookup key and may sit on several models. An Offer refers to a Glass model and reads its parameters from it; it never copies or overrides them, so another tint, coating or feature is another Glass model, and piece-specific defects go to the Offer's condition and description.

## Considered options

- **Eurocode as the catalogue entry (one entry per code).** Rejected: twin-model codes would need a separate "same glass" relation, and the client's alternative eurocodes would stay outside the catalogue.
- **Eurocode values as a preset copied onto the Offer.** Rejected: it keeps V1's two sources of truth and its conflicts; a catalogue fix would not reach existing Offers.

## Consequences

- An Offer may exist without a Glass model (the seller leaves an identification hint), but it cannot be approved until an admin assigns one.
- One-way compatibility ("A can replace B") is a future relation between Glass models, never an extra Eurocode.
