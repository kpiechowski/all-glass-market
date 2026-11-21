<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Http\Controllers;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-05, D-49: a private photo is served only to the owner and to whoever manages all Offers.
 * Anyone else, a seller asking for another Offer's photo included, gets a plain 404. The photo is
 * named by its position on the Offer, never by a path, so no file outside the Offer can be asked for.
 */
final class PrivatePhotoController
{
    public const string ROUTE = 'offers.private-photo';

    public function __invoke(int $offer, int $position): Response
    {
        $model = app(OfferQueries::class)->find($offer);

        abort_if($model === null || ! OfferDomainPolicy::canBeSeenBy(
            $model->owner_id,
            (int) CurrentActor::get()->id,
            CurrentActor::can(OffersPermission::ManageAll),
        ), 404);

        $path = ($model->private_photos ?? [])[$position] ?? null;
        $disk = Storage::disk(self::DISK);

        abort_if($path === null || ! $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, no-store']);
    }

    /** The disk of private photos: not the public one, so the files have no URL of their own. */
    public const string DISK = 'local';
}
