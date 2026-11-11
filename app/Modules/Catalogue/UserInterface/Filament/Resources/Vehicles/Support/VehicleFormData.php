<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Support;

/** Turns what the vehicle form holds into the arguments of the create and update commands. */
final class VehicleFormData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, makeId: int, model: ?string, bodyId: ?int, yearFrom: ?int, yearTo: ?int, generation: ?string, argicCode: ?string}
     */
    public static function commandArguments(array $data): array
    {
        return [
            'name' => (string) $data['name'],
            'makeId' => (int) $data['make_id'],
            'model' => self::text($data['model'] ?? null),
            'bodyId' => self::number($data['body_id'] ?? null),
            'yearFrom' => self::number($data['year_from'] ?? null),
            'yearTo' => self::number($data['year_to'] ?? null),
            'generation' => self::text($data['generation'] ?? null),
            'argicCode' => self::text($data['argic_code'] ?? null),
        ];
    }

    private static function text(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private static function number(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
