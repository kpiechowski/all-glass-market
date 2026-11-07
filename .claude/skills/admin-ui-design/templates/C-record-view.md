# C — Record view

One record read-only: the infolist as the first tab, audit history and relation data in the tabs after it. A `ViewRecord` page and the `{X}Infolist` schema.

## Skeleton

```php
class ViewCustomer extends ViewRecord
{
    /** The infolist becomes tab 1; relation managers follow as tabs. No lone tab when the user sees none. */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return $this->getCachedRelationManagers() !== [];
    }

    public function getContentTabLabel(): ?string
    {
        return __('customers::resources.customer.tabs.details');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),                          // primary
            ActionGroup::make([
                ImpersonateCustomerAction::make(),
                DeactivateCustomerAction::make(),        // red menu item
            ]),
        ];
    }
}
```

```php
// CustomerResource
public static function getRelations(): array
{
    return [
        RecordHistoryRelationManager::make(['recordType' => 'customers.customer']),   // Audit
        OrdersRelationManager::class,
    ];
}
```

Relation managers load lazily by default; leave `$isLazyLoaded` alone.

```php
/** Layout (b): status, type, role and dates stay in view beside the data. */
class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::identityHeader(),
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Group::make([self::companySection(), self::notesSection()])
                    ->columnSpan(['default' => 'full', 'lg' => 2])
                    ->columnOrder(['default' => 2, 'lg' => 1]),
                Group::make([self::accountSection()])
                    ->columnSpan(['default' => 'full', 'lg' => 1])
                    ->columnOrder(['default' => 1, 'lg' => 2]),   // above the main column on mobile
            ]),
        ]);
    }

    /** Avatar, name and the main contact; hidden labels stay for screen readers. */
    private static function identityHeader(): Section
    {
        return Section::make()
            ->extraAttributes(['class' => 'infolist--identity'])
            ->schema([
                Flex::make([
                    ImageEntry::make('avatar')->label(…)->hiddenLabel()->circular()->imageSize(56)->grow(false),
                    Group::make([
                        TextEntry::make('name')->label(…)->hiddenLabel()->size(TextSize::Large)->weight(FontWeight::Bold),
                        TextEntry::make('email')->label(…)->hiddenLabel()->copyable(),
                    ]),
                ])->verticallyAlignCenter(),
            ]);
    }

    /** Side column: status and metadata, one sheet column. */
    private static function accountSection(): Section
    {
        return Section::make(__('customers::resources.customer.sections.account'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('status')->label(…)->badge(),
                TextEntry::make('account_type')->label(…)->badge()->color('gray'),
                self::booleanEntry('email_verified', …),
                TextEntry::make('created_at')->label(…)->date('d.m.Y'),
            ]);
    }

    /** Short values paired by meaning, two sheet columns from xl; long values span the row. */
    private static function companySection(): Section
    {
        return Section::make(__('customers::resources.customer.sections.company'))
            ->visible(fn (CustomerModel $record): bool => $record->account_type === AccountType::Company)
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->columns(['default' => 1, 'xl' => 2])
            ->schema([
                TextEntry::make('company_name')->label(…),
                TextEntry::make('nip')->label(…)->copyable(),
                TextEntry::make('invoice_email')->label(…)->url(fn (string $state): string => 'mailto:'.$state),
                TextEntry::make('phone')->label(…)->placeholder('—'),
                TextEntry::make('address')->label(…)->placeholder('—')->columnSpanFull(),
            ]);
    }

    /** "Tak" / "Nie" with an icon: green check, grey cross (red only when "no" is a problem). */
    private static function booleanEntry(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->formatStateUsing(fn (bool $state): string => __($state ? 'filament-infolists::components.entries.icon.true' : 'filament-infolists::components.entries.icon.false'))
            ->icon(fn (bool $state): Heroicon => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
            ->iconColor(fn (bool $state): string => $state ? 'success' : 'gray');
    }
}
```

## Rules

- Tab 1 is the infolist. Audit history and relations are the following tabs, never sections stacked under the infolist.
- Filament builds the record tabs and the theme underlines them; set nothing on them. `tabs--underline` goes only on Tabs you build ([variants](../variants.md)).
- An identity header opens the record: avatar, name and main contact in one `infolist--identity` section, never a separate "Zdjęcie profilowe" entry.
- A record without an image shows name and main contact only, with no placeholder. A second contact (phone) sits under the main one.
- The infolist is a horizontal sheet (`infolist--sheet`): grey fixed-width label left, value right, a hairline between rows; on mobile the label moves above. The theme does this; never set `inlineLabel()`.
- A sheet section with short values runs two sheet columns from xl: `->columns(['default' => 1, 'xl' => 2])`, neighbours paired by meaning (name + NIP, e-mail + phone). Long values (address, notes, lists, images, repeatable, key-value) take `->columnSpanFull()`. A section of only long values stays one column.
- A boolean is a `TextEntry` showing "Tak" / "Nie" with an icon (`booleanEntry()` above), never an `IconEntry` alone.
- An empty value shows "—" (`->placeholder('—')`). A whole group that does not apply to this record hides as a section.
- Layout (b) when the record has status or metadata (status, type, role, confirmations, dates, author): data in the main column, those in the side column, which drops above the main one on mobile. Otherwise layout (a). The (b) grid may hold only the top sections: long sections at the bottom (repeatables, key-value tables, long lists) may follow it full width, under both columns.
- Status is one badge with a state colour; role and type are neutral.
- An attention indicator ("Wymaga zmiany hasła") joins the status entry as a second badge: `->state()` returns a list, `->color()` maps each item (`warning`).
- Header: Edit is the primary action; dangerous actions in "⋮".

## Not like this

The Users record view, before the retrofit:

- "Dane konta" is a 2-column grid mixing role, account type, declaration date, status, name, e-mail, phone, creation date and "Zmiana hasła". Status, type, role and dates belong in the side column; the sheet pairs the remaining data by meaning.
- "Zmiana hasła" shows a lone warning badge or "-". It is an indicator next to the status, or nothing.
- "Typ konta" and "Rola" are saturated orange and green badges. Neutral.
- The header repeats the edit page's four buttons (Hasło, Edytuj, Wejdź na konto, Dezaktywuj). Edit is primary; the rest go to "⋮".
- Every section heading has an icon. None.

## Before you finish

- `hasCombinedRelationManagerTabsWithContent()` returns true when the user sees a relation manager.
- An identity header opens the record; layout (b) whenever status or metadata exist.
- Every label–value section is a sheet (repeatable lists and metric grids may stay plain); short-value sections have `['default' => 1, 'xl' => 2]` columns with paired entries, and long values span the full width.
- Every boolean shows "Tak" / "Nie" with an icon; every optional entry has `->placeholder('—')`.
- Non-applicable groups hidden as whole sections.
- Header follows the action hierarchy.
