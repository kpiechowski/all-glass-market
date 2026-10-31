<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Exceptions;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainException;
use PHPUnit\Framework\TestCase;

class AuthorizationExceptionTest extends TestCase
{
    public function test_it_is_a_domain_exception_so_the_ui_can_show_it(): void
    {
        $exception = new AuthorizationException;

        $this->assertInstanceOf(DomainException::class, $exception);
        $this->assertSame('core::common.unauthorized', $exception->translationKey);
    }
}
