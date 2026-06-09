<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Observers;

use App\Modules\Users\Domain\Models\User;

class UserObserver
{
    /** Fires after a model is retrieved from the database. */
    public function retrieved(User $model): void
    {
        //
    }

    /** Fires before a model is created. */
    public function creating(User $model): void
    {
        //
    }

    /** Fires after a model is created. */
    public function created(User $model): void
    {
        //
    }

    /** Fires before a model is updated. */
    public function updating(User $model): void
    {
        //
    }

    /** Fires after a model is updated. */
    public function updated(User $model): void
    {
        //
    }

    /** Fires before a model is created or updated. */
    public function saving(User $model): void
    {
        //
    }

    /** Fires after a model is created or updated. */
    public function saved(User $model): void
    {
        //
    }

    /** Fires before a model is deleted or soft-deleted. */
    public function deleting(User $model): void
    {
        //
    }

    /** Fires after a model is deleted or soft-deleted. */
    public function deleted(User $model): void
    {
        //
    }

    /** Fires before a soft-deleted model is restored. */
    public function restoring(User $model): void
    {
        //
    }

    /** Fires after a soft-deleted model is restored. */
    public function restored(User $model): void
    {
        //
    }

    /** Fires before a model is permanently deleted (force delete). */
    public function forceDeleting(User $model): void
    {
        //
    }

    /** Fires after a model is permanently deleted (force delete). */
    public function forceDeleted(User $model): void
    {
        //
    }
}
