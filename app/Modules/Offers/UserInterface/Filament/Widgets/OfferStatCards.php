<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Widgets;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

/**
 * FR-32, template F (a sketch): the seller's own counts above "Moje oferty", as custom cards with an icon
 * (own view and `stat-card` CSS, not Filament's stats widget). A count turns `warning` only when it asks
 * for attention: Pending waits for an admin. It is used by the list page alone, never on the dashboard.
 */
class OfferStatCards extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'offers::filament.widgets.offer-stat-cards';

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $userId = (int) CurrentActor::get()->id;
        $queries = app(OfferQueries::class);
        $counts = $queries->countsByStatusOf($userId);

        return [
            'cards' => [
                $this->card('active', Heroicon::OutlinedCheckCircle, $counts[OfferStatus::Active->value] ?? 0, 'success', OfferResource::getUrl('index', ['tab' => 'active'])),
                $this->card('pending', Heroicon::OutlinedClock, $counts[OfferStatus::Pending->value] ?? 0, 'warning', OfferResource::getUrl('index', ['tab' => 'pending'])),
                $this->card('reserved', Heroicon::OutlinedBookmark, $counts[OfferStatus::Reserved->value] ?? 0, 'gray', OfferResource::getUrl('index', ['tab' => 'reserved'])),
                $this->card('sold_this_month', Heroicon::OutlinedBanknotes, $queries->soldInMonthOf($userId, now()), 'gray', OfferResource::getUrl('index', ['tab' => 'sold'])),
            ],
        ];
    }

    /** @return array{label: string, icon: Heroicon, value: int, variant: string, url: string} */
    private function card(string $key, Heroicon $icon, int $value, string $variant, string $url): array
    {
        return [
            'label' => __('offers::widgets.stat_cards.'.$key),
            'icon' => $icon,
            'value' => $value,
            'variant' => $value === 0 ? 'gray' : $variant,
            'url' => $url,
        ];
    }
}
