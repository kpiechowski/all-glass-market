<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use BackedEnum;

/**
 * Implemented by each module's permission enum, e.g. OfferPermission.
 */
interface Permission extends BackedEnum {}
