<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages;

use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\DeactivateUserDemoAction;
use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\DemoNotification;
use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\EditPricingDemoAction;
use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\InviteUserDemoAction;
use App\Core\UserInterface\Filament\Pages\Styleguide\Schemas\StyleguideButtons;
use App\Core\UserInterface\Filament\Pages\Styleguide\Schemas\StyleguideForm;
use App\Core\UserInterface\Filament\Pages\Styleguide\Schemas\StyleguideInfolist;
use App\Core\UserInterface\Filament\Pages\Styleguide\Schemas\StyleguideLayoutForm;
use App\Core\UserInterface\Filament\Pages\Styleguide\Schemas\StyleguideSettingsForm;
use App\Core\UserInterface\Filament\Pages\Styleguide\Schemas\StyleguideTabs;
use App\Core\UserInterface\Filament\Pages\Styleguide\Tables\DemoUsers;
use App\Core\UserInterface\Filament\Pages\Styleguide\Tables\StyleguideUsersTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Styleguide (D-33, D-34, D-43): every main component on one page, in underlined page tabs by
 * component area, built from the admin-ui-design templates (B form, C record view, A list,
 * E action modals). It is the reference for them and a regression check.
 *
 * - Registered in AdminPanelProvider only when `app()->isLocal()`; elsewhere its route does not exist.
 * - Static data only: the table reads DemoUsers through `records()`, the forms keep page state.
 *   Nothing is persisted, so no action builds a command: each one only sends a notification.
 *   The forms validate and never call `getState()`, so uploads are never stored.
 * - Demo copy is hard-coded Polish: the one exception to "every string from translations" (D-33).
 *
 * @property-read Schema $form
 * @property-read Schema $settingsForm
 * @property-read Schema $layoutForm
 * @property-read Schema $infolist
 */
