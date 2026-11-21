<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Exceptions;

use App\Core\Exceptions\DomainException;
use Stringable;

final class OfferRefused extends DomainException
{
    public static function glassModelAndHintTogether(): self
    {
        return new self('offers::exceptions.glass_model_and_hint_together');
    }

    public static function hintIsTooLong(int $max): self
    {
        return new self('offers::exceptions.hint_is_too_long', ['max' => $max]);
    }

    public static function ratingIsInvalid(): self
    {
        return new self('offers::exceptions.rating_is_invalid');
    }

    public static function ratingNotForNew(): self
    {
        return new self('offers::exceptions.rating_not_for_new');
    }

    public static function ratingNeedsCondition(): self
    {
        return new self('offers::exceptions.rating_needs_condition');
    }

    public static function vatRateIsInvalid(): self
    {
        return new self('offers::exceptions.vat_rate_is_invalid');
    }

    public static function priceIsInvalid(): self
    {
        return new self('offers::exceptions.price_is_invalid');
    }

    public static function yearIsInvalid(): self
    {
        return new self('offers::exceptions.year_is_invalid');
    }

    public static function numberIsInvalid(): self
    {
        return new self('offers::exceptions.number_is_invalid');
    }

    public static function conditionIsUnknown(): self
    {
        return new self('offers::exceptions.condition_is_unknown');
    }

    public static function glassModelCannotBePicked(): self
    {
        return new self('offers::exceptions.glass_model_cannot_be_picked');
    }

    public static function producerCannotBePicked(): self
    {
        return new self('offers::exceptions.producer_cannot_be_picked');
    }

    public static function notYours(): self
    {
        return new self('offers::exceptions.not_yours');
    }

    public static function tooManyPublicPhotos(int $max): self
    {
        return new self('offers::exceptions.too_many_public_photos', ['max' => $max]);
    }

    public static function tooManyPrivatePhotos(int $max): self
    {
        return new self('offers::exceptions.too_many_private_photos', ['max' => $max]);
    }

    public static function photoIsInvalid(): self
    {
        return new self('offers::exceptions.photo_is_invalid');
    }

    /**
     * FR-06: names the fields the sender still has to fill in.
     *
     * @param  list<string>  $fields
     */
    public static function missingForSending(array $fields): self
    {
        return new self('offers::exceptions.missing_for_sending', ['fields' => new class($fields) implements Stringable
        {
            /** @param list<string> $fields */
            public function __construct(private array $fields) {}

            /** Translated only when the message is shown, so the domain needs no translator to refuse. */
            public function __toString(): string
            {
                return implode(', ', array_map(fn (string $field): string => __('offers::exceptions.sending_fields.'.$field), $this->fields));
            }
        }]);
    }

    public static function cannotBeSent(): self
    {
        return new self('offers::exceptions.cannot_be_sent');
    }

    public static function cannotBeApproved(): self
    {
        return new self('offers::exceptions.cannot_be_approved');
    }

    public static function glassModelRequired(): self
    {
        return new self('offers::exceptions.glass_model_required');
    }

    public static function cannotBeRejected(): self
    {
        return new self('offers::exceptions.cannot_be_rejected');
    }

    public static function rejectionReasonRequired(): self
    {
        return new self('offers::exceptions.rejection_reason_required');
    }
}
