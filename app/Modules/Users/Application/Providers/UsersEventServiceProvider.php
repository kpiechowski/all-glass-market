<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;
use App\Modules\Users\Application\Listeners\SendJournalEntryNotification;
use App\Modules\Users\Application\Listeners\WriteAccountTypeChangeJournalEntries;
use App\Modules\Users\Domain\Events\AccountTypeChanged;
use App\Modules\Users\Domain\Events\JournalEntryAdded;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\Infrastructure\Persistence\Observers\UserObserver;

class UsersEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        AccountTypeChanged::class => [WriteAccountTypeChangeJournalEntries::class],
        JournalEntryAdded::class => [SendJournalEntryNotification::class],
    ];

    protected $observers = [
        UserModel::class => UserObserver::class,
    ];
}
