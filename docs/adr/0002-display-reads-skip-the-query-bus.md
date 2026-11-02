# Display reads skip the query bus

Filament tables need an Eloquent `Builder` so that sorting, search and filters keep working. A query handler would have to return one, which makes it a pass-through. So Filament reads straight from named methods on a concrete `{X}Queries` class in `Infrastructure/Persistence/Queries/` and from read-only models. This is the one place where UserInterface imports Infrastructure. Writes never take that path. A read that feeds a decision or a calculation still goes through a query handler, so the logic can be tested outside the UI.
