<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;

/**
 * The photos of an Offer (FR-05, D-49), as storage paths in their order: up to 10 public ones, the
 * first being the main photo, and up to 10 private ones. The public photos are part of the Offer's
 * published data (the re-approval rule of ticket 03 watches them); the private ones never are.
 * A path is a relative one inside the uploader's own folder for its kind.
 */
final readonly class OfferPhotos
{
    /**
     * @param  list<string>  $public
     * @param  list<string>  $private
     */
    private function __construct(
        public array $public,
        public array $private,
    ) {}

    /**
     * @param  list<string>  $public
     * @param  list<string>  $private
     */
    public static function from(array $public, array $private): self
    {
        $public = array_values($public);
        $private = array_values($private);

        if (count($public) > OfferDomainPolicy::MAX_PHOTOS_PER_KIND) {
            throw OfferRefused::tooManyPublicPhotos(OfferDomainPolicy::MAX_PHOTOS_PER_KIND);
        }

        if (count($private) > OfferDomainPolicy::MAX_PHOTOS_PER_KIND) {
            throw OfferRefused::tooManyPrivatePhotos(OfferDomainPolicy::MAX_PHOTOS_PER_KIND);
        }

        self::guardPaths($public);
        self::guardPaths($private);

        return new self($public, $private);
    }

    public static function none(): self
    {
        return new self([], []);
    }

    /** @param list<string> $paths */
    private static function guardPaths(array $paths): void
    {
        foreach ($paths as $path) {
            $segments = explode('/', $path);

            if ($path === '' || str_starts_with($path, '/') || in_array('..', $segments, true) || in_array('', $segments, true)) {
                throw OfferRefused::photoIsInvalid();
            }
        }

        if (count(array_unique($paths)) !== count($paths)) {
            throw OfferRefused::photoIsInvalid();
        }
    }
}
