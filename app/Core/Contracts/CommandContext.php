<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Ambient facts about the command being handled that travel to the commands
 * it sends and to the RecordChanged events they emit. A value set around a
 * callback is visible to every command frame opened inside it and ends with
 * the callback. A listener reads it from `RecordChanged::$context`.
 */
interface CommandContext
{
    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function with(string $key, mixed $value, callable $callback): mixed;
}
