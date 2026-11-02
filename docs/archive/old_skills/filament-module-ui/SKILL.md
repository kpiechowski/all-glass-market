---
name: filament-module-ui
description: "Filament v5 UI inside a module: resources, pages, tables, schemas, filters, actions, widgets, relation managers and translations. Use when building or changing anything under UserInterface/Filament — including how to split schemas into their own classes, how every CRUD path must dispatch a command, and the correct v5 namespaces and property signatures."
---

# Filament module UI

Everything Filament lives in `{Module}/UserInterface/Filament/`. It is presentation only: it describes what the user sees and dispatches intents. It holds no business logic.

For where UI sits among the layers, see `module-layout`. For commands, `module-cqrs`.

## Every CRUD path goes through a command

This is the rule that breaks most often, because Filament writes to the database by itself unless you stop it.

Resource pages override the record handlers:

```php
class CreateUser extends CreateRecord
{
    use HasCommandBus;

    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->commandBus->send(new CreateUserCommand(
            actorId: Auth::id(),
            name: $data['name'],
            // …
        ));
    }
}
```

`EditRecord` overrides `handleRecordUpdate(Model $record, array $data)` the same way.

**Actions need the same treatment, and this is the part that gets forgotten.** A `DeleteAction`, a bulk action, or a custom modal action writes directly unless you redirect it:

```php
DeleteAction::make()
    ->using(fn (Model $record) => $this->commandBus->send(
        new DeleteUserCommand(actorId: Auth::id(), id: $record->getKey())
    ));

Action::make('reserve')
    ->schema([...])
    ->action(fn (array $data, Offer $record) => $this->commandBus->send(
        new ReserveOfferCommand($record->id, $data['until'])
    ));
```

Every path that creates, updates or deletes — page handler, header action, table action, bulk action, relation manager — dispatches a command. No `->save()`, `->update()`, `->delete()` or `->create()` on a model from a Filament class, ever.

**Reads are the relaxed case.** A table or infolist may read through a repository directly, because that boundary was settled deliberately in `module-persistence`. Writes have no such exception.

## Split by structure, one class per concern

A `Resource` directory holds one subdirectory per kind of thing, all at the same level:

```
UserInterface/Filament/Resources/Offers/
  OfferResource.php          model, navigation, pages — no logic
  Pages/                     ListOffers, CreateOffer, EditOffer, ViewOffer
  Schemas/                   forms, infolists, filter sets
  Tables/                    table definitions
  Actions/                   custom actions with their own configuration
```

**Every schema-shaped structure is its own class.** A `Table` class already carries the query, pagination, default sort and toggles — don't also bury columns and filters in it. Filament's `configure()` convention keeps each piece separate:

```php
class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (OfferRepository $repository) => $repository->visibleForPanel())
            ->columns(OfferTableSchema::columns())
            ->filters(OfferTableFilters::schema())
            ->defaultSort('created_at', 'desc');
    }
}
```

The same applies to forms and infolists: `OfferForm::configure()`, `OfferInfolist::configure()`. When a form grows sections that stand alone, each section becomes its own class returning a component array.

**Custom actions get their own class** whenever they carry more than a default. An action with a modal schema, custom label, colour, icon, visibility and confirmation does not belong inline in a table definition:

```php
class ReserveOfferAction
{
    public static function make(): Action
    {
        return Action::make('reserve')
            ->label(__('offers::resource.actions.reserve'))
            ->icon(Heroicon::CalendarDays)
            ->schema(ReserveOfferForm::schema())
            ->action(/* dispatch command */);
    }
}
```

Plain `DeleteAction::make()` and `ViewAction::make()` stay inline. The threshold is a chain of configuration, not the mere existence of an action.

### Why this matters

Separate classes make schemas selectable at runtime. Showing a different form or table per role becomes a choice between two classes rather than a conditional threaded through one:

```php
->columns(Auth::user()->isAdmin()
    ? AdminOfferTableSchema::columns()
    : CustomerOfferTableSchema::columns())
```

