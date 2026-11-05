<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\ModuleApi;

use App\Modules\Audit\Domain\Enums\ValuePresenterKind;

/**
 * How a module wants one audited value shown (FR-07):
 *
 *     ValuePresenter::reference(ProducerModel::class, 'name')   // id -> readable name
 *     ValuePresenter::enum(OfferStatus::class)                  // case value -> its label
 *     ValuePresenter::money()   // a number of zloty, shown "1 200 zł"
 *     ValuePresenter::date()    // an ISO date or date-time
 *     ValuePresenter::list()    // an array, shown comma separated
 *     ValuePresenter::plain()   // as stored
 *
 * The entry always keeps the raw value; this only decides the display.
 */
final readonly class ValuePresenter
{
    /**
     * @param  class-string|null  $target  the referenced model or the enum
     */
    private function __construct(
        public ValuePresenterKind $kind,
        public ?string $target = null,
        public ?string $nameAttribute = null,
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

    public static function date(): self
    {
        return new self(ValuePresenterKind::Date);
    }

    public static function list(): self
    {
        return new self(ValuePresenterKind::List);
    }

    public static function plain(): self
    {
        return new self(ValuePresenterKind::Plain);
    }
}
