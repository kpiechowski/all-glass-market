<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

use App\Modules\Catalogue\Domain\ValueObjects\OemCode;

/**
 * What the candidate search is asked (D-49, FR-22): a text, optional filters, and whether withdrawn
 * models count. It also decides how the text is read for each route, so every caller (the admin
 * list, Offers through ticket 08) gets the same behaviour.
 *
 * Chosen at delivery, as the spec asks: a fragment needs at least 3 characters (after the route's
 * normalisation) and at most 20 candidates are returned (V1 values).
 */
final readonly class CandidateQuery
{
    public const int MIN_FRAGMENT_LENGTH = 3;

    public const int DEFAULT_LIMIT = 20;

    public function __construct(
        public string $text,
        public ?int $positionId = null,
        public ?int $colourId = null,
        public ?int $makeId = null,
        public bool $includeWithdrawn = false,
        public ?int $limit = self::DEFAULT_LIMIT,
    ) {}

    /** The same question without the result limit, to get every matching id. */
    public function withoutLimit(): self
    {
        return new self($this->text, $this->positionId, $this->colourId, $this->makeId, $this->includeWithdrawn, null);
    }

    /** A text shorter than the minimum fragment finds nothing. */
    public function isSearchable(): bool
    {
        return mb_strlen($this->cleanText()) >= self::MIN_FRAGMENT_LENGTH;
    }

    /** Whether the position, colour or make narrows the models. */
    public function hasFilter(): bool
    {
        return $this->positionId !== null || $this->colourId !== null || $this->makeId !== null;
    }

    /** RULE-03 form: uppercase letters and digits only. */
    public function eurocodeFragment(): ?string
    {
        return $this->longEnough(preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($this->cleanText())) ?? '');
    }

    /** RULE-04 form: uppercase, spaces, dashes and dots ignored. */
    public function oemFragment(): ?string
    {
        return $this->longEnough(OemCode::keyOf($this->cleanText()));
    }

    /**
     * Every word must match some field of one vehicle.
     *
     * @return list<string>
     */
    public function vehicleWords(): array
    {
        if (! $this->isSearchable()) {
            return [];
        }

        return array_values(array_filter(preg_split('/\s+/u', $this->cleanText()) ?: [], fn (string $word): bool => $word !== ''));
    }

    private function longEnough(string $fragment): ?string
    {
        return mb_strlen($fragment) >= self::MIN_FRAGMENT_LENGTH ? $fragment : null;
    }

    /** Trimmed, and without the LIKE wildcards, which are not characters of a code or a name. */
    private function cleanText(): string
    {
        return trim(str_replace(['%', '_'], '', $this->text));
    }
}
