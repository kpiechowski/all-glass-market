<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Auth;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Application\Commands\CompleteForcedPasswordChange\CompleteForcedPasswordChangeCommand;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Support\KeepsOwnSessionAfterPasswordChange;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

/**
 * "Ustaw nowe hasło" (FR-37, FR-38, WF-07): where every panel page leads while
 * "password change required" is set. The User can only set a new password
 * (RULE-03, different from the current one, the current one is not asked for)
 * or sign out.
 *
 * @property-read Schema $form
 */
class SetNewPassword extends SimplePage
{
    /** Relative route name, registered by the panel provider (an authenticated route). */
    public const string ROUTE = 'set-new-password';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    #[Unexposed]
    public function mount(): void
    {
        /** @var UserModel $user */
        $user = Auth::user();

        if (! $user->password_change_required) {
            $this->redirect(Filament::getUrl());

            return;
        }

        $this->form->fill();
    }

    #[Exposed]
    public function setNewPassword(): void
    {
        $data = $this->form->getState();

        try {
            CommandSender::send(new CompleteForcedPasswordChangeCommand(CurrentActor::get(), $data['password']));
        } catch (Halt) {
            return;
        }

        KeepsOwnSessionAfterPasswordChange::for((int) Auth::id());

        $this->redirect(Filament::getUrl());
    }

    #[Unexposed]
    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    #[Unexposed]
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('password')
                ->label(__('users::pages.set_new_password.password'))
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->required()
                ->minLength(8)
                ->maxLength(255)
                ->same('password_confirmation')
                ->autofocus(),

            TextInput::make('password_confirmation')
                ->label(__('users::pages.set_new_password.password_confirmation'))
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->required()
                ->dehydrated(false),
        ]);
    }

    #[Unexposed]
    public function getTitle(): string|Htmlable
    {
        return __('users::pages.set_new_password.title');
    }

    #[Unexposed]
    public function getHeading(): string|Htmlable|null
    {
        return __('users::pages.set_new_password.title');
    }

    #[Unexposed]
    public function getSubheading(): string|Htmlable|null
    {
        return __('users::pages.set_new_password.description');
    }

    #[Unexposed]
    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getFormContentComponent()]);
    }

    #[Unexposed]
    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('setNewPassword')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label(__('users::pages.set_new_password.submit'))
                        ->submit('setNewPassword'),
                    Action::make('logout')
                        ->label(__('users::pages.set_new_password.logout'))
                        ->color('gray')
                        ->url(Filament::getLogoutUrl())
                        ->postToUrl(),
                ])->fullWidth()->key('form-actions'),
            ]);
    }
}
