<?php

declare(strict_types=1);

return [
    'history_and_logs' => [
        'navigation_label' => 'Historia i logi',
        'title' => 'Historia i logi',
        'details' => 'Szczegóły',
        'close' => 'Zamknij',
        'tabs' => [
            'historia' => 'Historia zmian',
            'logi' => 'Logi techniczne',
            'zadania' => 'Nieudane zadania',
        ],
        'history' => [
            'empty' => [
                'heading' => 'Brak zapisanych zmian',
                'description' => 'Tu pojawi się każda zmiana rekordów zapisana w systemie.',
            ],
            'fields' => [
                'record_type' => 'Rodzaj rekordu',
                'record' => 'Rekord',
                'record_id' => 'Identyfikator rekordu',
                'period' => 'Okres',
                'from' => 'Od',
                'until' => 'Do',
            ],
            'indicators' => [
                'record_id' => 'Rekord #:id',
                'from' => 'Od :date',
                'until' => 'Do :date',
            ],
        ],
        'technical_logs' => [
            'download' => 'Pobierz plik',
            'details_heading' => 'Szczegóły wpisu',
            'empty' => [
                'heading' => 'Brak wpisów',
                'description' => 'Ten kanał nie zapisał w wybranym dniu żadnego wpisu.',
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
        'failed_jobs' => [
            'details_heading' => 'Szczegóły nieudanego zadania',
            'empty' => [
                'heading' => 'Brak nieudanych zadań',
                'description' => 'Wszystkie zadania w tle zakończyły się powodzeniem.',
            ],
            'retry' => 'Ponów',
            'retried' => 'Ponowiono zadanie',
            'remove' => 'Usuń',
            'remove_heading' => 'Usunąć nieudane zadanie?',
            'remove_description' => 'Zadanie nie zostanie już uruchomione. Historia zmian zostaje bez zmian.',
            'remove_submit' => 'Usuń zadanie',
            'removed' => 'Usunięto zadanie',
            'fields' => [
                'name' => 'Zadanie',
                'error' => 'Błąd',
                'queue' => 'Kolejka',
                'time' => 'Kiedy',
                'exception' => 'Wyjątek',
            ],
        ],
    ],
];
