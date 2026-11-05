# A — List

A table of records: list tabs for the main split, filters, row click into the record, bulk actions. Lives on a `ListRecords` page or a standalone page with a table.

## Skeleton

```php
class ListOffers extends ListRecords
{
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];   // the one primary action
    }

    /** The main split of the data. Each tab narrows through one {X}Queries method. */
    public function getTabs(): array
    {
        return [
            'active' => Tab::make(__('offers::resources.offer.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->onlyActive($query)),
            'archived' => Tab::make(__('offers::resources.offer.tabs.archived'))
                ->modifyQueryUsing(fn (Builder $query): Builder => app(OfferQueries::class)->onlyArchived($query)),
        ];
    }
}
```

```php
class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(OffersTableFilters::make(), layout: FiltersLayout::AboveContent)
            ->recordUrl(fn (OfferModel $record): string => OfferResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ReserveOfferAction::make()->iconButton()->tooltip(__('offers::resources.offer.actions.reserve.label')),
            ])
            ->toolbarActions([/* bulk actions, each sending a command */])
            ->emptyStateIcon(Heroicon::OutlinedTag)
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered() || $table->getLivewire()->hasTableSearch()
                ? __('offers::resources.offer.empty.filtered')
                : __('offers::resources.offer.empty.heading'))
            ->emptyStateDescription(__('offers::resources.offer.empty.description'))
            ->emptyStateActions([CreateAction::make()])
            ->defaultSort('published_at', 'desc');
    }

    /** Columns stay out of configure(); past ~10 they move to OffersTableColumns (filament-module-wiring). */
    private static function columns(): array
    {
        return [
            ViewColumn::make('card')                       // mobile card
                ->view('offers::filament.tables.offer-card')
                ->hiddenFrom('md'),
            TextColumn::make('title')
                ->label(__('offers::resources.offer.fields.title'))
                ->description(fn (OfferModel $record): string => $record->vehicle_name)
                ->searchable()
                ->sortable()
                ->visibleFrom('md'),
            TextColumn::make('status')
                ->label(__('offers::resources.offer.fields.status'))
                ->badge()                                   // enum carries a state colour
                ->visibleFrom('md'),
            TextColumn::make('published_at')
                ->label(__('offers::resources.offer.fields.published_at'))
                ->date()
                ->sortable()
                ->visibleFrom('md'),
            TextColumn::make('updated_at')
                ->label(__('offers::resources.offer.fields.updated_at'))
                ->since()
                ->dateTooltip('d.m.Y H:i')
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),
        ];
    }
}
```

The card view is our own Blade under `UserInterface/resources/views/filament/tables/`; Tailwind utilities are allowed there. The theme hides the table header and row lines on mobile — see variants (pending).

## Columns

- 5–6 visible columns at most. Every further column is `->toggleable(isToggledHiddenByDefault: true)`.
- Data that belongs together shares one cell: name + email through `->description()`; a composite `ViewColumn` when that is not enough.
- A column that is empty for most rows does not exist. Its fact becomes an indicator in the main cell: an icon or small badge next to the name.
- Every regular column is `->visibleFrom('md')`; the `card` column is `->hiddenFrom('md')`.
- Dates: `->date()`, or `->since()` with `->dateTooltip()`. Never `->dateTime()` with seconds.
- Category columns use neutral badges; state columns use state colours (SKILL.md, Colour = state).

## Tabs and filters

- The main split (Aktywne / Nieaktywne / Zaproszone) is list tabs, never a filter.
- Up to 3 simple, frequent filters: `FiltersLayout::AboveContent`. More than 3: the default funnel layout, with the frequent ones first.
- Keep Filament's native active-filter count; it shows only above 0.

## Actions

- The row click opens the record (`recordUrl`); View is not a row action.
- One extra row action stands alone as `->iconButton()` with a tooltip. Two or more go into one `ActionGroup`.
- Header: one primary (`CreateAction`), at most two secondary, the rest in "⋮".
- Bulk actions go in `toolbarActions()` and send commands (`filament-module-wiring`).

## Empty state

Every table has its own: icon, one-sentence heading, and the primary action when the user may perform it. An empty result under a search or filter says so instead ("Brak wyników dla tych filtrów"), as in the skeleton.

## Not like this

The Users list, before the retrofit:

- 7 columns: Imię i nazwisko, E-mail, Typ konta, Rola, Status, Zmiana hasła, Data utworzenia. Name and e-mail belong in one cell.
- "Status" is empty for every active user and "Zmiana hasła" is empty for most rows. Both are indicators next to the name, and the active/inactive split is a list tab.
- "Typ konta" and "Rola" are saturated orange and green badges. Categories are neutral.
- "Data utworzenia" shows `05.11.2026 14:32:07`. Lists show the date, or relative time with the full date in a tooltip.
- View, Edit and Impersonate sit in a row "⋮" while the row itself opens nothing. The row click opens the record; the menu holds the rest.

## Before you finish

- ≤6 visible columns; no column mostly empty; related data shares a cell.
- Every regular column `->visibleFrom('md')`, one card column `->hiddenFrom('md')`.
- No date column shows seconds.
- The main split is tabs; ≤3 inline filters, else the funnel.
- `recordUrl` set; ≤1 standalone row action.
- Own empty state with icon, sentence and primary action.
