<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Pages\EditProfile;

use App\Core\Concerns\HasCommandBus;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\ChangeUserPassword\ChangeUserPasswordCommand;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\Schemas\PasswordForm;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\Schemas\ProfileForm;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * @property-read Schema $form
 * @property-read Schema $passwordForm
 */
class EditProfile extends Page
{
    use HasCommandBus;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $passwordData = [];

    public static function getNavigationLabel(): string
    {
        return __('users::profile.navigation_label');
    }

    public function getTitle(): string
    {
        return __('users::profile.title');
    }

    public function mount(): void
    {
        /** @var UserModel $user */
        $user = Auth::user();
        $this->form->fill($user->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return ProfileForm::configure($schema);
    }

    public function passwordForm(Schema $schema): Schema
    {
        return PasswordForm::configure($schema);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([
                Tab::make(__('users::profile.tabs.general'))
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

                Tab::make(__('users::profile.tabs.change_password'))
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
            ->label(__('users::profile.actions.save'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    protected function getChangePasswordAction(): Action
    {
        return Action::make('changePassword')
            ->label(__('users::profile.actions.change_password'))
            ->submit('changePassword')
            ->color('warning');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        /** @var UserModel $user */
        $user = Auth::user();

        try {
            $this->commandBus->send(new UpdateUserCommand(
                actor: CurrentActor::get(),
                userId: $user->getKey(),
                name: $data['name'],
                email: $data['email'],
                phone: $data['phone'] ?? null,
                companyName: $data['company_name'] ?? null,
                companyNip: $data['company_nip'] ?? null,
                companyAddress: $data['company_address'] ?? null,
                pickupAddress: $data['shipment_address'] ?? null,
                city: $data['city'] ?? null,
                postalCode: $data['city_code'] ?? null,
                // FR-15: not editable here; passed through so a manager's own save keeps it.
                shippingText: $user->shipping_note,
            ));
        } catch (Throwable) {
            Notification::make()->danger()->title(__('users::profile.notifications.save_failed'))->send();

            return;
        }

        Notification::make()->success()->title(__('users::profile.notifications.saved'))->send();
    }

    public function changePassword(): void
    {
        $data = $this->passwordForm->getState();
        /** @var UserModel $user */
        $user = Auth::user();

        try {
            $this->commandBus->send(new ChangeUserPasswordCommand(
                actor: CurrentActor::get(),
                userId: $user->getKey(),
                password: $data['password'],
            ));
        } catch (Throwable) {
            Notification::make()->danger()->title(__('users::profile.notifications.password_change_failed'))->send();

            return;
        }

        $this->passwordForm->fill([]);

        Notification::make()->success()->title(__('users::profile.notifications.password_changed'))->send();
    }

    /** @return array<Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
