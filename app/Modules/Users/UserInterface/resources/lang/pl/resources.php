<?php

declare(strict_types=1);

return [
    'user' => [
        'label' => 'Użytkownik',
        'plural_label' => 'Użytkownicy',

        'sections' => [
            'account_details' => 'Dane konta',
            'password' => 'Hasło',
            'company_details' => 'Dane firmy i adresy',
            'shipping' => 'Wysyłka',
            'offers' => 'Oferty',
            'history' => 'Historia zmian',
        ],

        'fields' => [
            'role' => 'Rola',
            'name' => 'Imię i nazwisko',
            'email' => 'Adres e-mail',
            'phone' => 'Telefon',
            'password' => 'Hasło tymczasowe',
            'password_edit' => 'Nowe hasło',
            'password_confirmation' => 'Potwierdzenie hasła',
            'company_name' => 'Nazwa firmy',
            'company_nip' => 'NIP',
            'company_address' => 'Adres firmy',
            'pickup_address' => 'Adres odbioru',
            'city' => 'Miasto',
            'postal_code' => 'Kod pocztowy',
            'shipping_text' => 'Wysyłka',
            'account_type' => 'Typ konta',
            'created_at' => 'Data dołączenia',
            'status' => 'Status',
            'deactivated_at' => 'Dezaktywowano',
        ],

        'status' => [
            'active' => 'Aktywne',
            'inactive' => 'konto nieaktywne',
        ],

        'actions' => [
            'deactivate' => [
                'label' => 'Dezaktywuj',
                'heading' => 'Dezaktywować to konto?',
                'description' => 'Użytkownik zostanie wylogowany i nie zaloguje się, dopóki konto nie zostanie ponownie aktywowane. Dane, dziennik i historia zostają.',
                'done' => 'Konto zostało dezaktywowane.',
            ],
            'reactivate' => [
                'label' => 'Aktywuj ponownie',
                'done' => 'Konto zostało ponownie aktywowane.',
            ],
        ],

        'helpers' => [
            'password_edit' => 'Zostaw puste, aby zachować obecne hasło.',
            'password_create' => 'Użytkownik zaloguje się tym hasłem. Hasło może wygenerować przycisk obok pola.',
            'shipping_text' => 'Informacja wyświetli się w sekcji WYSYŁKA opisu otomoto dla ofert dotyczących tego użytkownika. Pozostaw puste aby użyć wysyłki z oferty/kategorii',
        ],

        'unknown_role' => 'nieznana rola',

        'filters' => [
            'role' => 'Rola',
            'account_type' => 'Typ konta',
            'status' => 'Status konta',
            'status_all' => 'Wszystkie',
            'status_active' => 'Aktywne',
            'status_inactive' => 'Nieaktywne',
        ],
    ],

    'journal_entry' => [
        'label' => 'Wpis dziennika',
        'plural_label' => 'Dziennik',
        'subject_missing' => 'Obiekt, którego dotyczy wpis, już nie istnieje.',

        'fields' => [
            'title' => 'Tytuł',
            'content' => 'Treść',
            'kind' => 'Rodzaj',
            'severity' => 'Ważność',
            'is_read' => 'Przeczytano',
            'created_at' => 'Data',
        ],

        'kinds' => [
            'offer' => 'Oferta',
            'users' => 'Użytkownicy',
        ],

        'actions' => [
            'mark_read' => 'Oznacz jako przeczytane',
        ],
    ],
];
