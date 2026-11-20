<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\Policies;

use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use PHPUnit\Framework\TestCase;

class OfferDomainPolicyTest extends TestCase
{
    public function test_a_rating_applies_to_used_and_outlet_only(): void
    {
        $this->assertTrue(OfferDomainPolicy::ratingApplies(OfferCondition::Used));
        $this->assertTrue(OfferDomainPolicy::ratingApplies(OfferCondition::Outlet));
        $this->assertFalse(OfferDomainPolicy::ratingApplies(OfferCondition::New));
        $this->assertFalse(OfferDomainPolicy::ratingApplies(null));
    }

    public function test_a_new_piece_is_shown_with_five_stars_and_others_with_their_own(): void
    {
        $this->assertSame(5, OfferDomainPolicy::displayedStars(OfferCondition::New, null));
        $this->assertSame(3, OfferDomainPolicy::displayedStars(OfferCondition::Used, 3));
        $this->assertNull(OfferDomainPolicy::displayedStars(OfferCondition::Outlet, null));
        $this->assertNull(OfferDomainPolicy::displayedStars(null, null));
    }

    public function test_the_owner_and_whoever_manages_all_offers_may_see_and_change_an_offer(): void
    {
        $this->assertTrue(OfferDomainPolicy::canBeSeenBy(ownerId: 5, userId: 5, managesAll: false));
        $this->assertTrue(OfferDomainPolicy::canBeSeenBy(ownerId: 5, userId: 9, managesAll: true));
        $this->assertFalse(OfferDomainPolicy::canBeSeenBy(ownerId: 5, userId: 9, managesAll: false));
    }

    public function test_a_new_photo_must_sit_in_the_uploaders_folder_and_an_existing_one_may_stay(): void
    {
        $this->assertTrue(OfferDomainPolicy::photosAreFromUploader(['offers/public/3/a.jpg'], [], 'offers/public/3'));
        $this->assertTrue(OfferDomainPolicy::photosAreFromUploader(['offers/public/9/old.jpg'], ['offers/public/9/old.jpg'], 'offers/public/3'));
        $this->assertFalse(OfferDomainPolicy::photosAreFromUploader(['offers/public/9/other.jpg'], ['offers/public/9/old.jpg'], 'offers/public/3'));
        $this->assertFalse(OfferDomainPolicy::photosAreFromUploader(['offers/public/30/a.jpg'], [], 'offers/public/3'));
    }

    public function test_the_upload_folders_belong_to_the_uploader_and_the_kind(): void
    {
        $this->assertSame('offers/public/3', OfferDomainPolicy::publicPhotoDirectory(3));
        $this->assertSame('offers/private/3', OfferDomainPolicy::privatePhotoDirectory(3));
    }
}
