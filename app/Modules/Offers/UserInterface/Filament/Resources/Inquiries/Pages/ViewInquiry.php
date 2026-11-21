<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Pages;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\MarkInquiryRead\MarkInquiryReadCommand;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Actions\AcceptInquiryAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Actions\RejectInquiryAction;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\InquiryResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Template C (record view). One primary: accepting. Rejecting is the one secondary. Opening it as its owner is
 * what marks it read (FR-22), so it goes through a command.
 */
class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var InquiryModel $inquiry */
        $inquiry = $this->getRecord();

        if (OfferDomainPolicy::inquiryAwaitsReading($inquiry->state) && OfferDomainPolicy::isOwnedBy($inquiry->offer->owner_id, CurrentActor::get()->id)) {
            CommandSender::send(new MarkInquiryReadCommand(CurrentActor::get(), (int) $inquiry->getKey()));

            $this->record = $inquiry->refresh();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            AcceptInquiryAction::make(),
            RejectInquiryAction::make(),
        ];
    }
}
