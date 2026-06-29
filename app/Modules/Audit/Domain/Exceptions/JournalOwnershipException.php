<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class JournalOwnershipException extends AuthorizationException {}
