<?php

namespace App\Modules\Users\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Observers\UserObserver;

class UsersEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        // 'App\Events\SomeEvent' => [
        //     'App\Listeners\EventListener',
        // ],
    ];

    protected $observers = [
        User::class => UserObserver::class,
    ];
}
