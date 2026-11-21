<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Modules\Offers\Domain\Enums\OfferCondition;

/** Turns what the Offer form holds into command arguments and back; the form types złoty, the command carries grosze. */
final class OfferFormData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{glassModelId: ?int, identificationHint: ?string, producerId: ?int, year: ?int, condition: ?string, rating: ?int, description: ?string, privateNotes: ?string, priceGrosze: ?int, publicPhotos: list<string>, privatePhotos: list<string>}
     */
    public static function commandArguments(array $data): array
    {
        return [
            'glassModelId' => self::number($data['glass_model_id'] ?? null),
            'identificationHint' => self::text($data['identification_hint'] ?? null),
            'producerId' => self::number($data['producer_id'] ?? null),
            'year' => self::number($data['year'] ?? null),
            'condition' => self::condition($data['condition'] ?? null)?->value,
            'rating' => self::number($data['rating'] ?? null),
            'description' => self::text($data['description'] ?? null),
            'privateNotes' => self::text($data['private_notes'] ?? null),
            'priceGrosze' => self::grosze($data['price'] ?? null),
            'publicPhotos' => self::paths($data['public_photos'] ?? null),
            'privatePhotos' => self::paths($data['private_photos'] ?? null),
        ];
    }

    /**
     * The model's attributes as the form's state.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function formState(array $data): array
    {
        $data['price'] = isset($data['price_grosze']) ? number_format($data['price_grosze'] / 100, 2, '.', '') : null;
        $data['condition'] = self::condition($data['condition'] ?? null)?->value;

        return $data;
    }

    /** The condition field's state is the case or its value, depending on the path it came by. */
    public static function condition(mixed $state): ?OfferCondition
    {
        return $state instanceof OfferCondition ? $state : (filled($state) ? OfferCondition::tryFrom((string) $state) : null);
    }

    /**
     * The upload field's state is keyed by file; the command takes the paths in display order.
     *
     * @return list<string>
     */
    private static function paths(mixed $state): array
    {
        return is_array($state) ? array_values(array_map('strval', $state)) : [];
    }

    private static function text(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    private static function number(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /** A złoty amount as typed (comma or dot) in grosze; null when empty. */
    public static function grosze(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) str_replace(',', '.', (string) $value)) * 100);
    }
}
