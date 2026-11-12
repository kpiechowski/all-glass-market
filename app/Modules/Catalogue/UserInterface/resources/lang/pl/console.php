<?php

declare(strict_types=1);

return [
    'seed_catalogue' => [
        'description' => 'Czyści katalog i ładuje testowe dane (rozmiar: s, m lub l)',
        'invalid_size' => 'Nieznany rozmiar ":size". Dozwolone: s, m, l.',
        'production_refused' => 'Odmowa: nie wolno czyścić katalogu w środowisku produkcyjnym.',
        'confirm' => 'To usunie WSZYSTKIE dane katalogu (modele szyb, pojazdy, słowniki, miniatury) i załaduje dane testowe. Kontynuować?',
        'cancelled' => 'Przerwano, nic nie zmieniono.',
        'done' => 'Katalog załadowany, rozmiar: :size.',
        'rows' => [
            'dictionary_values' => 'Wartości słowników',
            'vehicles' => 'Pojazdy',
            'glass_models' => 'Modele szyb',
            'eurocodes' => 'Eurokody',
            'oem_codes' => 'Kody OEM',
            'fitments' => 'Dopasowania do pojazdów',
            'thumbnail_mappings' => 'Mapowania miniatur',
        ],
    ],
];
