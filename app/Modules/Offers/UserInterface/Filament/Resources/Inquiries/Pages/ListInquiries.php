<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Pages;

use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\InquiryResource;
use Filament\Resources\Pages\ListRecords;

/** Template A (list): no header action, the page only reads. */
class ListInquiries extends ListRecords
{
    protected static string $resource = InquiryResource::class;
}
