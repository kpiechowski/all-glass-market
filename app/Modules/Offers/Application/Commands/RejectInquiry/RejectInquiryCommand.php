<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\RejectInquiry;

use App\Core\Domain\Actor;

/** FR-22: the owner rejects an Inquiry. */
final readonly class RejectInquiryCommand
{
    public function __construct(
        public Actor $actor,
        public int $inquiryId,
    ) {}
}
