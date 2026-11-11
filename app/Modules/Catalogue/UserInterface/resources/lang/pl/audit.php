<?php

declare(strict_types=1);

return [
    'dictionary_value' => [
        'type' => 'Wartość słownika',

        'fields' => [
            'dictionary' => 'Słownik',
            'name' => 'Nazwa',
            'withdrawn' => 'Wycofana',
        ],
    ],

    'vehicle' => [
        'type' => 'Pojazd',

        'fields' => [
            'name' => 'Nazwa',
            'make' => 'Marka',
            'model' => 'Model',
            'body' => 'Nadwozie',
            'year_from' => 'Rok produkcji od',
            'year_to' => 'Rok produkcji do',
            'generation' => 'Generacja',
            'argic_code' => 'Kod pojazdu ARGIC',
        ],
    ],
];
