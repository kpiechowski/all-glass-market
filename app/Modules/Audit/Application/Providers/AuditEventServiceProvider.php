<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Providers;

use App\Core\Abstracts\CoreEventServiceProvider;
use App\Core\Domain\Events\RecordChanged;
use App\Modules\Audit\Application\Listeners\RecordChangeInHistory;
use App\Modules\Audit\Application\Listeners\WriteSecurityLogEntry;
use App\Modules\Users\Domain\Events\ImpersonationEnded;
use App\Modules\Users\Domain\Events\ImpersonationStarted;
use App\Modules\Users\Domain\Events\PasswordResetCompleted;
use App\Modules\Users\Domain\Events\PasswordResetRequested;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuditEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        RecordChanged::class => [RecordChangeInHistory::class],
        Login::class => [[WriteSecurityLogEntry::class, 'signedIn']],
        Logout::class => [[WriteSecurityLogEntry::class, 'signedOut']],
        Failed::class => [[WriteSecurityLogEntry::class, 'signInFailed']],
        PasswordResetRequested::class => [[WriteSecurityLogEntry::class, 'passwordResetRequested']],
        PasswordResetCompleted::class => [[WriteSecurityLogEntry::class, 'passwordResetCompleted']],
        ImpersonationStarted::class => [[WriteSecurityLogEntry::class, 'impersonationStarted']],
        ImpersonationEnded::class => [[WriteSecurityLogEntry::class, 'impersonationEnded']],
    ];

    protected $observers = [];
}
