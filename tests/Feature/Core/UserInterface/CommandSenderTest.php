<?php

declare(strict_types=1);

namespace Tests\Feature\Core\UserInterface;

use App\Core\Exceptions\AuthorizationException;
use App\Core\UserInterface\CommandSender;
use Ecotone\Modelling\CommandBus;
use Filament\Notifications\NotificationMailbox;
use Filament\Support\Exceptions\Halt;
use RuntimeException;
use stdClass;
use Tests\TestCase;

class CommandSenderTest extends TestCase
{
    public function test_it_sends_the_command_on_the_bus_and_returns_the_result(): void
    {
        $command = new stdClass;
        $this->mock(CommandBus::class)->shouldReceive('send')->once()->with($command)->andReturn(15);

        $this->assertSame(15, CommandSender::send($command));
    }

    public function test_a_domain_exception_becomes_a_danger_notification_and_halts(): void
    {
        $this->mock(CommandBus::class)->shouldReceive('send')->andThrow(new AuthorizationException);
        $mailbox = $this->spy(NotificationMailbox::class);

        try {
            CommandSender::send(new stdClass);
            $this->fail('The action was not halted');
        } catch (Halt) {
            $mailbox->shouldHaveReceived('push')->once()->withArgs(
                fn (array $notification): bool => $notification['status'] === 'danger'
                    && $notification['title'] === __('core::common.unauthorized')
            );
        }
    }

    public function test_other_exceptions_pass_through_untouched(): void
    {
        $this->mock(CommandBus::class)->shouldReceive('send')->andThrow(new RuntimeException('boom'));
        $mailbox = $this->spy(NotificationMailbox::class);

        try {
            CommandSender::send(new stdClass);
            $this->fail('The exception was swallowed');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
            $mailbox->shouldNotHaveReceived('push');
        }
    }
}
