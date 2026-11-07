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
        $table = $table
            ->columns(self::columns())
            ->filters(OffersTableFilters::make(), layout: FiltersLayout::AboveContent)
            ->recordUrl(fn (OfferModel $record): string => OfferResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ReserveOfferAction::make()->iconButton()->tooltip(__('offers::resources.offer.actions.reserve.label')),
            ])
            ->toolbarActions([/* bulk actions, each sending a command */])
            ->defaultSort('published_at', 'desc');

        return TableEmptyState::configure(               // both empty states (D-15)
            $table,
            icon: Heroicon::OutlinedTag,
            heading: __('offers::resources.offer.empty.heading'),
            description: __('offers::resources.offer.empty.description'),
            actions: [CreateAction::make()],
        );
    }

    /** Columns stay out of configure(); past ~10 they move to OffersTableColumns (filament-module-wiring). */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()                       // mobile card, hidden from md
                ->title(fn (OfferModel $record): string => $record->title)
                ->subtitle(fn (OfferModel $record): string => $record->vehicle_name)
                ->status(fn (OfferModel $record): OfferStatus => $record->status)
                ->badges(fn (OfferModel $record): array => [$record->category])
                ->meta(fn (OfferModel $record): array => [$record->published_at?->format('d.m.Y')]),
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
            TextColumn::make('price')
                ->label(__('offers::resources.offer.fields.price'))
                ->money('PLN')
                ->alignEnd()
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

`RecordCardColumn`, `PersonColumn` and `TableEmptyState` live in `App\Core\UserInterface\Filament\Tables`. A module writes no card Blade and no card CSS.

## Mobile card

- Every list table declares exactly one `RecordCardColumn::make()`. It hides from `md` by itself; every other column is `->visibleFrom('md')`.
- `title()`: the value of the main column. `subtitle()`: its description (e-mail, vehicle, number).
- `status()`: the one state badge (an enum with `HasLabel` + `HasColor`, or `['label' => …, 'color' => …]`).
- `badges()`: category values, neutral; an enum or a plain string.
- `flags()`: the indicators of the main cell (`['label' => 'Zmiana hasła', 'icon' => Heroicon::OutlinedKey]`, `warning` by default).
- `meta()`: up to two short facts, the date column first ("od 12.03.2024"). Empty values drop out.
- Below `md` the theme hides the head and renders each row as a card; the row click, checkbox and row actions keep working. Never override the table view for this.

## Person cell

- A list of people (users, contacts, sales reps) shows the person as `PersonColumn::make('name')->email(fn ($record) => $record->email)->flags(…)`: initials, name, text flags after the name, e-mail below. Named after the name attribute, it takes `->sortable()` and `->searchable()` like a `TextColumn`.
- Any other title + secondary line is a `TextColumn` with `->description()`.
- An indicator inside a `->description()` cell is an icon after the text with a tooltip (`->icon()->iconPosition(IconPosition::After)->tooltip()`); inside a person cell it is a flag.
## Columns

- 5–6 visible columns at most. Every further column is `->toggleable(isToggledHiddenByDefault: true)`.
- Data that belongs together shares one cell: a person through `PersonColumn`, anything else through `->description()`.
- A column that is empty for most rows does not exist. Its fact becomes an indicator in the main cell: an icon or small badge next to the name.
- Every regular column is `->visibleFrom('md')`; the one `RecordCardColumn` hides from `md` by itself.
- Numbers (counts, money) are `->numeric()` / `->money()` with `->alignEnd()`.
- Dates: `->date()`, or `->since()` with `->dateTooltip()`. Never `->dateTime()` with seconds.
- Category columns use neutral badges; state columns use state colours (SKILL.md, Colour = state).

## Tabs and filters

- The main split (Aktywne / Nieaktywne / Zaproszone) is list tabs, never a filter.
- Up to 3 simple, frequent filters: `FiltersLayout::AboveContent`. More than 3: the default funnel layout, with the frequent ones first.
- Inline filters are selects, dates, toggles or checkboxes; no `->columnSpan()` and no `->filtersFormColumns()` on them.
- Keep the defaults of `TableDefaults` (`app/Filament/Tables`, registered through `ComponentDefaults` in `AdminPanelProvider::boot()`): inline filters apply on change, the panel has "Zastosuj filtry" and is 24 rem wide, the count badge shows only above 0, "Zresetuj filtry" shows only while a filter is active. Do not set `deferFilters()`, `filtersFormWidth()` or `filtersTriggerAction()` without the user's approval.
- A filter without a fixed option list gets `->indicateUsing()`, so its chip under the toolbar names the value.

## Actions

- The row click opens the record (`recordUrl`); View is not a row action.
- One extra row action stands alone as `->iconButton()` with a tooltip. Two or more go into one `ActionGroup`.
- Header: one primary (`CreateAction`), at most two secondary, the rest in "⋮".
- Bulk actions go in `toolbarActions()` and send commands (`filament-module-wiring`).

## Empty state

Every table calls `TableEmptyState::configure()` with its own icon, one-sentence heading, one-sentence description and the primary action when the user may perform it. Under a search or filter it switches by itself to "Brak wyników dla tych filtrów" with "Wyczyść filtry". Never set `emptyState*()` by hand.

## Not like this

The Users list, before the retrofit:

- 7 columns: Imię i nazwisko, E-mail, Typ konta, Rola, Status, Zmiana hasła, Data utworzenia. Name and e-mail belong in one cell.
- "Status" is empty for every active user and "Zmiana hasła" is empty for most rows. Both are indicators next to the name, and the active/inactive split is a list tab.
- "Typ konta" and "Rola" are saturated orange and green badges. Categories are neutral.
- "Data utworzenia" shows `05.11.2026 14:32:07`. Lists show the date, or relative time with the full date in a tooltip.
- View, Edit and Impersonate sit in a row "⋮" while the row itself opens nothing. The row click opens the record; the menu holds the rest.

## Before you finish

- ≤6 visible columns; no column mostly empty; related data shares a cell.
- Every regular column `->visibleFrom('md')`; exactly one `RecordCardColumn` with title, status and the main cell's flags.
- A list of people uses `PersonColumn`; every other two-line cell uses `->description()`.
- No date column shows seconds.
- The main split is tabs; ≤3 inline filters, else the funnel.
- `recordUrl` set; ≤1 standalone row action.
- Empty state through `TableEmptyState::configure()` with icon, sentence and primary action.
- No `deferFilters()`, `filtersFormWidth()` or vendor table view override.
