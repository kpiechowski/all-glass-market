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
        'empty' => [
            'heading' => 'Brak zapisanych zmian',
            'description' => 'Zmiany tego rekordu pojawią się tutaj.',
        ],
        'fields' => [
            'occurred_at' => 'Kiedy',
            'actor' => 'Autor zmiany',
            'kind' => 'Rodzaj',
        ],
    ],
    'restore' => [
        'label' => 'Przywróć',
        'heading' => 'Przywrócić wartości sprzed zmiany?',
        'description' => 'Przywrócić: :values? Przywrócenie zapisze się w historii jako nowa zmiana.',
        'submit' => 'Przywróć wartości',
        'done' => 'Przywrócono wartości',
        'marker' => 'przywrócono z wpisu z :date',
    ],
];
