<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Observers\DictionaryValueObserver;

class CatalogueEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [];

    protected $observers = [
        DictionaryValueModel::class => DictionaryValueObserver::class,
    ];
}
