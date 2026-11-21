<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends ModelFactory<InquiryModel> */
final class InquiryModelFactory extends ModelFactory
{
    protected $model = InquiryModel::class;

    /** A Sent Inquiry from an admin. */
    public function definition(): array
    {
        return [
            'offer_id' => OfferModelFactory::new(),
            'sender_id' => UserModel::factory()->admin(),
            'channel' => null,
            'note' => 'Pan Kowalski, tel. 500 100 200',
            'state' => InquiryState::Sent,
            'sent_at' => now(),
            'read_at' => null,
            'answered_at' => null,
        ];
    }

    public function forOffer(int $offerId): static
    {
        return $this->state(fn (): array => ['offer_id' => $offerId]);
    }

    public function sentBy(int $senderId): static
    {
        return $this->state(fn (): array => ['sender_id' => $senderId]);
    }

    public function inState(InquiryState $state): static
    {
        return $this->state(fn (): array => ['state' => $state]);
    }
}
