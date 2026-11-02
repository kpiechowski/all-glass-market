<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-13: "Edytuj profil" in the user menu leads to "Mój profil". */
class UserMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_user_menu_offers_edit_profile_leading_to_my_profile(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(UserModel::factory()->create());

        $profile = Filament::getCurrentPanel()->getUserMenuItems()['profile'];

        $this->assertSame('Edytuj profil', $profile->getLabel());
        $this->assertSame(EditProfile::getUrl(), $profile->getUrl());
    }
}
