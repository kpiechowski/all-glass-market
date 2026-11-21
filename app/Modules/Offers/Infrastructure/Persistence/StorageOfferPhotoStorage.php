<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Modules\Offers\Domain\Repositories\OfferPhotoStorage;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use Illuminate\Support\Facades\Storage;

/** Public photos sit on the public disk, private ones on the disk with no URL (FR-05). */
final class StorageOfferPhotoStorage implements OfferPhotoStorage
{
    public const string PUBLIC_DISK = 'public';

    public const string PRIVATE_DISK = 'local';

    public function deleteAll(OfferPhotos $photos): void
    {
        Storage::disk(self::PUBLIC_DISK)->delete($photos->public);
        Storage::disk(self::PRIVATE_DISK)->delete($photos->private);
    }
}
