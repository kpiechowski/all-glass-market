<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Enums;

/** How a raw audited value is turned into readable text at display (FR-07, D-35). */
enum ValuePresenterKind: string
{
    case Plain = 'plain';
    case Reference = 'reference';
    case Enum = 'enum';
    case Money = 'money';
    case Date = 'date';
    case List = 'list';
}
