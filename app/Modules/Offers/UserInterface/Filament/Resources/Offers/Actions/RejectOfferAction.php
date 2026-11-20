<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\RejectOffer\RejectOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;

/** FR-09, template E (dangerous, with a form): a rejection needs a reason, which the owner reads in their Journal. */
final class RejectOfferAction
{
    public static function make(): Action
    {
        return Action::make('reject')
            ->label(__('offers::resources.offer.actions.reject.label'))
            ->color('danger')
            ->modalHeading(__('offers::resources.offer.actions.reject.heading'))
            ->modalDescription(__('offers::resources.offer.actions.reject.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.reject.label'))
            ->schema([
                Textarea::make('reason')
                    ->label(__('offers::resources.offer.fields.rejection_reason'))
                    ->required()
                    ->maxLength(2000)
                    ->rows(4),
            ])
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canReject($record))
            ->action(function (OfferModel $record, array $data): void {
                CommandSender::send(new RejectOfferCommand(CurrentActor::get(), (int) $record->getKey(), (string) $data['reason']));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.reject.done'));
    }
}
