---
name: filament-module-wiring
description: "Filament v5 mechanics inside a module: writes as commands, visibility, reads through {X}Queries, class split, v5 namespaces and translations. Use when writing or changing code under UserInterface/Filament."
---

# Filament module wiring

Everything Filament lives in `{Module}/UserInterface/Filament/`. It describes what the user sees and sends intents. It reads models and `{X}Queries`; it writes only by sending commands.

How a screen looks (template, layout, action placement, columns, colours, copy) is `admin-ui-design`.

## Every write sends a command

Filament writes to the database by itself unless redirected. Redirect every path: page handler, header action, table action, bulk action, relation manager.

```php
class CreateOffer extends CreateRecord
{
    protected function handleRecordCreation(array $data): Model
    {
        $id = CommandSender::send(new CreateOfferCommand(
            actor: CurrentActor::get(),
            vehicleId: $data['vehicle_id'],
            price: $data['price'],
        ));

        return OfferModel::findOrFail($id);
    }
}
```

`EditRecord::handleRecordUpdate(Model $record, array $data)` sends the update command and returns `$record->refresh()`. Actions:

```php
DeleteAction::make()
    ->using(fn (OfferModel $record) => CommandSender::send(
        new DeleteOfferCommand(CurrentActor::get(), $record->id)
    ));
```

- `Core\UserInterface\CommandSender::send()` puts the command on the bus. When the handler throws a `DomainException`, it shows the translated message as a danger notification and halts, so the form stays open with its data.
- `Core\UserInterface\CurrentActor::get()` returns `Actor::user()` for the logged-in user. The UI builds no other actor.
- **Relations and media are command data.** A `BelongsTo` select puts the FK in `$data`. A many-relation field uses `->options(...)` and its array of ids goes into the command. `->relationship()` is used only to load options or display, never to save. Uploaded files travel as paths in the command.

## Visibility

Show or hide with `visible()`, combining the permission and the domain rule:

```php
->visible(fn (OfferModel $record): bool =>
    CurrentActor::can(OfferPermission::Activate)
    && OfferDomainPolicy::canBeActivated($record->status, $record->price))
```

The policy is the same pure method the entity calls (`module-cqrs`). Visibility is convenience; the handler checks again. Laravel Policies are not used.

## Reading

```php
->query(fn (OfferQueries $offers): Builder => $offers->activeForSeller(CurrentActor::get()))
```

Tables, resources (`getEloquentQuery()`) and widgets start from a named `{X}Queries` method; Filament layers its sorting, search and filters on top. Tabs use one `{X}Queries` method each.

## Split by structure

```
UserInterface/Filament/Resources/Offers/
  OfferResource.php        model, navigation, pages — configuration only
  Pages/                   ListOffers, CreateOffer, EditOffer, ViewOffer
  Schemas/                 OfferForm, OfferInfolist, form sections
  Tables/                  OffersTable, OffersTableColumns, OffersTableFilters
  Actions/                 ReserveOfferAction …
```

| Piece | Rule |
|---|---|
| `{X}Table::configure()` | query, default sort, pagination, toggles, record and bulk actions |
| Columns | never inline in `configure()`: a private `columns(): array` method of `{X}Table`; a separate `{X}TableColumns::make(): array` when the list passes ~10 columns (card and toggleable ones count) or carries composite-column logic |
| Filters | always their own class `{X}TableFilters::make(): array` |
| Form, infolist | always their own class: `{X}Form::configure()`, `{X}Infolist::configure()` |
| Form section | own class when it has more than ~8 fields or is used in two places |
| Action | own class in `Actions/` (`{Verb}{X}Action::make()`) when it has a modal schema or a chain longer than ~5 calls; a bare `DeleteAction::make()->using(...)` stays inline |
| Standalone page | flat file when simple; a directory `Pages/{Page}/` with `Schemas/` when it has extracted schemas |

Inside a table, form or infolist class, split content into **private methods**, one per section or small single-use component, so `configure()` reads as an outline:

```php
public static function configure(Schema $schema): Schema
{
    return $schema->components([
        self::pricingSection(),
        self::vehicleSection(),
    ]);
}
```

Widgets go in `Filament/Widgets/`, clusters in `Filament/Clusters/`, relation managers in `Filament/RelationManagers/`. `DiscoverModuleFilament` registers everything under `UserInterface/Filament/`; the panel provider lists nothing by hand except sidebar entries (`admin-ui-design`, Navigation).

## Enums carry their own presentation

A domain enum shown in Filament implements `HasLabel` / `HasColor` / `HasIcon` / `HasDescription` and may use Core enum traits (ADR 0003). `TextColumn::make('status')->badge()` and `Select::make('status')->options(OfferStatus::class)` then need nothing more. Search existing enums before adding one.

## Translations

Every string comes from `UserInterface/resources/lang/pl/` under the module namespace (snake_case module name):

| File | Holds | Key example |
|---|---|---|
| `resources.php` | resources and everything under them; top key = resource | `offers::resources.offer.fields.price` |
| `pages.php` | standalone pages | `users::pages.edit_profile.title` |
| `enums.php` | enum labels and descriptions | `offers::enums.offer_status.active` |
| `exceptions.php` | domain exception messages | `offers::exceptions.cannot_be_activated` |
| `mail.php`, `notifications.php` | mails and notifications | `offers::notifications.reserved.title` |

Generic words ("Save", "Active") come from Filament's own translations or `core::common`.

## v5 namespaces

| Component | Namespace |
|---|---|
| Form fields (`TextInput`, `Select`, `Repeater`) | `Filament\Forms\Components\` |
| Infolist entries (`TextEntry`, `IconEntry`) | `Filament\Infolists\Components\` |
| Layout (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`) | `Filament\Schemas\Components\` |
| `Get`, `Set` | `Filament\Schemas\Components\Utilities\` |
| Table columns | `Filament\Tables\Columns\` |
| Table filters | `Filament\Tables\Filters\` |
| **All** actions | `Filament\Actions\` |
| Icons | `Filament\Support\Icons\Heroicon` |

Signatures to preserve: `$navigationIcon` is `string|BackedEnum|null`, `$navigationGroup` is `string|UnitEnum|null`, `$view` on `Page`/`Widget` is non-static `protected string`. `Grid`, `Section`, `Fieldset` and `Repeater` span one column unless given `->columnSpanFull()`. File uploads are private unless `->visibility('public')`.

Use `search-docs` for any API detail. Generate files with `dartisan make:filament-resource … --no-interaction`, then move them into the structure above.

## Before you finish

- Every create, update, delete and custom action sends a command through `CommandSender`.
- No field saves a relation or media by itself.
- Every `visible()` on a guarded action uses `CurrentActor::can()` and, where a rule applies, the domain policy.
- Every class respects the split table, and `configure()` reads as an outline.
- Every user-facing string is a translation key in the right file.
- The `admin-ui-design` checklist holds.
- The resource has its Livewire test for create, edit and delete (`module-testing`).
