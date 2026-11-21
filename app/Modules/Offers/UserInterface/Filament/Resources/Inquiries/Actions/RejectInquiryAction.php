<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\RejectInquiry\RejectInquiryCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Support\InquiryActionVisibility;
use Filament\Actions\Action;

/** FR-22, template E (confirmation): a rejection tells the sending admin. */
final class RejectInquiryAction
{
    public static function make(): Action
    {
        return Action::make('reject')
            ->label(__('offers::resources.inquiry.actions.reject.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.inquiry.actions.reject.heading'))
            ->modalDescription(__('offers::resources.inquiry.actions.reject.description'))
            ->modalSubmitActionLabel(__('offers::resources.inquiry.actions.reject.label'))
            ->visible(fn (InquiryModel $record): bool => InquiryActionVisibility::canReject($record))
            ->action(function (InquiryModel $record): void {
                CommandSender::send(new RejectInquiryCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.inquiry.actions.reject.done'));
    }
}
