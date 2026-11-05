# E — Action modal

A short task launched from a button: a confirmation, or a form of up to ~5 fields. Lives in `Actions/{Verb}{X}Action.php` (`filament-module-wiring`, class split).

## Skeleton — short form

```php
final class ReserveOfferAction
{
    public static function make(): Action
    {
        return Action::make('reserve')
            ->label(__('offers::resources.offer.actions.reserve.label'))          // "Zarezerwuj"
            ->modalHeading(__('offers::resources.offer.actions.reserve.heading'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.reserve.submit'))
            ->schema([
                Select::make('buyer_id')->label(…)->options(…)->required(),
                DatePicker::make('until')->label(…)->required(),
            ])
            ->visible(…)                                                          // filament-module-wiring, Visibility
            ->action(function (OfferModel $record, array $data): void {
                // send the command (filament-module-wiring)
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.reserve.done'));   // "Zarezerwowano ofertę"
    }
}
```

## Skeleton — dangerous confirmation

```php
Action::make('deactivate')
    ->label(__('customers::resources.customer.actions.deactivate.label'))       // "Dezaktywuj"
    ->color('danger')                                                           // red text inside "⋮"
    ->requiresConfirmation()
    ->modalHeading(__('customers::resources.customer.actions.deactivate.heading'))
    ->modalDescription(__('customers::resources.customer.actions.deactivate.description'))
    ->modalSubmitActionLabel(__('customers::resources.customer.actions.deactivate.label'))
    ->action(…);
```

The action sits inside the page's `ActionGroup`, never as a header button. The red submit button in the modal is the only full red on the screen.

## Skeleton — longer task beside a list

```php
Action::make('editPricing')
    ->slideOver()
    ->schema([/* 6+ fields */]);
```

Use a slide-over when the user needs the list underneath while filling it. Without that need, a long form gets its own page (B-form).

## Rules

- Up to ~5 fields or a confirmation. More fields: own page, or a slide-over.
- Inside the modal, the field and section rules of B-form apply; one column by default.
- The heading names the task; the description of a dangerous action states plainly what will happen ("Użytkownik nie zaloguje się, dopóki go nie aktywujesz").
- The submit button names the act: "Dezaktywuj", "Zarezerwuj". Never "Tak", "OK", "Potwierdź".
- Success notification: past tense, short.
- A domain error keeps the modal open with the data (`CommandSender`, `filament-module-wiring`).

## Not like this

- Users "Dezaktywuj" is a full red button in the page header next to three others. It belongs in "⋮" as a red menu item; only its confirmation button is full red.
- Users "Hasło" opens a button dropdown in the header while the edit form also has a collapsed "Hasło" section. One capability, one place.

## Before you finish

- ≤5 fields, or a slide-over / own page with the reason clear.
- Submit label is the verb of the act.
- Dangerous description says what will happen.
- Notification past tense.
