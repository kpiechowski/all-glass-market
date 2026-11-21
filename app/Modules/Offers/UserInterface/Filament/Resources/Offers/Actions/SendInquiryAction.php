<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\SendInquiry\SendInquiryCommand;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Queries\InquiryQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Text;

/**
 * FR-21, template E (short form): an admin asks the owner of an Active Offer about it. The modal first says how
 * many Inquiries are already open and from whom.
 */
final class SendInquiryAction
{
    public static function make(): Action
    {
        return Action::make('sendInquiry')
            ->label(__('offers::resources.offer.actions.send_inquiry.label'))
            ->color('gray')
            ->modalHeading(__('offers::resources.offer.actions.send_inquiry.heading'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.send_inquiry.submit'))
            ->schema(fn (OfferModel $record): array => [
                Text::make(self::openInquiriesSummary($record)),
                Select::make('channel')
                    ->label(__('offers::resources.inquiry.fields.channel'))
                    ->options(SaleChannel::class),
                Textarea::make('note')
                    ->label(__('offers::resources.inquiry.fields.note'))
                    ->helperText(__('offers::resources.inquiry.helpers.note'))
                    ->required()
                    ->maxLength(OfferDomainPolicy::MAX_INQUIRY_NOTE_LENGTH)
                    ->rows(3),
            ])
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canSendInquiry($record))
            ->action(function (OfferModel $record, array $data): void {
                CommandSender::send(new SendInquiryCommand(
                    CurrentActor::get(),
                    (int) $record->getKey(),
                    ($data['channel'] ?? null) instanceof SaleChannel ? $data['channel']->value : ($data['channel'] ?? null),
                    (string) $data['note'],
                ));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.send_inquiry.done'));
    }

    private static function openInquiriesSummary(OfferModel $offer): string
    {
        $open = app(InquiryQueries::class)->openOfOffer((int) $offer->getKey())->get();

        if ($open->isEmpty()) {
            return __('offers::resources.offer.actions.send_inquiry.none_open');
        }

        return __('offers::resources.offer.actions.send_inquiry.open', [
            'count' => $open->count(),
            'senders' => $open->map(fn (InquiryModel $inquiry): string => $inquiry->sender->name)->implode(', '),
        ]);
    }
}
