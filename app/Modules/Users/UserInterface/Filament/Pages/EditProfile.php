<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Pages;

use App\Core\Concerns\HasCommandBus;
use App\Modules\Users\Application\Commands\ChangeUserPassword\ChangeUserPasswordCommand;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * @property-read Schema $form
 * @property-read Schema $passwordForm
 */
class EditProfile extends Page
{
    use HasCommandBus;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $passwordData = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Personal Details')
                    ->aside()
                    ->description('Your basic account information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->unique(ignorable: fn () => auth()->user())
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->unique(ignorable: fn () => auth()->user())
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('phone')
                                ->tel()
                                ->maxLength(50)
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Company Account')
                    ->aside()
                    ->description('Register as a company to enable B2B pricing and invoicing')
                    ->schema([
                        Toggle::make('company_account')
                            ->label('I represent a company')
                            ->live()
                            ->columnSpanFull(),

                        Toggle::make('has_accepted_terms')
                            ->label('I accept the terms and conditions')
                            ->visible(fn (Get $get): bool => (bool) $get('company_account'))
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->visible(fn (Get $get): bool => (bool) $get('company_account'))
                            ->schema([
                                TextInput::make('company_name')
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('company_nip')
                                    ->label('NIP')
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('company_address')
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('shipment_address')
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('city')
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('city_code')
                                    ->label('Postal code')
                                    ->maxLength(10)
                                    ->columnSpan(1),
                            ]),

                        RichEditor::make('shipping_information')
                            ->visible(fn (Get $get): bool => (bool) $get('company_account'))
                            ->label('Shipping details')
                            ->helperText('Displayed on your offer pages. Leave empty to use the category default.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('passwordData')
            ->components([
                Section::make('Change Password')
                    ->aside()
                    ->description('Choose a strong password of at least 8 characters')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255)
                            ->suffixAction(
                                Action::make('generate-password')
                                    ->icon(Heroicon::ArrowPathRoundedSquare)
                                    ->action(fn (Set $set) => $set('password', Str::password(12, true, true, true, false)))
                            )
                            ->columnSpanFull(),

                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('password')
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([
                Tab::make('General')
                    ->schema([
                        Form::make([EmbeddedSchema::make('form')])
                            ->id('form')
                            ->livewireSubmitHandler('save')
                            ->footer([
                                Actions::make([$this->getSaveFormAction()])
                                    ->alignment($this->getFormActionsAlignment())
                                    ->key('form-actions'),
                            ]),
                    ]),

                Tab::make('Change Password')
                    ->icon(Heroicon::LockClosed)
                    ->schema([
                        Form::make([EmbeddedSchema::make('passwordForm')])
                            ->id('passwordForm')
                            ->livewireSubmitHandler('changePassword')
                            ->footer([
                                Actions::make([$this->getChangePasswordAction()])
                                    ->alignment($this->getFormActionsAlignment())
                                    ->key('password-form-actions'),
                            ]),
                    ]),
            ]),
        ]);
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Save profile')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    protected function getChangePasswordAction(): Action
    {
        return Action::make('changePassword')
            ->label('Change password')
            ->submit('changePassword')
            ->color('warning');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $this->commandBus->send(new UpdateUserCommand(
            id: $user->getKey(),
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            companyAccount: (bool) ($data['company_account'] ?? false),
            hasAcceptedTerms: (bool) ($data['has_accepted_terms'] ?? false),
            companyName: $data['company_name'] ?? null,
            companyNip: $data['company_nip'] ?? null,
            companyAddress: $data['company_address'] ?? null,
            shipmentAddress: $data['shipment_address'] ?? null,
            city: $data['city'] ?? null,
            cityCode: $data['city_code'] ?? null,
            shippingInformation: $data['shipping_information'] ?? null,
        ));

        Notification::make()->success()->title('Profile saved')->send();
    }

    public function changePassword(): void
    {
        $data = $this->passwordForm->getState();
        $user = auth()->user();

        $this->commandBus->send(new ChangeUserPasswordCommand(
            id: $user->getKey(),
            password: $data['password'],
        ));

        $this->passwordForm->fill([]);

        Notification::make()->success()->title('Password changed')->send();
    }

    /** @return array<Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'My Profile';
    }
}
