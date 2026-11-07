<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Pages\EditProfile;

use App\Core\Concerns\HasCommandBus;
use App\Core\Exceptions\DomainException;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\ChangeUserPassword\ChangeUserPasswordCommand;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\Schemas\PasswordForm;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\Schemas\ProfileForm;
use App\Modules\Users\UserInterface\Support\KeepsOwnSessionAfterPasswordChange;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Template D (settings), layout (a): one form with one sticky Zapisz; the password change is the
 * header action "Zmień hasło" opening a modal (template E), so it appears once.
 *
 * @property-read Schema $form
 */
class EditProfile extends Page
{
    use HasCommandBus;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('users::profile.navigation_label');
    }

    /** FR-16, D-23: an attention indicator while the Account type is not Company; read from the signed-in User, no query. */
    public static function getNavigationBadge(): ?string
    {
        /** @var UserModel|null $user */
        $user = Auth::user();

        return $user !== null && $user->account_type !== AccountType::Company ? '!' : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return __('users::profile.navigation_badge_tooltip');
    }

    public function getTitle(): string
    {
        return __('users::profile.title');
    }

    public function mount(): void
    {
        /** @var UserModel $user */
        $user = Auth::user();
        $this->form->fill([
            ...$user->attributesToArray(),
            'account_type' => $user->account_type?->value,
            'seller_declaration_accepted' => $user->account_type === AccountType::Company,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return ProfileForm::configure($schema);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->getSaveFormAction()])
                        ->alignment($this->getFormActionsAlignment())
                        ->sticky()
                        ->key('form-actions'),
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

    /** FR-18: the "Pamiętaj!" notice shown when Private is chosen, even if never saved. */
    public function privateAccountNoticeAction(): Action
    {
        return Action::make('privateAccountNotice')
            ->modalHeading(__('users::profile.private_notice_modal.heading'))
            ->modalDescription(__('users::profile.private_notice_modal.description'))
            ->modalSubmitActionLabel(__('users::profile.private_notice_modal.confirm'))
            ->modalCancelAction(false)
            ->action(fn () => null);
    }

    /** A grey secondary header action; the modal holds the two password fields. */
    protected function changePasswordAction(): Action
    {
        return Action::make('changePassword')
            ->label(__('users::profile.actions.change_password'))
            ->color('gray')
            ->modalHeading(__('users::profile.actions.change_password'))
            ->modalSubmitActionLabel(__('users::profile.actions.change_password'))
            ->modalWidth('md')
            ->schema(PasswordForm::components())
            ->action(fn (array $data, Action $action) => $this->changePassword($data, $action));
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
                // FR-19: a locked control is not submitted, so the type stays as it is.
                accountType: ($data['account_type'] ?? null) instanceof AccountType ? $data['account_type']->value : ($data['account_type'] ?? null),
                sellerDeclarationAccepted: (bool) ($data['seller_declaration_accepted'] ?? false),
            ));
        } catch (DomainException $exception) {
            if ($exception->translationKey === 'users::exceptions.company_data_missing') {
                $message = __($exception->translationKey);

                throw ValidationException::withMessages(['data.company_nip' => $message, 'data.company_address' => $message]);
            }

            Notification::make()->danger()->title(__($exception->translationKey, $exception->parameters))->send();

            return;
        } catch (Throwable) {
            Notification::make()->danger()->title(__('users::profile.notifications.save_failed'))->send();

            return;
        }

        Notification::make()->success()->title(__('users::profile.notifications.saved'))->send();
    }

    /** @param  array<string, mixed>  $data */
    private function changePassword(array $data, Action $action): void
    {
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
            $action->halt();

            return;
        }

        KeepsOwnSessionAfterPasswordChange::for((int) $user->getKey());

        Notification::make()->success()->title(__('users::profile.notifications.password_changed'))->send();
    }

    /** @return array<Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [$this->changePasswordAction()];
    }
}
