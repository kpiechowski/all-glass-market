<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\AcceptInquiry\AcceptInquiryCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Support\InquiryActionVisibility;
use Filament\Actions\Action;

/** FR-22, template E (confirmation): accepting reserves the Offer in the sending admin's name. */
final class AcceptInquiryAction
{
    public static function make(): Action
    {
        return Action::make('accept')
            ->label(__('offers::resources.inquiry.actions.accept.label'))
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.inquiry.actions.accept.heading'))
            ->modalDescription(__('offers::resources.inquiry.actions.accept.description'))
            ->modalSubmitActionLabel(__('offers::resources.inquiry.actions.accept.label'))
            ->visible(fn (InquiryModel $record): bool => InquiryActionVisibility::canAnswer($record))
            ->action(function (InquiryModel $record): void {
                CommandSender::send(new AcceptInquiryCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.inquiry.actions.accept.done'));
    }
}
