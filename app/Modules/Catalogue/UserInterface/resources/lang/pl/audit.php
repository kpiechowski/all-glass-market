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

    'glass_model' => [
        'type' => 'Model szkła',

        'fields' => [
            'position' => 'Rodzaj szyby',
            'colour' => 'Kolor',
            'withdrawn' => 'Wycofany',
        ],

        'relations' => [
            'eurocodes' => 'Eurokody',
            'main_eurocode' => 'Eurokod główny',
            'oem_codes' => 'Kody OEM',
            'equipment' => 'Wyposażenie',
            'vehicles' => 'Dopasowane pojazdy',
        ],
    ],
];
