# D — Settings / single object

A standalone `Page` editing one object that is not a row in a list: the user's own profile, module settings, a configuration object. Same field and section rules as B-form; what differs is the page shell.

## Skeleton

```php
/** @property-read Schema $form */
class ShopSettings extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(app(ShopSettingsQueries::class)->current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return ShopSettingsForm::configure($schema)->statePath('data');   // one column of sections
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->getSaveAction()])
                        ->sticky()
                        ->key('form-actions'),
                ]),
        ]);
    }

    protected function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('shop::pages.settings.actions.save'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function save(): void
    {
        // build the command from $this->form->getState() and send it (filament-module-wiring)
        Notification::make()->success()->title(__('shop::pages.settings.saved'))->send();   // "Zapisano zmiany"
    }
}
```

## Rules

- Layout (a): one column of sections. The page has one form and one sticky Zapisz.
- An independent capability with its own save (changing the password next to the profile) is either a header action opening a modal (E), or its own section with a section footer action. Pick one.
- Tabs on a settings page only for 4+ independent groups, with no required field after the first tab. Tab labels carry no icon.
- The sidebar item carries no badge unless it counts something to do.
- Everything else (field pairing, widths, sections, copy) as in B-form.

## Not like this

The Users "Mój profil" page, before the retrofit:

- The sidebar item shows an "i" badge whose meaning is unclear. A sidebar indicator must say plainly that something needs attention ("!" or a warning icon with a tooltip).
- Tab "Zmiana hasła" holds a section "Zmiana hasła". The tab and section label repeat each other.
- Only the password tab has an icon. Tab labels carry none.

## Before you finish

- One form, one sticky save; the success notification is past tense.
- Each independent capability appears once: modal action or section, not both.
- A sidebar badge is a clear attention indicator or a cheap count (SKILL.md, Colour = state).
