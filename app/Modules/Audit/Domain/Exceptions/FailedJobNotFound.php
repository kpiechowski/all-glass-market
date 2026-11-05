<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

/** The failed job is gone: retried or removed already (FR-18). */
final class FailedJobNotFound extends DomainException
{
    public static function make(): self
    {
        return new self('audit::exceptions.failed_job_not_found');
    }
}
