<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Exceptions;

use App\Core\Exceptions\DomainException;

final class ChangeRefused extends DomainException
{
    public static function nothingChanged(): self
    {
        return new self('audit::exceptions.change_nothing_changed');
    }

    public static function invalidActor(): self
    {
        return new self('audit::exceptions.change_invalid_actor');
    }

    public static function invalidRestoreMarker(): self
    {
        return new self('audit::exceptions.change_invalid_restore_marker');
    }

    public static function restoreNotFound(): self
    {
        return new self('audit::exceptions.restore_not_found');
    }

    public static function restoreNotOffered(): self
    {
        return new self('audit::exceptions.restore_not_offered');
    }

    public static function restoreNothingRestorable(): self
    {
        return new self('audit::exceptions.restore_nothing_restorable');
    }

    public static function restoreRecordGone(): self
    {
        return new self('audit::exceptions.restore_record_gone');
    }
}