It also keeps schemas reusable across a resource, a standalone page and a relation manager without duplication.

### Pages outside a resource

A simple standalone page is a flat file. A page with extracted schemas becomes a directory:

```
Pages/
  Dashboard.php                    simple, self-contained
  EditProfile/
    EditProfile.php
    Schemas/
      ProfileForm.php
      PasswordForm.php
```

Widgets go in `Filament/Widgets/`, clusters in `Filament/Clusters/`, relation managers in `Filament/RelationManagers/`.

Auto-discovery via `DiscoverModuleFilament` on `AdminPanelProvider` registers anything under `UserInterface/Filament/`. Never register resources manually in the panel provider.

## Enums carry their own presentation

A domain enum shown in Filament implements the relevant contracts instead of being formatted at each call site:

```php
enum OfferStatus: string implements HasColor, HasLabel, HasIcon
{
    case Active = 'active';

    public function getLabel(): string { return __('offers::resource.statuses.'.$this->value); }
    public function getColor(): string|array|null { return 'success'; }
}
```

`TextColumn::make('status')->badge()` and `Select::options(OfferStatus::class)` then resolve everything automatically. Never write `formatStateUsing()` or `color(fn ...)` for something an enum can own.

This is a documented trade-off — Filament contracts are the only UI imports allowed in Domain (`module-layout`). Search existing enums before adding one for a similar concept.

## Translations

Every module keeps `UserInterface/resources/lang/pl/resource.php`, loaded under the snake_case module namespace: `__('offers::resource.actions.reserve')`. No user-facing string is hardcoded in a schema, action or column.

## v5 namespaces

Wrong namespaces are the most common Filament error, and they are not guessable from surrounding code:

| Component | Namespace |
|---|---|
| Form fields (`TextInput`, `Select`, `Repeater`) | `Filament\Forms\Components\` |
| Infolist entries (`TextEntry`, `IconEntry`) | `Filament\Infolists\Components\` |
| Layout (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`) | `Filament\Schemas\Components\` |
| Schema utilities (`Get`, `Set`) | `Filament\Schemas\Components\Utilities\` |
| Table columns (`TextColumn`, `IconColumn`) | `Filament\Tables\Columns\` |
| Table filters (`SelectFilter`, `Filter`) | `Filament\Tables\Filters\` |
| **All** actions (`DeleteAction`, `CreateAction`, `Action`) | `Filament\Actions\` |
| Icons | `Filament\Support\Icons\Heroicon` |

There is no `Filament\Tables\Actions\` or `Filament\Forms\Actions\` in v5. Actions come from one namespace.

Use `search-docs` for API detail rather than guessing — it returns version-correct results for the installed Filament.

## Signatures and defaults that trip people up

Overridden properties have union types that must be preserved:

- `$navigationIcon`: `protected static string | BackedEnum | null`
- `$navigationGroup`: `protected static string | UnitEnum | null`
- `$view`: `protected string` — not `static` — on `Page` and `Widget`

Other reliable mistakes:

- `Grid`, `Section`, `Fieldset` and `Repeater` **do not span all columns by default** — set `->columnSpanFull()` or `->columnSpan()`.
- File uploads are **private by default**; add `->visibility('public')` when public access is needed.
- `Repeater` uses `->schema()`, not `->fields()`.
- Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo. There is no `BelongsToSelect`.
- Never add `->dehydrated(false)` to a field that must be saved — it strips the value before the action runs.

Create files with Filament's own Artisan commands (`dartisan make:filament-resource …`), always with `--no-interaction`, then move them into the module structure above.

## Migration status

- **`EditUser` has a bare `DeleteAction::make()`** while `DeleteUserCommand` exists — a delete that skips the bus. It needs `->using()`. Treat it as a bug, not a pattern.
- **Users and Audit predate the split-class convention.** `UserForm`, `UsersTable` and `UserInfolist` exist, but columns and filters are not split into separate schema classes. New work follows this skill.
- **No `Actions/` directories exist yet**; actions are currently inline.
