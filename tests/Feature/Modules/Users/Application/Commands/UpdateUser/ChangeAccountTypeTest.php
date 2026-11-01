<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\UpdateUser;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\Events\AccountTypeChanged;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-02, FR-17..FR-21, RULE-04/05/07. */
class ChangeAccountTypeTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, UserModel $target, ?string $type, bool $accepted = true, string $name = 'Nowe Imie', ?string $nip = '5252525252'): void
    {
        app(CommandBus::class)->send(new UpdateUserCommand(
            $actor, $target->id, $name, $target->email, companyNip: $nip, companyAddress: 'ul. Szklana 1',
            accountType: $type, sellerDeclarationAccepted: $accepted,
        ));
    }

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-01 10:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_a_user_switches_to_company_with_the_declaration(): void
    {
        Event::fake([AccountTypeChanged::class]);
        $user = UserModel::factory()->create();

        $this->send(Actor::user($user->id), $user, 'company');

        $fresh = $user->fresh();
        $this->assertSame(AccountType::Company, $fresh->account_type);
        $this->assertSame('V1', $fresh->seller_declaration_version);
        $this->assertEquals(now()->toDateTimeString(), $fresh->seller_declaration_accepted_at->toDateTimeString());
        $this->assertEquals(now()->toDateTimeString(), $fresh->account_type_changed_at->toDateTimeString());
        Event::assertDispatched(AccountTypeChanged::class, fn (AccountTypeChanged $e): bool => $e->userId === $user->id && $e->accountType === 'company');
    }

    public function test_a_company_user_switches_back_to_private_and_the_acceptance_is_cleared(): void
    {
        Event::fake([AccountTypeChanged::class]);
        $user = UserModel::factory()->company()->accountTypeChangedAt(now()->subHour())->create();

        $this->send(Actor::user($user->id), $user, 'private', false);

        $fresh = $user->fresh();
        $this->assertSame(AccountType::Private, $fresh->account_type);
        $this->assertNull($fresh->seller_declaration_accepted_at);
        $this->assertNull($fresh->seller_declaration_version);
        Event::assertDispatched(AccountTypeChanged::class, fn (AccountTypeChanged $e): bool => $e->accountType === 'private');
    }

    public function test_the_event_is_dispatched_only_after_the_save(): void
    {
        $user = UserModel::factory()->create();
        $typeSeenByListener = null;
        Event::listen(AccountTypeChanged::class, function () use ($user, &$typeSeenByListener): void {
            $typeSeenByListener = UserModel::query()->find($user->id)->account_type;
        });

        $this->send(Actor::user($user->id), $user, 'company');

        $this->assertSame(AccountType::Company, $typeSeenByListener);
    }

    public function test_company_without_the_declaration_is_refused_and_nothing_happens(): void
    {
        Event::fake([AccountTypeChanged::class]);
        $user = UserModel::factory()->create(['name' => 'Stare']);

        try {
            $this->send(Actor::user($user->id), $user, 'company', false);
            $this->fail('Expected a refusal.');
        } catch (UserRefused $e) {
            $this->assertSame('users::exceptions.declaration_not_accepted', $e->translationKey);
        }

        $fresh = $user->fresh();
        $this->assertSame('Stare', $fresh->name);
        $this->assertSame(AccountType::Private, $fresh->account_type);
        $this->assertNull($fresh->account_type_changed_at);
        Event::assertNotDispatched(AccountTypeChanged::class);
    }

    public function test_company_without_a_nip_is_refused(): void
    {
        $user = UserModel::factory()->create();

        $this->expectException(UserRefused::class);

        $this->send(Actor::user($user->id), $user, 'company', true, nip: null);
    }

    public function test_a_locked_toggle_is_refused_with_no_event(): void
    {
        Event::fake([AccountTypeChanged::class]);
        $user = UserModel::factory()->company()->accountTypeChangedAt(now()->subMinutes(10))->create();

        try {
            $this->send(Actor::user($user->id), $user, 'private', false);
            $this->fail('Expected a refusal.');
        } catch (UserRefused $e) {
            $this->assertSame('users::exceptions.account_type_locked', $e->translationKey);
            $this->assertSame(['time' => '10:20'], $e->parameters);
        }

        $this->assertSame(AccountType::Company, $user->fresh()->account_type);
        Event::assertNotDispatched(AccountTypeChanged::class);
    }

    public function test_the_lock_does_not_stop_a_profile_save_that_keeps_the_type(): void
    {
        $user = UserModel::factory()->company()->accountTypeChangedAt(now()->subMinutes(1))->create();

        $this->send(Actor::user($user->id), $user, 'company', true, 'Inne Imie');

        $this->assertSame('Inne Imie', $user->fresh()->name);
    }

    public function test_only_the_user_themself_may_change_the_type(): void
    {
        Event::fake([AccountTypeChanged::class]);
        $admin = UserModel::factory()->admin()->create();
        $superAdmin = UserModel::factory()->superAdmin()->create();
        $user = UserModel::factory()->create();

        foreach ([Actor::user($admin->id), Actor::user($superAdmin->id), Actor::system('import')] as $actor) {
            try {
                $this->send($actor, $user, 'company');
                $this->fail('Expected a refusal.');
            } catch (UserRefused $e) {
                $this->assertSame('users::exceptions.only_owner_changes_account_type', $e->translationKey);
            }
        }

        $this->assertSame(AccountType::Private, $user->fresh()->account_type);
        Event::assertNotDispatched(AccountTypeChanged::class);
    }

    public function test_an_admin_edit_without_a_type_keeps_it(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->company()->create();

        $this->send(Actor::user($admin->id), $user, null);

        $this->assertSame(AccountType::Company, $user->fresh()->account_type);
    }

    public function test_a_company_switching_to_private_may_clear_nip_and_address_in_the_same_save(): void
    {
        Event::fake([AccountTypeChanged::class]);
        $user = UserModel::factory()->company()->accountTypeChangedAt(now()->subHour())->create();

        app(CommandBus::class)->send(new UpdateUserCommand(
            Actor::user($user->id), $user->id, 'Jan', $user->email, accountType: 'private',
        ));

        $fresh = $user->fresh();
        $this->assertSame(AccountType::Private, $fresh->account_type);
        $this->assertNull($fresh->company_nip);
        $this->assertNull($fresh->company_address);
        $this->assertNull($fresh->seller_declaration_accepted_at);
        Event::assertDispatched(AccountTypeChanged::class, fn (AccountTypeChanged $e): bool => $e->accountType === 'private');
    }

    public function test_a_company_staying_company_cannot_clear_the_nip(): void
    {
        $user = UserModel::factory()->company()->create();

        try {
            $this->send(Actor::user($user->id), $user, 'company', true, nip: null);
            $this->fail('Expected a refusal.');
        } catch (UserRefused $e) {
            $this->assertSame('users::exceptions.company_data_missing', $e->translationKey);
        }

        $this->assertSame('5252525252', $user->fresh()->company_nip);
    }
}
