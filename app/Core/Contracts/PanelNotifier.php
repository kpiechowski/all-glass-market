<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * The shared panel notification channel (D-04): the bell in the admin panel.
 * A module sends through it and never builds a Filament notification itself.
 *
 * The recipients are User ids. To reach the "Admins" group, pass the ids from
 * the Users `AdminRecipients` module API.
 */
interface PanelNotifier
{
    /**
     * @param  list<int>  $userIds  Users that exist; unknown ids are skipped
     * @param  'info'|'success'|'warning'|'danger'  $status
     * @param  string  $body  plain text, shown as typed
     * @param  list<array{label: string, url: string, markAsRead?: bool}>  $actions
     */
    public function notify(array $userIds, string $title, string $body, string $status = 'info', array $actions = []): void;

    /**
     * Removes every panel notification that has an action linking to `$url`,
     * for when the thing it opens no longer exists.
     */
    public function retract(string $url): void;
}
