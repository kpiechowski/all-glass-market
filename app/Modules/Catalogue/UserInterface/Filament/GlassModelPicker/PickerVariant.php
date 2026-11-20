<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;

/**
 * FR-19, FR-20: who the Glass model picker is built for. A Seller never sees withdrawn models and
 * cannot create one; an Admin may list withdrawn models on request and create a model in place.
 */
enum PickerVariant: string
{
    case Seller = 'seller';
    case Admin = 'admin';

    /** The variant that fits the logged-in user: Admin for whoever may manage the catalogue. */
    public static function forCurrentActor(): self
    {
        return CurrentActor::can(CataloguePermission::Manage) ? self::Admin : self::Seller;
    }

    /** A Seller variant requested by a user who may not manage the catalogue is never an Admin one. */
    public function effective(): self
    {
        return $this === self::Admin && CurrentActor::can(CataloguePermission::Manage) ? self::Admin : self::Seller;
    }
}
