<?php

declare(strict_types=1);

return [
    'technical_log' => [
        'navigation_label' => 'Logi techniczne',
        'title' => 'Logi techniczne',
        'empty' => 'Brak wpisów',
        'download' => 'Pobierz plik',
        'details' => 'Szczegóły',
        'details_heading' => 'Szczegóły wpisu',
        'close' => 'Zamknij',
        'tabs' => [
            'files' => 'Pliki logów',
            'failed_jobs' => 'Nieudane zadania',
        ],
        'failed_jobs' => [
            'empty' => 'Brak nieudanych zadań',
            'details_heading' => 'Szczegóły nieudanego zadania',
            'retry' => 'Ponów',
            'retried' => 'Zadanie wróciło do kolejki.',
            'remove' => 'Usuń',
            'remove_heading' => 'Usunąć nieudane zadanie?',
            'remove_description' => 'Zadanie zostanie zapomniane i nie zostanie uruchomione. Historia zmian nie jest naruszana.',
            'removed' => 'Nieudane zadanie usunięto.',
            'fields' => [
                'name' => 'Zadanie',
                'error' => 'Błąd',
                'queue' => 'Kolejka',
                'exception' => 'Wyjątek',
            ],
        ],
        'fields' => [
            'channel' => 'Kanał',
            'day' => 'Dzień',
            'time' => 'Czas',
            'level' => 'Poziom',
            'message' => 'Komunikat',
            'context' => 'Kontekst',
            'stack_trace' => 'Ślad stosu',
        ],
    ],
];
