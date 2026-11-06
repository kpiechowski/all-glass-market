<?php

declare(strict_types=1);

namespace Tests\Unit\Core\UserInterface\Filament\Tables\Columns;

use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use PHPUnit\Framework\TestCase;

/** D-12: a card mark is an enum with HasLabel (+ HasColor) or an array, normalised for the view. */
class RecordCardColumnTest extends TestCase
{
    public function test_an_enum_mark_takes_its_label_and_colour(): void
    {
        $this->assertSame(
            ['label' => 'Aktywny', 'color' => 'success', 'icon' => null],
            RecordCardColumn::normaliseMark(CardTestStatus::Active, 'gray'),
        );
    }

    public function test_an_array_mark_falls_back_to_the_default_colour(): void
    {
        $this->assertSame(
            ['label' => 'Zmiana hasła', 'color' => 'warning', 'icon' => Heroicon::OutlinedKey],
            RecordCardColumn::normaliseMark(['label' => 'Zmiana hasła', 'icon' => Heroicon::OutlinedKey], 'warning'),
        );
    }

    public function test_a_string_mark_is_a_neutral_label_and_empty_marks_are_dropped(): void
    {
        $this->assertSame(
            [['label' => 'Firma', 'color' => 'gray', 'icon' => null]],
            RecordCardColumn::normaliseMarks(['Firma', null, ''], 'gray'),
        );
    }
}

enum CardTestStatus: string implements HasColor, HasLabel
{
    case Active = 'active';

    public function getLabel(): string
    {
        return 'Aktywny';
    }

    public function getColor(): string
    {
        return 'success';
    }
}
