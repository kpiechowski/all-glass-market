# C — Record view

One record read-only: the infolist as the first tab, audit history and relation data in the tabs after it. A `ViewRecord` page and the `{X}Infolist` schema.

## Skeleton

```php
class ViewCustomer extends ViewRecord
{
    /** The infolist becomes tab 1; relation managers follow as tabs. */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
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
class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            self::contactSection(),
            self::companySection(),
        ]);
    }

    private static function contactSection(): Section
    {
        return Section::make(__('customers::resources.customer.sections.contact'))
            ->extraAttributes(['class' => 'infolist--sheet'])   // see variants (pending)
            ->schema([
                TextEntry::make('name')->label(…),
                TextEntry::make('email')->label(…)->copyable(),
                TextEntry::make('phone')->label(…)->placeholder('—'),
                TextEntry::make('created_at')->label(…)->date(),
            ]);
    }

    /** Hidden as a whole on a private account. */
    private static function companySection(): Section
    {
        return Section::make(__('customers::resources.customer.sections.company'))
            ->visible(fn (CustomerModel $record): bool => $record->account_type === AccountType::Company)
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([/* … */]);
    }
}
```

## Rules

- Tab 1 is the infolist. Audit history and relations are the following tabs, never sections stacked under the infolist.
- Page tabs are underlined — see variants (pending).
- The infolist is a horizontal sheet: grey fixed-width label left, value right; on mobile the label moves above. One entry per row, so the section stays one column.
- An empty value shows "—" (`->placeholder('—')`). A whole group that does not apply to this record hides as a section.
- Layout (a). Layout (b) when status and metadata deserve a side column: same `Grid` + `columnOrder` as B-form.
- Status is one badge with a state colour; role and type are neutral.
- Header: Edit is the primary action; dangerous actions in "⋮".

## Not like this

The Users record view, before the retrofit:

- "Dane konta" is a 2-column grid mixing role, account type, declaration date, status, name, e-mail, phone, creation date and "Zmiana hasła". A sheet has one entry per row, grouped by meaning.
- "Zmiana hasła" shows a lone warning badge or "-". It is an indicator next to the status, or nothing.
- "Typ konta" and "Rola" are saturated orange and green badges. Neutral.
- The header repeats the edit page's four buttons (Hasło, Edytuj, Wejdź na konto, Dezaktywuj). Edit is primary; the rest go to "⋮".
- Every section heading has an icon. None.

## Before you finish

- `hasCombinedRelationManagerTabsWithContent()` returns true when the resource has relation managers.
- Infolist is one column of sheet sections; every optional entry has `->placeholder('—')`.
- Non-applicable groups hidden as whole sections.
- Header follows the action hierarchy.
