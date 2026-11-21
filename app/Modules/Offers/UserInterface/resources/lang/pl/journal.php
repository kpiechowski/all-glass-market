<?php

declare(strict_types=1);

return [
    'approved' => [
        'title' => 'Oferta :number została zatwierdzona',
        'content' => 'Administrator zatwierdził Twoją ofertę :number. Jest teraz aktywna.',
    ],
    'rejected' => [
        'title' => 'Oferta :number została odrzucona',
        'content' => "Administrator odrzucił Twoją ofertę :number.\nPowód: :reason",
    ],
    'changed_by_admin' => [
        'title' => 'Administrator zmienił ofertę :number',
        'content' => 'Administrator zmienił dane Twojej oferty :number. Status oferty pozostał bez zmian.',
    ],
    'withdrawn' => [
        'title' => 'Oferta :number została wycofana',
        'content' => 'Administrator wycofał Twoją ofertę :number. Możesz ją wznowić, jeśli nie zmieniano jej po zatwierdzeniu.',
    ],
    'deleted' => [
        'title' => 'Oferta :number została usunięta',
        'content' => 'Administrator usunął Twoją ofertę :number. Nie widzisz jej już na liście.',
    ],
    'reserved' => [
        'title' => 'Oferta :number została zarezerwowana',
        'content' => 'Administrator zarezerwował Twoją ofertę :number.',
    ],
    'released' => [
        'title' => 'Rezerwacja oferty :number została zwolniona',
        'content' => 'Administrator zwolnił rezerwację Twojej oferty :number. Oferta jest znów aktywna.',
    ],
    'sold' => [
        'title' => 'Oferta :number została sprzedana',
        'content' => 'Administrator oznaczył Twoją ofertę :number jako sprzedaną.',
    ],
    'returned' => [
        'title' => 'Sprzedaż oferty :number została zwrócona',
        'content' => 'Administrator zwrócił sprzedaż Twojej oferty :number. Oferta jest znów aktywna.',
    ],
    'inquiry_sent' => [
        'title' => 'Zapytanie o ofertę :number',
        'content' => 'Administrator zapytał o Twoją ofertę :number. Odpowiedz na zapytanie w sekcji Zapytania.',
    ],
    'inquiry_accepted' => [
        'title' => 'Zapytanie o ofertę :number zaakceptowane',
        'content' => 'Sprzedawca zaakceptował Twoje zapytanie. Oferta :number jest zarezerwowana na Twoje nazwisko.',
    ],
    'inquiry_rejected' => [
        'title' => 'Zapytanie o ofertę :number odrzucone',
        'content' => 'Sprzedawca odrzucił Twoje zapytanie o ofertę :number.',
    ],
    'inquiry_outdated' => [
        'title' => 'Zapytanie o ofertę :number jest nieaktualne',
        'content' => 'Oferta :number przestała być aktywna, więc Twoje zapytanie straciło aktualność.',
    ],
];
