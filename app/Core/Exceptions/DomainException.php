<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

/**
 * A business rule refusal. The UI translates the key and shows it to the user.
 */
abstract class DomainException extends Exception
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $translationKey,
        public readonly array $parameters = [],
    ) {
        parent::__construct($translationKey);
    }
}
