<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Exceptions;

use App\Core\Exceptions\DomainException;
use PHPUnit\Framework\TestCase;

class DomainExceptionTest extends TestCase
{
    public function test_it_carries_a_translation_key_and_parameters(): void
    {
        $exception = new class('offers::exceptions.no_price', ['offer' => 12]) extends DomainException {};

        $this->assertSame('offers::exceptions.no_price', $exception->translationKey);
        $this->assertSame(['offer' => 12], $exception->parameters);
        $this->assertSame('offers::exceptions.no_price', $exception->getMessage());
    }

    public function test_parameters_default_to_empty(): void
    {
        $exception = new class('offers::exceptions.no_price') extends DomainException {};

        $this->assertSame([], $exception->parameters);
    }
}
