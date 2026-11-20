<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends ModelFactory<OfferModel> */
final class OfferModelFactory extends ModelFactory
{
    protected $model = OfferModel::class;

    /** A Draft with nothing but its owner and number. */
    public function definition(): array
    {
        return [
            'number' => 'AG'.str_pad((string) fake()->unique()->numberBetween(1, 99999999), 8, '0', STR_PAD_LEFT),
            'owner_id' => UserModel::factory(),
            'status' => OfferStatus::Draft,
            'glass_model_id' => null,
            'identification_hint' => null,
            'producer_id' => null,
            'year' => null,
            'condition' => null,
            'rating' => null,
            'description' => null,
            'private_notes' => null,
            'price_grosze' => null,
        ];
    }

    public function ownedBy(int $userId): static
    {
        return $this->state(fn (): array => ['owner_id' => $userId]);
    }

    public function withGlassModel(int $glassModelId): static
    {
        return $this->state(fn (): array => ['glass_model_id' => $glassModelId, 'identification_hint' => null]);
    }

    public function withHint(string $hint): static
    {
        return $this->state(fn (): array => ['identification_hint' => $hint, 'glass_model_id' => null]);
    }

    public function used(int $rating): static
    {
        return $this->state(fn (): array => ['condition' => OfferCondition::Used, 'rating' => $rating]);
    }

    public function priced(int $grosze): static
    {
        return $this->state(fn (): array => ['price_grosze' => $grosze]);
    }
}
