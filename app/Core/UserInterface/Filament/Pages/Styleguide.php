<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages;

use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\DeactivateUserDemoAction;
use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\DemoNotification;
use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\EditPricingDemoAction;
use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\InviteUserDemoAction;
use App\Core\UserInterface\Filament\Pages\Styleguide\Schemas\StyleguideForm;
use App\Core\UserInterface\Filament\Pages\Styleguide\Schemas\StyleguideInfolist;
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
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Styleguide (D-33, D-34): every main component on one page, built from the admin-ui-design
 * templates (B form, C record view, A list, E action modals). It is the reference for them and a
 * regression check.
 *
 * - Registered in AdminPanelProvider only when `app()->isLocal()`; elsewhere its route does not exist.
 * - Static data only: the table reads DemoUsers through `records()`, the forms keep page state.
 *   Nothing is persisted, so no action builds a command: each one only sends a notification.
 *   The forms validate and never call `getState()`, so uploads are never stored.
 * - Demo copy is hard-coded Polish: the one exception to "every string from translations" (D-33).
 *
 * @property-read Schema $form
 * @property-read Schema $settingsForm
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

    public function infolist(Schema $schema): Schema
    {
        return StyleguideInfolist::configure($schema);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            ...self::blockHeading('Formularz', 'Szablon B, układ (a): sekcje jedna pod drugą, siatka 12 kolumn.'),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Zapisz')->submit('save'),
                        Action::make('cancel')->label('Anuluj')->color('gray')->action(fn () => $this->cancel()),
                    ])->sticky()->key('form-actions'),
                ]),

            ...self::blockHeading('Zakładki', 'Zakładki strony (bez ramki) oraz zakładki w formularzu.'),
            StyleguideTabs::make(),
            Form::make([EmbeddedSchema::make('settingsForm')])
                ->id('settingsForm')
                ->livewireSubmitHandler('saveSettings')
                ->footer([
                    Actions::make([
                        Action::make('saveSettings')->label('Zapisz ustawienia')->submit('saveSettings'),
                    ])->key('settings-form-actions'),
                ]),

            Html::make('<span id="karta-rekordu"></span>'),
            ...self::blockHeading('Karta rekordu', 'Szablon C: arkusz, jeden wpis w wierszu, „—” dla pustych wartości.'),
            EmbeddedSchema::make('infolist'),

            ...self::blockHeading('Lista', 'Szablon A: zakładki listy, filtry, karta na telefonie, akcje wiersza i masowe.'),
            View::make('core::filament.pages.styleguide.list-tabs'),
            EmbeddedTable::make(),

            ...self::blockHeading('Akcje i powiadomienia', 'Szablon E: modal, panel boczny, niebezpieczne potwierdzenie, powiadomienia.'),
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

    /** @return list<Text> */
    private static function blockHeading(string $title, string $description): array
    {
        return [
            Text::make($title)->size(TextSize::Large)->weight(FontWeight::Bold),
            Text::make($description)->color('gray'),
        ];
    }
}
