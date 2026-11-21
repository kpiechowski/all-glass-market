<?php

declare(strict_types=1);

return [
    'offer' => [
        'type' => 'Oferta',
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
            'public_photos' => 'Zdjęcia publiczne',
            'private_photos' => 'Zdjęcia prywatne',
            'price' => 'Cena (w groszach)',
            'first_approved_at' => 'Pierwsze zatwierdzenie',
            'last_approved_at' => 'Ostatnie zatwierdzenie',
            'changed_since_approval' => 'Zmieniona od zatwierdzenia',
            'trashed_at' => 'W koszu od',
        ],
    ],

    'reservation' => [
        'type' => 'Rezerwacja',
        'title' => 'Rezerwacja oferty :number',
        'fields' => [
            'offer' => 'Oferta',
            'author' => 'Autor rezerwacji',
            'made_by_admin' => 'Zrobiona przez administratora',
            'note' => 'Dla kogo',
            'made_at' => 'Zarezerwowano',
            'ended_as' => 'Zakończona jako',
            'ended_at' => 'Zakończono',
            'inquiry' => 'Zapytanie, z którego powstała',
        ],
    ],
    'inquiry' => [
        'type' => 'Zapytanie',
        'title' => 'Zapytanie o ofertę :number',
        'fields' => [
            'offer' => 'Oferta',
            'sender' => 'Wysłał administrator',
            'channel' => 'Kanał',
            'note' => 'Treść',
            'state' => 'Stan',
            'sent_at' => 'Wysłano',
            'read_at' => 'Przeczytano',
            'answered_at' => 'Odpowiedziano',
        ],
    ],
    'sale' => [
        'type' => 'Sprzedaż',
        'title' => 'Sprzedaż oferty :number',
        'fields' => [
            'offer' => 'Oferta',
            'channel' => 'Kanał sprzedaży',
            'sale_price' => 'Cena sprzedaży (w groszach)',
            'entered_price' => 'Cena wpisana w ofercie (w groszach)',
            'vat_rate' => 'Stawka VAT',
            'buyers_price' => 'Cena dla kupującego (w groszach)',
            'marked_by' => 'Sprzedaż oznaczył',
            'sold_at' => 'Sprzedano',
            'returned_at' => 'Zwrócono',
            'return_reason' => 'Powód zwrotu',
            'returned_by' => 'Zwrot przyjął',
        ],
    ],
];
