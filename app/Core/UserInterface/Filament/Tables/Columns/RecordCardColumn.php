<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Tables\Columns;

use BackedEnum;
use Closure;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The mobile card of a list table (D-12, template A): the one cell a list shows below `md`.
 * Every regular column is `->visibleFrom('md')`; this one hides from `md`, and the theme turns
 * each row into a card below `md` (`filament/tables/mobile.css`).
 *
 *   ┌──────────────────────────────────────────┐
 *   │ Title                          [status]  │
 *   │ subtitle                                 │
 *   │ ⚑ flag   [badge] [badge]   meta   meta   │
 *   └──────────────────────────────────────────┘
 *
 * Every part takes a closure with `$record`. A badge, status or flag is an enum with
 * `HasLabel` (+ `HasColor`, `HasIcon`) or an array `['label' => …, 'color' => …, 'icon' => …]`.
 * Categories stay neutral (`gray`), states carry their colour (admin-ui-design, Colour = state).
 */
class RecordCardColumn extends ViewColumn
{
    protected string $view = 'core::filament.tables.record-card';

    protected string|Closure|null $cardTitle = null;

    protected string|Closure|null $cardSubtitle = null;

    protected array|BackedEnum|Closure|null $cardStatus = null;

    /** @var array<mixed>|Closure */
    protected array|Closure $cardBadges = [];

    /** @var array<mixed>|Closure */
    protected array|Closure $cardFlags = [];

    /** @var array<string|Htmlable|null>|Closure */
    protected array|Closure $cardMeta = [];

    public static function make(?string $name = 'card'): static
    {
        return parent::make($name ?? 'card');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenFrom('md');
        $this->extraCellAttributes(['class' => 'ta-card-cell'], merge: true);
        $this->extraHeaderAttributes(['class' => 'ta-card-header'], merge: true);
    }

    public function title(string|Closure $title): static
    {
        $this->cardTitle = $title;

        return $this;
    }

    public function subtitle(string|Closure|null $subtitle): static
    {
        $this->cardSubtitle = $subtitle;

        return $this;
    }

    /** One state badge in the top-right corner (Aktywny, Opublikowana). */
    public function status(array|BackedEnum|Closure|null $status): static
    {
        $this->cardStatus = $status;

        return $this;
    }

    /** Neutral category badges in the bottom line (Typ konta, Rola). */
    public function badges(array|Closure $badges): static
    {
        $this->cardBadges = $badges;

        return $this;
    }

    /** Indicators that a row needs attention ("Zmiana hasła"), icon + short label. */
    public function flags(array|Closure $flags): static
    {
        $this->cardFlags = $flags;

        return $this;
    }

    /** Short muted facts in the bottom line ("od 12.03.2024", "3 oferty"). Empty values are skipped. */
    public function meta(array|Closure $meta): static
    {
        $this->cardMeta = $meta;

        return $this;
    }

    public function getCardTitle(): string
    {
        return (string) $this->evaluate($this->cardTitle);
    }

    public function getCardSubtitle(): ?string
    {
        $subtitle = $this->evaluate($this->cardSubtitle);

        return filled($subtitle) ? (string) $subtitle : null;
    }

    /** @return array{label: string, color: string, icon: string|BackedEnum|null}|null */
    public function getCardStatus(): ?array
    {
        $status = $this->evaluate($this->cardStatus);

        return filled($status) ? self::normaliseMark($status, 'gray') : null;
    }

    /** @return list<array{label: string, color: string, icon: string|BackedEnum|null}> */
    public function getCardBadges(): array
    {
        return self::normaliseMarks($this->evaluate($this->cardBadges), 'gray');
    }

    /** @return list<array{label: string, color: string, icon: string|BackedEnum|null}> */
    public function getCardFlags(): array
    {
        return self::normaliseMarks($this->evaluate($this->cardFlags), 'warning');
    }

    /** @return list<string|Htmlable> */
    public function getCardMeta(): array
    {
        return array_values(array_filter((array) $this->evaluate($this->cardMeta), filled(...)));
    }

    /**
     * @param  array<mixed>|null  $marks
     * @return list<array{label: string, color: string, icon: string|BackedEnum|null}>
     */
    public static function normaliseMarks(?array $marks, string $defaultColor): array
    {
        return array_values(array_map(
            fn (mixed $mark): array => self::normaliseMark($mark, $defaultColor),
            array_filter($marks ?? [], filled(...)),
        ));
    }

    /** @return array{label: string, color: string, icon: string|BackedEnum|null} */
    public static function normaliseMark(mixed $mark, string $defaultColor): array
    {
        if ($mark instanceof HasLabel) {
            $color = $mark instanceof HasColor ? $mark->getColor() : null;

            return [
                'label' => (string) $mark->getLabel(),
                'color' => is_string($color) ? $color : $defaultColor,
                'icon' => $mark instanceof HasIcon ? $mark->getIcon() : null,
            ];
        }

        if (is_array($mark)) {
            return [
                'label' => (string) ($mark['label'] ?? ''),
                'color' => (string) ($mark['color'] ?? $defaultColor),
                'icon' => $mark['icon'] ?? null,
            ];
        }

        return ['label' => (string) $mark, 'color' => $defaultColor, 'icon' => null];
    }
}
