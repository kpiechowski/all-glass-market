# Domain enums implement Filament enum contracts

A domain enum may implement Filament's enum contracts (`HasLabel`, `HasColor`, `HasIcon`, `HasDescription`, and others as needed), call `__()`, and use Core enum traits. Then `Select::options(Enum::class)` and `->badge()` resolve without a presenter class per enum. Filament is the only UI this application will have, so the coupling costs little. These contracts are the only Filament imports allowed in Domain, and deptrac allows exactly them.
