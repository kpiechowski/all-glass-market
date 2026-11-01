<?php

namespace App\Modules\Users\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;

class UsersEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        // 'App\Events\SomeEvent' => [
        //     'App\Listeners\EventListener',
        // ],
    ];

    protected $observers = [];
}
