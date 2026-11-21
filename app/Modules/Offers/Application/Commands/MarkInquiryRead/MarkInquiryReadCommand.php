<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\MarkInquiryRead;

use App\Core\Domain\Actor;

/** FR-22: the owner opened the Inquiry. */
final readonly class MarkInquiryReadCommand
{
    public function __construct(
        public Actor $actor,
        public int $inquiryId,
    ) {}
}
