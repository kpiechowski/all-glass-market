<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\ModuleApi;

use App\Modules\Audit\Domain\Enums\ValuePresenterKind;
use Closure;

/**
 * How a module wants one audited value shown (FR-07):
 *
 *     ValuePresenter::reference(ProducerModel::class, 'name')   // id -> readable name
 *     ValuePresenter::enum(OfferStatus::class)                  // case value -> its label
 *     ValuePresenter::money()   // a number of zloty, shown "1 200 zł"
 *     ValuePresenter::moneyInGrosze()   // an integer number of grosze, shown "123,45 zł"
 *     ValuePresenter::date()    // an ISO date or date-time
 *     ValuePresenter::list()    // an array, shown comma separated
 *     ValuePresenter::fileCount()   // a list of stored paths (an array or its JSON), shown as "3 pliki"
 *     ValuePresenter::boolean()   // 0/1 -> tak / nie
 *     ValuePresenter::translated('users::permissions.')   // 'users.manage' -> the translation at prefix + 'users_manage'; raw when missing
 *     ValuePresenter::resolved(fn (mixed $raw): ?string => ...)   // the module names the value itself, for example an id of another module's record
 *     ValuePresenter::plain()   // as stored
 *
 * The entry always keeps the raw value; this only decides the display.
 */
final readonly class ValuePresenter
{
    /**
     * @param  string|null  $target  the referenced model or the enum class, or the translation key prefix
     */
    private function __construct(
        public ValuePresenterKind $kind,
        public ?string $target = null,
        public ?string $nameAttribute = null,
        public ?Closure $resolver = null,
    ) {}

    /**
     * @param  class-string  $modelClass
     */
    public static function reference(string $modelClass, string $nameAttribute = 'name'): self
    {
        return new self(ValuePresenterKind::Reference, $modelClass, $nameAttribute);
    }

    /**
     * @param  class-string  $enumClass
     */
    public static function enum(string $enumClass): self
    {
        return new self(ValuePresenterKind::Enum, $enumClass);
    }

    public static function money(): self
    {
        return new self(ValuePresenterKind::Money);
    }

    public static function moneyInGrosze(): self
    {
        return new self(ValuePresenterKind::MoneyGrosze);
    }

    public static function date(): self
    {
        return new self(ValuePresenterKind::Date);
    }

    public static function list(): self
    {
        return new self(ValuePresenterKind::List);
    }

    /** A list of stored file paths: the entry shows how many files there are, not their random names. */
    public static function fileCount(): self
    {
        return new self(ValuePresenterKind::FileCount);
    }

    /** A flag stored as 0/1 (or true/false), shown "tak" / "nie". */
    public static function boolean(): self
    {
        return new self(ValuePresenterKind::Boolean);
    }

    /** The value, dots turned to underscores, is looked up as a translation key under `$keyPrefix`. */
    public static function translated(string $keyPrefix): self
    {
        return new self(ValuePresenterKind::Translated, $keyPrefix);
    }

    /**
     * @param  Closure(mixed): ?string  $resolver  the raw value to its text; null shows the raw value
     */
    public static function resolved(Closure $resolver): self
    {
        return new self(ValuePresenterKind::Resolved, resolver: $resolver);
    }

    public static function plain(): self
    {
        return new self(ValuePresenterKind::Plain);
    }
}
