<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_the_detail_shows_contact_company_account_type_role_and_shipping_text(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->create([
            'name' => 'Anna Nowak', 'phone' => '600700800', 'company_name' => 'Szklarnia', 'company_nip' => '1234567890',
            'shipment_address' => 'Magazyn 5', 'city' => 'Łódź', 'city_code' => '90-001', 'shipping_note' => '<p>Kurier</p>',
        ]);

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertSee(['Anna Nowak', '600700800', 'Szklarnia', '1234567890', 'Magazyn 5', 'Łódź', '90-001', 'Kurier', 'Prywatne', 'Użytkownik']);
    }

    public function test_an_admin_sees_a_super_admin_read_only(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $super = UserModel::factory()->superAdmin()->create();

        Livewire::test(ViewUser::class, ['record' => $super->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('edit');
    }
}
