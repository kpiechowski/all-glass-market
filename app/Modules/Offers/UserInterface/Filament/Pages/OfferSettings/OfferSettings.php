<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Pages\OfferSettings;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\ChangeVatRate\ChangeVatRateCommand;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\UserInterface\Filament\Pages\OfferSettings\Schemas\OfferSettingsForm;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Template D (settings), layout (a): one form, one sticky Zapisz. FR-30, D-54: Super Admin only;
 * saving sends one command, which announces every Offer whose buyer's price moves with the rate.
 *
 * @property-read Schema $form
 */
class OfferSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $slug = 'offer-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('offers::pages.offer_settings.navigation_label');
    }

    public function getTitle(): string
    {
        return __('offers::pages.offer_settings.title');
    }

    public static function canAccess(): bool
    {
        return CurrentActor::can(OffersPermission::ChangeVatRate);
    }

    public function mount(): void
    {
        $this->form->fill(['vat_rate_percent' => app(BuyersPrices::class)->vatRateBasisPoints() / 100]);
    }

    public function form(Schema $schema): Schema
    {
        return OfferSettingsForm::configure($schema);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->getSaveAction()])
                        ->alignment($this->getFormActionsAlignment())
                        ->sticky()
                        ->key('form-actions'),
                ]),
        ]);
    }

    protected function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('offers::pages.offer_settings.actions.save'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function save(): void
    {
        /** @var array{vat_rate_percent: numeric-string|int|float} $data */
        $data = $this->form->getState();

        CommandSender::send(new ChangeVatRateCommand(CurrentActor::get(), (int) round((float) $data['vat_rate_percent'] * 100)));

        Notification::make()->success()->title(__('offers::pages.offer_settings.saved'))->send();
    }
}
