<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Oferty',
    ],

    'offer' => [
        'label' => 'Oferta',
        'plural_label' => 'Oferty',
        'mine' => 'Moje oferty',
        'all' => 'Wszystkie oferty',
        'sections' => [
            'glass' => 'Szyba',
            'piece' => 'Sztuka',
            'notes' => 'Notatki prywatne',
            'photos' => 'Zdjęcia',
            'public_photos' => 'Zdjęcia publiczne',
            'private_photos' => 'Zdjęcia prywatne',
            'price' => 'Cena',
            'offer' => 'Oferta',
        ],
        'descriptions' => [
            'photos' => 'Do 10 zdjęć. Pierwsze jest zdjęciem głównym, kolejność zmienisz przeciągając.',
            'private_photos' => 'Do 10 zdjęć. Widzisz je tylko Ty i administratorzy. Nie trafiają do ogłoszeń.',
            'notes' => 'Widzisz je tylko Ty i administratorzy. Nie trafiają do ogłoszeń.',
        ],
        'fields' => [
            'number' => 'Numer oferty',
            'owner' => 'Właściciel',
            'status' => 'Status',
            'glass_model' => 'Model szyby',
            'identification_hint' => 'Podpowiedź identyfikacyjna',
            'producer' => 'Producent',
            'year' => 'Rok',
            'condition' => 'Stan',
            'rating' => 'Ocena',
            'description' => 'Opis dla kupujących',
            'private_notes' => 'Notatki prywatne',
            'price' => 'Cena',
            'created_at' => 'Utworzono',
            'main_photo' => 'Główne',
            'glass' => 'Szyba',
        ],
        'helpers' => [
            'identification_hint' => 'Dowolny kod, numer OEM, opis pojazdu lub auta dawcy; może być też numer VIN.',
            'price' => 'Kwota w złotych, wpisana przez Ciebie.',
            'rating' => 'Od 1 do 5 gwiazdek.',
        ],
        'glass_model' => [
            'withdrawn' => 'Wycofany',
            'eurocodes' => 'Eurokody',
            'oem_codes' => 'Kody OEM',
            'position' => 'Pozycja',
            'colour' => 'Kolor',
            'equipment' => 'Wyposażenie',
            'vehicles' => 'Pojazdy',
            'missing' => 'Ten model szkła nie jest już dostępny.',
        ],
        'actions' => [
            'pick_glass_model' => 'Wybierz model szyby',
            'unknown_glass_model' => 'Nie znam modelu',
            'create' => 'Utwórz ofertę',
        ],
        'notifications' => [
            'created' => 'Zapisano ofertę',
        ],
        'tabs' => [
            'details' => 'Szczegóły',
        ],
        'empty' => [
            'heading' => 'Brak ofert',
            'description' => 'Dodaj pierwszą ofertę, aby pojawiła się na liście.',
        ],
    ],
];
