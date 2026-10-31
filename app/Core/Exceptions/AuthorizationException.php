<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class AuthorizationException extends DomainException
{
    public function __construct()
    {
        parent::__construct('core::common.unauthorized');
    }
}
