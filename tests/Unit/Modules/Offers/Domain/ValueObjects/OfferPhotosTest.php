<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use PHPUnit\Framework\TestCase;

/** FR-05: up to 10 ordered public photos and up to 10 private ones. */
class OfferPhotosTest extends TestCase
{
    /** @return list<string> */
    private function paths(int $count, string $directory = 'offers/public/3'): array
    {
        return array_map(fn (int $n): string => "{$directory}/photo-{$n}.jpg", range(1, $count));
    }

    private function assertRefused(string $key, callable $action): void
    {
        try {
            $action();
        } catch (OfferRefused $exception) {
            $this->assertSame('offers::exceptions.'.$key, $exception->translationKey);

            return;
        }

        $this->fail('Expected a refusal: '.$key);
    }

    public function test_it_keeps_the_order_of_both_kinds(): void
    {
        $photos = OfferPhotos::from(['offers/public/3/b.jpg', 'offers/public/3/a.jpg'], ['offers/private/3/x.jpg']);

        $this->assertSame(['offers/public/3/b.jpg', 'offers/public/3/a.jpg'], $photos->public);
        $this->assertSame(['offers/private/3/x.jpg'], $photos->private);
    }

    public function test_ten_of_each_kind_are_allowed(): void
    {
        $photos = OfferPhotos::from($this->paths(10), $this->paths(10, 'offers/private/3'));

        $this->assertCount(10, $photos->public);
        $this->assertCount(10, $photos->private);
    }

    public function test_an_eleventh_public_photo_is_refused(): void
    {
        $this->assertRefused('too_many_public_photos', fn () => OfferPhotos::from($this->paths(11), []));
    }

    public function test_an_eleventh_private_photo_is_refused(): void
    {
        $this->assertRefused('too_many_private_photos', fn () => OfferPhotos::from([], $this->paths(11, 'offers/private/3')));
    }

    public function test_a_repeated_path_is_refused(): void
    {
        $this->assertRefused('photo_is_invalid', fn () => OfferPhotos::from(['offers/public/3/a.jpg', 'offers/public/3/a.jpg'], []));
    }

    public function test_a_path_that_climbs_out_of_the_folder_or_is_empty_is_refused(): void
    {
        $this->assertRefused('photo_is_invalid', fn () => OfferPhotos::from(['offers/public/3/../4/a.jpg'], []));
        $this->assertRefused('photo_is_invalid', fn () => OfferPhotos::from(['/etc/passwd'], []));
        $this->assertRefused('photo_is_invalid', fn () => OfferPhotos::from([''], []));
    }

    public function test_none_has_no_photos(): void
    {
        $this->assertSame([], OfferPhotos::none()->public);
        $this->assertSame([], OfferPhotos::none()->private);
    }
}
