<?php

declare(strict_types=1);

return [
    'changes_log' => [
        'label' => 'Wpis zmian',
        'plural_label' => 'Dziennik zmian',

        'fields' => [
            'loggable_type' => 'Typ obiektu',
            'loggable_title' => 'Obiekt',
            'user' => 'Użytkownik',
            'action' => 'Akcja',
            'message' => 'Opis',
            'created_at' => 'Data',
        ],

        'filters' => [
            'action' => 'Akcja',
            'loggable_type' => 'Typ obiektu',
            'user' => 'Użytkownik',
        ],

        'actions' => [
            'created' => 'Dodano',
            'updated' => 'Zaktualizowano',
            'deleted' => 'Usunięto',
        ],
    ],

    'user_journal' => [
        'label' => 'Wpis dziennika',
        'plural_label' => 'Dziennik ofert',

        'fields' => [
            'user' => 'Użytkownik',
            'offer_id' => 'ID oferty',
            'type' => 'Typ',
            'title' => 'Tytuł',
            'content' => 'Treść',
            'is_read' => 'Przeczytano',
            'created_at' => 'Data',
        ],

        'filters' => [
            'is_read' => 'Status odczytu',
        ],

        'actions' => [
            'mark_read' => 'Oznacz jako przeczytane',
        ],
    ],

    'journal_types' => [
        'Info' => 'Informacja',
        'Success' => 'Sukces',
        'Warning' => 'Ostrzeżenie',
        'Rejection' => 'Odrzucenie',
    ],
];
