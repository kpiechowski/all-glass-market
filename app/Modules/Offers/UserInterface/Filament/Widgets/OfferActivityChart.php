<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Widgets;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

/**
 * FR-36, FR-37, template F (a sketch): a line chart of the last twelve months, "Dodane do sprzedaży" and
 * "Sprzedane", counts only. A seller gets their own Offers on "Moje oferty", an admin all Offers above
 * "Wszystkie oferty" with a seller filter. The seller filter is an admin's alone: whatever `$sellerId` a
 * seller's browser sends, the numbers stay their own. Own SVG and `activity-chart` CSS (the dataviz rules:
 * 2 px lines, legend, hover readout, table view), not Filament's chart widget. Used by the list page alone.
 */
class OfferActivityChart extends Widget
{
    private const int WIDTH = 640;

    private const int HEIGHT = 240;

    private const int PAD_LEFT = 32;

    private const int PAD_RIGHT = 16;

    private const int PAD_TOP = 12;

    private const int PAD_BOTTOM = 28;

    protected static bool $isDiscovered = false;

    protected string $view = 'offers::filament.widgets.offer-activity-chart';

    protected int|string|array $columnSpan = 'full';

    /** The admin's seller filter; null is every seller. */
    public ?int $sellerId = null;

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $queries = app(OfferQueries::class);
        $managesAll = CurrentActor::can(OffersPermission::ManageAll);
        $ownerId = $managesAll ? ($this->sellerId ?: null) : (int) auth()->id();
        $months = $queries->monthlyActivity(now(), $ownerId);

        $ceiling = $this->ceiling(max(1, ...array_map(fn (array $month): int => max($month['added'], $month['sold']), $months)));
        $step = $ceiling / 4;
        $plotWidth = self::WIDTH - self::PAD_LEFT - self::PAD_RIGHT;
        $plotHeight = self::HEIGHT - self::PAD_TOP - self::PAD_BOTTOM;
        $x = fn (int $index): float => round(self::PAD_LEFT + $plotWidth * $index / 11, 1);
        $y = fn (int $value): float => round(self::PAD_TOP + $plotHeight * (1 - $value / $ceiling), 1);

        $series = [];

        foreach (['added', 'sold'] as $key) {
            $points = [];

            foreach ($months as $index => $month) {
                $points[] = ['x' => $x($index), 'y' => $y($month[$key]), 'value' => $month[$key]];
            }

            $series[$key] = [
                'label' => __('offers::widgets.activity_chart.series.'.$key),
                'points' => $points,
                'path' => 'M'.implode(' L', array_map(fn (array $point): string => $point['x'].' '.$point['y'], $points)),
            ];
        }

        return [
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'plotLeft' => self::PAD_LEFT,
            'plotRight' => self::WIDTH - self::PAD_RIGHT,
            'plotTop' => self::PAD_TOP,
            'plotBottom' => self::HEIGHT - self::PAD_BOTTOM,
            'bandWidth' => round($plotWidth / 11, 1),
            'ticks' => array_map(fn (int $tick): array => ['value' => $tick * $step, 'y' => $y((int) ($tick * $step))], range(0, 4)),
            'months' => array_map(fn (array $month, int $index): array => [
                'x' => $x($index),
                'short' => $this->shortLabel($month['month']),
                'long' => $this->longLabel($month['month']),
                'added' => $month['added'],
                'sold' => $month['sold'],
            ], $months, array_keys($months)),
            'series' => $series,
            'sellers' => $managesAll ? $queries->sellersWithOffers() : [],
            'managesAll' => $managesAll,
            'chevron' => Heroicon::OutlinedChevronDown,
        ];
    }

    /** The y-axis top: a multiple of four steps of 1, 2, 5 or 10 times a power of ten, so the four gaps are whole numbers. */
    private function ceiling(int $max): int
    {
        $magnitude = 1;

        while (true) {
            foreach ([1, 2, 5] as $factor) {
                $step = $factor * $magnitude;

                if ($step * 4 >= $max) {
                    return $step * 4;
                }
            }

            $magnitude *= 10;
        }
    }

    private function shortLabel(string $month): string
    {
        [$year, $number] = explode('-', $month);

        return __('offers::widgets.activity_chart.months.'.(int) $number).($number === '01' ? ' '.substr($year, 2) : '');
    }

    private function longLabel(string $month): string
    {
        [$year, $number] = explode('-', $month);

        return __('offers::widgets.activity_chart.months.'.(int) $number).' '.$year;
    }
}
