<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class PermissionDeniedException extends AuthorizationException {}
