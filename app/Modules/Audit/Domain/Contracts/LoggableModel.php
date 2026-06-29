<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Contracts;

interface LoggableModel
{
    public function getLoggableTitle(): string;

    public function getLoggableResourceName(): string;

    public function getLoggableUrl(): ?string;

    public function getLoggableIcon(): ?string;
}
