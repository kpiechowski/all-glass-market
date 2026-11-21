<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends ModelFactory<ReservationModel> */
final class ReservationModelFactory extends ModelFactory
{
    protected $model = ReservationModel::class;

    /** A current Reservation by a seller. */
    public function definition(): array
    {
        return [
            'offer_id' => OfferModelFactory::new(),
            'author_id' => UserModel::factory(),
            'made_by_admin' => false,
            'note' => 'Pan Kowalski, tel. 500 100 200',
            'made_at' => now(),
            'ended_as' => null,
            'ended_at' => null,
            'is_current' => true,
        ];
    }

    public function forOffer(int $offerId): static
    {
        return $this->state(fn (): array => ['offer_id' => $offerId]);
    }

    public function madeBy(int $authorId, bool $admin): static
    {
        return $this->state(fn (): array => ['author_id' => $authorId, 'made_by_admin' => $admin]);
    }

    public function ended(ReservationEnd $how): static
    {
        return $this->state(fn (): array => ['ended_as' => $how, 'ended_at' => now(), 'is_current' => null]);
    }
}
