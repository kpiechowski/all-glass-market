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

    public static function cannotBeWithdrawn(): self
    {
        return new self('offers::exceptions.cannot_be_withdrawn');
    }

    public static function cannotBeResumed(): self
    {
        return new self('offers::exceptions.cannot_be_resumed');
    }

    public static function cannotBeTrashed(): self
    {
        return new self('offers::exceptions.cannot_be_trashed');
    }

    public static function cannotBeRestored(): self
    {
        return new self('offers::exceptions.cannot_be_restored');
    }

    public static function cannotBeDeletedPermanently(): self
    {
        return new self('offers::exceptions.cannot_be_deleted_permanently');
    }

    public static function isInTrash(): self
    {
        return new self('offers::exceptions.is_in_trash');
    }

    public static function reservedCannotBeTrashed(): self
    {
        return new self('offers::exceptions.reserved_cannot_be_trashed');
    }

    public static function cannotBeEditedWhileReservedOrSold(): self
    {
        return new self('offers::exceptions.cannot_be_edited_while_reserved_or_sold');
    }

    public static function cannotBeReserved(): self
    {
        return new self('offers::exceptions.cannot_be_reserved');
    }

    public static function cannotBeReleased(): self
    {
        return new self('offers::exceptions.cannot_be_released');
    }

    public static function cannotBeSold(): self
    {
        return new self('offers::exceptions.cannot_be_sold');
    }

    public static function priceRequiredToSell(): self
    {
        return new self('offers::exceptions.price_required_to_sell');
    }

    public static function saleCannotBeReturned(): self
    {
        return new self('offers::exceptions.sale_cannot_be_returned');
    }

    public static function reservationNoteRequired(): self
    {
        return new self('offers::exceptions.reservation_note_required');
    }

    public static function reservationNoteIsTooLong(int $max): self
    {
        return new self('offers::exceptions.reservation_note_is_too_long', ['max' => $max]);
    }

    public static function inquiryNoteRequired(): self
    {
        return new self('offers::exceptions.inquiry_note_required');
    }

    public static function inquiryNoteIsTooLong(int $max): self
    {
        return new self('offers::exceptions.inquiry_note_is_too_long', ['max' => $max]);
    }

    public static function inquiryIsClosed(): self
    {
        return new self('offers::exceptions.inquiry_is_closed');
    }

    public static function offerCannotReceiveInquiry(): self
    {
        return new self('offers::exceptions.offer_cannot_receive_inquiry');
    }

    public static function inquiryChannelIsUnknown(): self
    {
        return new self('offers::exceptions.inquiry_channel_is_unknown');
    }

    public static function reservationAlreadyEnded(): self
    {
        return new self('offers::exceptions.reservation_already_ended');
    }

    public static function reservationIsLocked(): self
    {
        return new self('offers::exceptions.reservation_is_locked');
    }

    public static function salePriceIsInvalid(): self
    {
        return new self('offers::exceptions.sale_price_is_invalid');
    }

    public static function saleChannelIsUnknown(): self
    {
        return new self('offers::exceptions.sale_channel_is_unknown');
    }

    public static function returnReasonRequired(): self
    {
        return new self('offers::exceptions.return_reason_required');
    }

    public static function saleAlreadyReturned(): self
    {
        return new self('offers::exceptions.sale_already_returned');
    }

    public static function priceChangeIsInvalid(): self
    {
        return new self('offers::exceptions.price_change_is_invalid');
    }

    public static function priceCannotBeChanged(): self
    {
        return new self('offers::exceptions.price_cannot_be_changed');
    }

    public static function priceWouldNotBePositive(): self
    {
        return new self('offers::exceptions.price_would_not_be_positive');
    }
}
