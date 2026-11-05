<?php

declare(strict_types=1);

return [
    'record_history' => [
        'title' => 'Historia zmian',
        'details' => 'Szczegóły',
        'details_heading' => 'Szczegóły zmiany',
        'close' => 'Zamknij',
        'system' => 'System: :reason',
        'system_option' => 'System',
        'fields' => [
            'occurred_at' => 'Kiedy',
            'actor' => 'Autor zmiany',
            'kind' => 'Rodzaj',
        ],
    ],
    'audit_log' => [
        'navigation_label' => 'Dziennik audytu',
        'title' => 'Dziennik audytu',
        'fields' => [
            'record_type' => 'Rodzaj rekordu',
            'record' => 'Rekord',
            'record_id' => 'Identyfikator rekordu',
            'from' => 'Od',
            'until' => 'Do',
        ],
    ],
];
