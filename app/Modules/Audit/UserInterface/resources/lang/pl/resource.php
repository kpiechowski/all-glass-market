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
];
