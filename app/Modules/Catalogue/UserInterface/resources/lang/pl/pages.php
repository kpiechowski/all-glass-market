<?php

declare(strict_types=1);

return [
    'catalogue_settings' => [
        'navigation_label' => 'Ustawienia katalogu',
        'title' => 'Ustawienia katalogu',
        'saved' => 'Zapisano zmiany',

        'actions' => [
            'save' => 'Zapisz',
        ],

        'positions' => [
            'heading' => 'Rodzaj szyby i kształt',
            'description' => 'Kształt miniatury dla każdego rodzaju szyby. Odbicie odwraca kształt w poziomie, dla wariantu lewego i prawego.',
            'shape' => 'Kształt',
            'mirrored' => 'Odbicie lustrzane',
            'unmapped' => 'Domyślny (nieznany kształt)',
        ],

        'colours' => [
            'heading' => 'Kolor i wypełnienie',
            'description' => 'Wypełnienie miniatury dla każdego koloru. Kolor bez własnego wypełnienia jest rysowany jako szkło przezroczyste.',
            'custom' => 'Własne wypełnienie',
            'fill' => 'Kolor wypełnienia',
            'opacity' => 'Krycie',
        ],

        'equipment' => [
            'heading' => 'Wyposażenie i piktogram',
            'description' => 'Piktogram pokazywany przy miniaturze. Wyposażenie bez piktogramu dostaje piktogram uniwersalny.',
            'pictogram' => 'Piktogram',
            'unmapped' => 'Uniwersalny',
        ],
    ],
];
