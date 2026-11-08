<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Core\Exceptions\DomainException;

/** The test module's own refusal, shown verbatim (the key is plain text, which the translator returns unchanged). */
final class ThingRefused extends DomainException {}
