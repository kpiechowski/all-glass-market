<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Http;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\DeactivateUser\DeactivateUserCommand;
use App\Modules\Users\Domain\Events\ImpersonationEnded;
use App\Modules\Users\Domain\Events\ImpersonationStarted;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use App\Modules\Users\UserInterface\Livewire\ImpersonationBanner;
use Ecotone\Modelling\CommandBus;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

/** WF-06, FR-28, RULE-10 through the panel; the §11 deactivation cases. */
class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
    }

    public function test_the_action_is_offered_only_for_a_permitted_lower_active_target(): void
    {
        $holder = UserModel::factory()->admin()->create(['extra_permissions' => ['users.impersonate']]);
        $this->actingAs($holder);

        Livewire::test(ViewUser::class, ['record' => UserModel::factory()->create()->getKey()])->assertActionVisible('impersonate');
        Livewire::test(ViewUser::class, ['record' => UserModel::factory()->admin()->create()->getKey()])->assertActionHidden('impersonate');
        Livewire::test(ViewUser::class, ['record' => UserModel::factory()->superAdmin()->create()->getKey()])->assertActionHidden('impersonate');
        Livewire::test(ViewUser::class, ['record' => UserModel::factory()->deactivated()->create()->getKey()])->assertActionHidden('impersonate');
    }

    public function test_an_admin_without_the_extra_permission_has_no_action(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        Livewire::test(ViewUser::class, ['record' => UserModel::factory()->create()->getKey()])->assertActionHidden('impersonate');
    }

    public function test_a_super_admin_starts_then_returns_and_both_are_announced(): void
    {
        Event::fake([ImpersonationStarted::class, ImpersonationEnded::class]);
        $super = UserModel::factory()->superAdmin()->create();
        $target = UserModel::factory()->admin()->create();
        $this->actingAs($super);

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])->callAction('impersonate');

        $this->assertSame($target->id, auth()->id());
        Event::assertDispatched(ImpersonationStarted::class, fn ($e): bool => $e->impersonatorId === $super->id && $e->targetUserId === $target->id);

        $this->get('/admin')->assertOk()->assertSee('Wróć na swoje konto');

        Livewire::test(ImpersonationBanner::class)->call('leave');

        $this->assertSame($super->id, auth()->id());
        Event::assertDispatched(ImpersonationEnded::class, fn ($e): bool => $e->reason === 'returned' && $e->impersonatorId === $super->id);
        $this->get('/admin')->assertOk()->assertDontSee('Wróć na swoje konto');
    }

    public function test_no_action_is_offered_while_impersonating(): void
    {
        $super = UserModel::factory()->superAdmin()->create();
        $target = UserModel::factory()->admin()->create(['extra_permissions' => ['users.impersonate']]);
        $this->actingAs($super);
        Livewire::test(ViewUser::class, ['record' => $target->getKey()])->callAction('impersonate');

        Livewire::test(ViewUser::class, ['record' => UserModel::factory()->create()->getKey()])->assertActionHidden('impersonate');
    }

    public function test_a_deactivated_target_ends_the_impersonation_and_returns_the_impersonator(): void
    {
        Event::fake([ImpersonationEnded::class]);
        $super = UserModel::factory()->superAdmin()->create();
        $target = UserModel::factory()->admin()->create();
        $this->actingAs($super);
        Livewire::test(ViewUser::class, ['record' => $target->getKey()])->callAction('impersonate');

        app(CommandBus::class)->send(new DeactivateUserCommand(Actor::system('test'), $target->id));

        $this->app['auth']->forgetGuards();

        $this->get('/admin')->assertRedirect();
        $this->assertSame($super->id, auth()->id());
        Event::assertDispatched(ImpersonationEnded::class, fn ($e): bool => $e->reason === 'target_deactivated');
        $this->get('/admin')->assertOk();
    }

    public function test_a_deactivated_impersonator_ends_both_sessions(): void
    {
        Event::fake([ImpersonationEnded::class]);
        $holder = UserModel::factory()->admin()->create(['extra_permissions' => ['users.impersonate']]);
        $target = UserModel::factory()->create();
        $this->actingAs($holder);
        Livewire::test(ViewUser::class, ['record' => $target->getKey()])->callAction('impersonate');

        app(CommandBus::class)->send(new DeactivateUserCommand(Actor::system('test'), $holder->id));

        $this->app['auth']->forgetGuards();

        $this->get('/admin')->assertRedirect();
        $this->assertGuest();
        Event::assertDispatched(ImpersonationEnded::class, fn ($e): bool => $e->reason === 'impersonator_deactivated');
    }
}
