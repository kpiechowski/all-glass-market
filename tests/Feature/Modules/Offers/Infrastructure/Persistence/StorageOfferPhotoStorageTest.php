<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence;

use App\Modules\Offers\Domain\Repositories\OfferPhotoStorage;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/** FR-26: the files go only once the permanent delete has committed. */
class StorageOfferPhotoStorageTest extends TestCase
{
    use RefreshDatabase;

    private function photos(): OfferPhotos
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('offers/public/1/a.jpg', 'x');
        Storage::disk('local')->put('offers/private/1/b.jpg', 'x');

        return OfferPhotos::from(['offers/public/1/a.jpg'], ['offers/private/1/b.jpg']);
    }

    public function test_the_files_are_deleted_once_the_transaction_commits(): void
    {
        $photos = $this->photos();

        DB::transaction(fn () => app(OfferPhotoStorage::class)->deleteAll($photos));

        Storage::disk('public')->assertMissing('offers/public/1/a.jpg');
        Storage::disk('local')->assertMissing('offers/private/1/b.jpg');
    }

    public function test_the_files_are_kept_when_the_transaction_rolls_back(): void
    {
        $photos = $this->photos();

        try {
            DB::transaction(function () use ($photos): void {
                app(OfferPhotoStorage::class)->deleteAll($photos);

                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        Storage::disk('public')->assertExists('offers/public/1/a.jpg');
        Storage::disk('local')->assertExists('offers/private/1/b.jpg');
    }
}
