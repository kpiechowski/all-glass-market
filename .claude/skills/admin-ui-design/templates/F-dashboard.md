# F — Dashboard (sketch)

A page of widgets: statistics, recent activity, things to do. This template is a sketch until the first real dashboard; when you build one, stop and agree the layout with the user, then complete this file.

## Skeleton

```php
class SalesDashboard extends Page
{
    protected function getHeaderWidgets(): array
    {
        return [SalesStatsWidget::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 4];
    }
}
```

```php
class SalesStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $offers = app(OfferQueries::class);   // reads through {X}Queries

        return [
            Stat::make(__('offers::widgets.stats.active'), $offers->activeCount()),
            Stat::make(__('offers::widgets.stats.awaiting_review'), $offers->awaitingReviewCount()),
        ];
    }
}
```

Widgets live in `Filament/Widgets/` (`filament-module-wiring`).

## Rules so far

- Layout (c), free grid of boxes, is allowed here, with responsive columns.
- Colour marks state: a stat turns `warning` or `danger` only when it needs action.
- A count that needs no action carries no badge or alarm colour.
