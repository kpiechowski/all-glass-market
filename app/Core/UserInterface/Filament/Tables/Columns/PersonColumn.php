<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Support\Str;

/**
 * A composite "person" cell (D-13, template A): initials, the name with text indicators after
 * it, and the e-mail below. The name is the column state, so `->sortable()` and `->searchable()`
 * work as on a TextColumn of the same name.
 *
 *   (AK)  Anna Kowalska  [⚑ Zmiana hasła]
 *         anna.kowalska@szyby-kowalski.pl
 *
 * Flags take the shape of `RecordCardColumn::flags()`: an enum with `HasLabel` or an array
 * `['label' => …, 'color' => …, 'icon' => …]`; the colour defaults to `warning`.
 */
class PersonColumn extends ViewColumn
{
    protected string $view = 'core::filament.tables.person-cell';

    protected string|Closure|null $email = null;

    /** @var array<mixed>|Closure */
    protected array|Closure $personFlags = [];

    protected bool|Closure $showsInitials = true;

    public function email(string|Closure|null $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function flags(array|Closure $flags): static
    {
        $this->personFlags = $flags;

        return $this;
    }

    public function initials(bool|Closure $condition = true): static
    {
        $this->showsInitials = $condition;

        return $this;
    }

    public function getPersonName(): string
    {
        return (string) $this->getState();
    }

    public function getEmail(): ?string
    {
        $email = $this->evaluate($this->email);

        return filled($email) ? (string) $email : null;
    }

    /** @return list<array{label: string, color: string, icon: string|\BackedEnum|null}> */
    public function getPersonFlags(): array
    {
        return RecordCardColumn::normaliseMarks($this->evaluate($this->personFlags), 'warning');
    }

    public function showsInitials(): bool
    {
        return (bool) $this->evaluate($this->showsInitials);
    }

    /** "Anna Kowalska" → "AK"; one word → its first letter. */
    public function getInitials(): string
    {
        return Str::of($this->getPersonName())
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