class Styleguide extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $slug = 'styleguide';

    protected static ?int $navigationSort = 1000;

    protected string $view = 'core::filament.pages.styleguide';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $settings = [];

    /** @var array<string, mixed>|null */
    public ?array $layoutDemo = [];

    public string $listTab = 'active';

    public bool $showsEmptyTable = false;

    public bool $showsFilterPanel = false;

    public static function getNavigationLabel(): string
    {
        return 'Styleguide';
    }

    public function getTitle(): string
    {
        return 'Styleguide';
    }

    public function getSubheading(): string
    {
        return 'Wzorzec wszystkich komponentów panelu. Tylko lokalnie; niczego nie zapisuje.';
    }

    public function mount(): void
    {
        $this->form->fill(StyleguideForm::demoState());
        $this->settingsForm->fill(StyleguideSettingsForm::demoState());
        $this->layoutForm->fill(StyleguideLayoutForm::demoState());

        /* Shows the error state on first load; Zapisz then validates for real. */
        $this->addError('data.company_nip', StyleguideForm::nipError());
    }

    public function form(Schema $schema): Schema
    {
        return StyleguideForm::configure($schema);
    }

    public function settingsForm(Schema $schema): Schema
    {
        return StyleguideSettingsForm::configure($schema);
    }

    public function layoutForm(Schema $schema): Schema
    {
        return StyleguideLayoutForm::configure($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return StyleguideInfolist::configure($schema);
    }

    /**
     * D-43: underlined page tabs by component area, so a component is compared without scrolling
     * the whole page. Every panel stays rendered, so switching tabs keeps form state; the active
     * tab is kept in the query string (`?obszar=`) across reloads.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Obszary komponentów')
                ->contained(false)
                ->persistTabInQueryString('obszar')
                ->tabs([
                    Tab::make('Formularze')->key('formularze')->schema($this->formsTab()),
                    Tab::make('Sekcje i układ')->key('sekcje')->schema($this->layoutTab()),
                    Tab::make('Karta rekordu')->key('karta-rekordu')->schema($this->recordTab()),
                    Tab::make('Tabele i filtry')->key('tabele')->schema($this->tableTab()),
                    Tab::make('Akcje i modale')->key('akcje')->schema($this->actionsTab()),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return StyleguideUsersTable::configure($table)
            ->records(fn (?string $search, ?array $filters, ?string $sortColumn, ?string $sortDirection, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->showsEmptyTable
                ? new LengthAwarePaginator([], total: 0, perPage: (int) $recordsPerPage ?: 5, currentPage: 1)
                : DemoUsers::page($this->listTab, $search, $filters, $sortColumn, $sortDirection, $page, $recordsPerPage))
            ->filtersLayout(fn (): FiltersLayout => $this->showsFilterPanel ? FiltersLayout::Dropdown : FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('toggleEmptyState')
                    ->label(fn (): string => $this->showsEmptyTable ? 'Pokaż rekordy' : 'Pokaż pusty stan')
                    ->color('gray')
                    ->action(function (): void {
                        $this->showsEmptyTable = ! $this->showsEmptyTable;
                        $this->refreshTable();
                    }),
                Action::make('toggleFiltersLayout')
                    ->label(fn (): string => $this->showsFilterPanel ? 'Filtry nad tabelą' : 'Filtry w panelu')
                    ->color('gray')
                    ->action(function (): void {
                        $this->showsFilterPanel = ! $this->showsFilterPanel;
                        $this->refreshTable();
                    }),
            ]);
    }

    /** @return array<string, string> */
    public function listTabs(): array
    {
        return [
            'active' => 'Aktywni',
            'inactive' => 'Nieaktywni',
            'invited' => 'Zaproszeni',
            'all' => 'Wszyscy',
        ];
    }

    public function setListTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->listTabs())) {
            return;
        }

        $this->listTab = $tab;
        $this->refreshTable();
    }

    public function save(): void
    {
        $this->form->validate();

        DemoNotification::success('Zapisano zmiany');
    }

    public function saveSettings(): void
    {
        $this->settingsForm->validate();

        DemoNotification::success('Zapisano ustawienia');
    }

    public function cancel(): void
    {
        $this->resetErrorBag();
        $this->form->fill(StyleguideForm::demoState());
    }

    /** One capability, one place: header holds 1 primary, 2 secondary, the rest in "⋮" (danger last). */
    protected function getHeaderActions(): array
    {
        return [
            InviteUserDemoAction::make(),
            EditPricingDemoAction::make(),
            Action::make('export')
                ->label('Eksportuj')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn () => DemoNotification::success('Wyeksportowano listę')),
            ActionGroup::make([
                Action::make('duplicate')
                    ->label('Duplikuj')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(fn () => DemoNotification::success('Zduplikowano')),
                Action::make('archive')
                    ->label('Archiwizuj')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->action(fn () => DemoNotification::success('Zarchiwizowano')),
                DeactivateUserDemoAction::make(),
            ]),
        ];
    }

    private function refreshTable(): void
    {
        $this->flushCachedTableRecords();
        $this->resetPage();
    }

    /** @return list<Component> */
    private function formsTab(): array
    {
        return [
            ...self::blockHeading('Formularz', 'Szablon B, układ (a): sekcje jedna pod drugą, siatka 12 kolumn, przyklejona stopka.'),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Zapisz')->submit('save'),
                        Action::make('cancel')->label('Anuluj')->color('gray')->action(fn () => $this->cancel()),
                    ])->sticky()->key('form-actions'),
                ]),
        ];
    }

    /** @return list<Component> */
    private function layoutTab(): array
    {
        return [
            ...self::blockHeading('Sekcje', 'Sekcja z akcją w nagłówku, sekcja zagnieżdżona z grupą pól i akcją w stopce, sekcja zwinięta.'),
            EmbeddedSchema::make('layoutForm'),
            ...self::blockHeading('Zakładki w formularzu', 'Tylko dla 4+ niezależnych grup, bez pól wymaganych poza pierwszą zakładką.'),
            Form::make([EmbeddedSchema::make('settingsForm')])
                ->id('settingsForm')
                ->livewireSubmitHandler('saveSettings')
                ->footer([
                    Actions::make([
                        Action::make('saveSettings')->label('Zapisz ustawienia')->submit('saveSettings'),
                    ])->key('settings-form-actions'),
                ]),
        ];
    }

    /** @return list<Component> */
    private function recordTab(): array
    {
        return [
            ...self::blockHeading('Karta rekordu', 'Szablon C: zakładki rekordu, w pierwszej arkusz, jeden wpis w wierszu, „—” dla pustych wartości.'),
            StyleguideTabs::make(EmbeddedSchema::make('infolist')),
        ];
    }

    /** @return list<Component> */
    private function tableTab(): array
    {
        return [
            ...self::blockHeading('Lista', 'Szablon A: zakładki listy, filtry, karta na telefonie, akcje wiersza i masowe, pusty stan.'),
            View::make('core::filament.pages.styleguide.list-tabs'),
            EmbeddedTable::make(),
        ];
    }

    /** @return list<Component> */
    private function actionsTab(): array
    {
        return [
            ...self::blockHeading('Przyciski', 'Główny, szary, link, ikona; czerwony wypełniony tylko jako przycisk potwierdzenia.'),
            StyleguideButtons::kinds(),
            StyleguideButtons::sizes(),
            ...self::blockHeading('Modale i powiadomienia', 'Szablon E: modal, panel boczny, niebezpieczne potwierdzenie, powiadomienia.'),
            Actions::make([
                InviteUserDemoAction::make('demoModal')->color('gray'),
                EditPricingDemoAction::make('demoSlideOver'),
                Action::make('demoNotification')
                    ->label('Pokaż powiadomienie')
                    ->icon(Heroicon::OutlinedBell)
                    ->color('gray')
                    ->action(fn () => DemoNotification::success('Zapisano zmiany')),
                Action::make('demoErrorNotification')
                    ->label('Pokaż błąd')
                    ->color('gray')
                    ->action(fn () => Notification::make()
                        ->danger()
                        ->title('Nie udało się wysłać zaproszenia')
                        ->body('Sprawdź adres e-mail i spróbuj ponownie.')
                        ->send()),
                ActionGroup::make([
                    DeactivateUserDemoAction::make('demoDanger'),
                ]),
            ]),
            ...self::blockHeading('Odznaki', 'Kolor oznacza stan; kategorie są neutralne.'),
            StyleguideButtons::badges(),
        ];
    }

    /**
     * A page-level block heading (h2 + grey description), one step above the section titles below it.
     * Styled by `pages/styleguide.css`.
     *
     * @return list<Html>
     */
    private static function blockHeading(string $title, string $description): array
    {
        return [
            Html::make(sprintf(
                '<div class="styleguide-block-heading"><h2 class="styleguide-block-heading__title">%s</h2><p class="styleguide-block-heading__description">%s</p></div>',
                e($title),
                e($description),
            )),
        ];
    }
}
