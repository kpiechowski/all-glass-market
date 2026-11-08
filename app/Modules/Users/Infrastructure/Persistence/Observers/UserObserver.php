<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Observers;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Persistence\ChangeRecorder;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * Thin: hands each change to ChangeRecorder (D-39). The one exception is the
 * session guard's own bookkeeping (remember token): it runs at sign-in and
 * sign-out with no command and carries no audited data, so it is not recorded.
 * Extra Permissions are a JSON column; the repository adapter reports them as
 * a list change, so the raw column diff is not audited.
 */
final class UserObserver
{
    /** Columns the session guard writes by itself. */
    private const array AUTH_PLUMBING = ['remember_token', 'updated_at'];

    public function __construct(private readonly ChangeRecorder $recorder) {}

    public function created(UserModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Created);
    }

    public function updated(UserModel $model): void
    {
        if (array_diff(array_keys($model->getDirty()), self::AUTH_PLUMBING) === []) {
            return;
        }

        $this->recorder->record($model, ChangeKind::Changed);
    }

    public function deleted(UserModel $model): void
    {
        $this->recorder->record($model, ChangeKind::Deleted);
    }
}
