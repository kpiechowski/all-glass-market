<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class ThumbnailMappingRefused extends DomainException
{
    public static function fillIsNotAColour(): self
    {
        return new self('catalogue::exceptions.thumbnail.fill_is_not_a_colour');
    }

    public static function opacityIsOutOfRange(): self
    {
        return new self('catalogue::exceptions.thumbnail.opacity_is_out_of_range');
    }

    public static function wrongDictionary(): self
    {
        return new self('catalogue::exceptions.thumbnail.wrong_dictionary');
    }
}
