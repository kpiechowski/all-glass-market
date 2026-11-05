# B — Form

Create or edit one record: `CreateRecord` / `EditRecord` pages and the `{X}Form` schema they share.

## Skeleton — layout (a), the default

```php
class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::contactSection(),
            self::companyAddressSection(),
            self::pickupAddressSection(),
            self::notesSection(),
        ]);
    }

    private static function contactSection(): Section
    {
        return Section::make(__('customers::resources.customer.sections.contact'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('first_name')->label(…)->required()->columnSpan(['default' => 'full', 'md' => 6]),
                TextInput::make('last_name')->label(…)->required()->columnSpan(['default' => 'full', 'md' => 6]),
                TextInput::make('email')->label(…)->email()->required()->columnSpan(['default' => 'full', 'md' => 8]),
                TextInput::make('phone')->label(…)->tel()->columnSpan(['default' => 'full', 'md' => 4]),
            ]);
    }

    /** One address, one section. A second address is its own section below. */
    private static function companyAddressSection(): Section
    {
        return Section::make(__('customers::resources.customer.sections.company_address'))
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('company_street')->label(…)->columnSpanFull(),
                TextInput::make('company_postcode')->label(…)->placeholder('00-000')->columnSpan(['default' => 'full', 'md' => 3]),
                TextInput::make('company_city')->label(…)->columnSpan(['default' => 'full', 'md' => 9]),
            ]);
    }

    /** The section's only field is named like the section, so its label is hidden. */
    private static function notesSection(): Section
    {
        return Section::make(__('customers::resources.customer.sections.notes'))
            ->schema([
                RichEditor::make('notes')->label(__('customers::resources.customer.sections.notes'))->hiddenLabel(),
            ]);
    }
}
```

`->label(…)` stands for the translation key (`filament-module-wiring`, Translations).

## Skeleton — layout (b), main + side

Use it only when the record has status or metadata worth keeping in view. The side column holds read-only facts (`TextEntry`) or that status, never ordinary inputs.

```php
/** Layout (b): status, role and dates stay in view while editing. */
public static function configure(Schema $schema): Schema
{
    return $schema->components([
        Grid::make(['default' => 1, 'lg' => 3])->schema([
            Group::make([self::contactSection(), self::companyAddressSection()])
                ->columnSpan(['default' => 'full', 'lg' => 2])
                ->columnOrder(['default' => 2, 'lg' => 1]),
            Group::make([self::statusSection()])
                ->columnSpan(['default' => 'full', 'lg' => 1])
                ->columnOrder(['default' => 1, 'lg' => 2]),   // above the main column on mobile
        ]),
    ]);
}
```

## Page

```php
class EditCustomer extends EditRecord
{
    public static bool $formActionsAreSticky = true;   // Zapisz + Anuluj stay at the bottom

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),                          // secondary (grey)
            ActionGroup::make([
                ResetPasswordAction::make(),
                DeactivateCustomerAction::make(),        // red menu item; full red only in its modal
            ]),
        ];
    }
}
```

The handler wiring (`handleRecordCreation`, `handleRecordUpdate`) is `filament-module-wiring`.

## Rules

- One field per row unless both are short and a pair: First + Last name, Postcode + City.
- Width follows the data on a 12-column grid: postcode 3, phone 4, NIP 4, e-mail 8, street full.
- Placeholder only as a format example; the label always sits above.
- `helperText` one sentence; more goes into `Section::description()`.
- A field that applies only after another choice appears with `->live()` + `->visible()`.
- Tabs inside a form only for 4+ independent groups, with no required field after the first tab. Otherwise stacked sections.
- A section collapses only when it holds rarely used, optional settings.
- A capability served by an action (password reset, deactivation) has no section, and the reverse.

## Not like this

The Users edit form, before the retrofit:

- The schema keeps the resource's default 2-column grid and no section spans full width, so "Dane konta", "Dane firmy", "Wysyłka" and "Hasło" render as a grid of boxes side by side. Forms are layout (a): `$schema->columns(1)`.
- Company address, NIP, pickup address, city and postcode fill one 2-column grid, so the company address and the pickup address interleave. Each address is its own section.
- Section "Dodatkowe uprawnienia" holds one field labelled "Dodatkowe uprawnienia"; section "Wysyłka" holds a field labelled "Wysyłka". The field label is hidden.
- Every section heading has an icon (user, key, building, truck, lock). Headings carry none.
- "Hasło" is a header action group and also a collapsed section at the bottom. One place only.
- Header actions Hasło, Podgląd, Wejdź na konto, Dezaktywuj as four buttons, Dezaktywuj in full red. One primary, ≤2 secondary, the rest and every dangerous action in "⋮".

## Before you finish

- Layout (a), or (b) with its reason in the docblock and no inputs in the side column.
- Every row pair is semantic; every short field spans only what it needs.
- Every address, contact or group is one section; nothing interleaves.
- No section icon; no single field repeating its section heading.
- Sticky footer; header follows the action hierarchy.
