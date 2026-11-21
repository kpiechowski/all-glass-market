<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Widgets;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use Filament\Support\Facades\FilamentColor;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * FR-36, FR-37: a line chart (Filament's `ChartWidget`) of the last twelve months, "Dodane do sprzedaży" and
 * "Sprzedane", counts only. A seller gets their own Offers on "Moje oferty", an admin all Offers above
 * "Wszystkie oferty" with a seller filter. The filter is an admin's alone: a seller has none, and whatever
 * `$filter` a seller's browser sends, the numbers stay their own. Used by the list page alone.
 */
class OfferActivityChart extends ChartWidget
{
    private const string ALL_SELLERS = 'all';

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected bool $isCollapsible = true;

    protected ?string $maxHeight = '280px';

    /** The admin's seller filter: `all` or a seller id. */
    public ?string $filter = self::ALL_SELLERS;

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('offers::widgets.activity_chart.title');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('offers::widgets.activity_chart.subtitle');
    }

    /** @return array<string, string>|null */
    protected function getFilters(): ?array
    {
        if (! CurrentActor::can(OffersPermission::ManageAll)) {
            return null;
        }

        $sellers = [];

        foreach (app(OfferQueries::class)->sellersWithOffers() as $id => $name) {
            $sellers[(string) $id] = $name;
        }

        return [self::ALL_SELLERS => __('offers::widgets.activity_chart.all_sellers')] + $sellers;
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $months = app(OfferQueries::class)->monthlyActivity(now(), $this->ownerId());

        return [
            'labels' => array_map(fn (array $month): string => $this->label($month['month']), $months),
            'datasets' => [
                $this->dataset('added', array_column($months, 'added'), 'primary'),
                $this->dataset('sold', array_column($months, 'sold'), 'info'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }

    /** A seller's own id always; an admin's pick only when it is a seller who has Offers. */
    private function ownerId(): ?int
    {
        if (! CurrentActor::can(OffersPermission::ManageAll)) {
            return (int) auth()->id();
        }

        $sellerId = (int) $this->filter;

        return array_key_exists($sellerId, app(OfferQueries::class)->sellersWithOffers()) ? $sellerId : null;
    }

    /**
     * @param  array<int, int>  $counts
     * @return array<string, mixed>
     */
    private function dataset(string $key, array $counts, string $color): array
    {
        $shade = FilamentColor::getColor($color)[500] ?? null;

        return [
            'label' => __('offers::widgets.activity_chart.series.'.$key),
            'data' => array_values($counts),
            'borderColor' => $shade,
            'backgroundColor' => $shade,
        ];
    }

    private function label(string $month): string
    {
        [$year, $number] = explode('-', $month);

        return __('offers::widgets.activity_chart.months.'.(int) $number).($number === '01' ? ' '.substr($year, 2) : '');
    }
}
