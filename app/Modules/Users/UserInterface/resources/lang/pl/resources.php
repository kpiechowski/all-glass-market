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
            'extra_permissions' => 'Dodatkowe uprawnienia',
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
            'extra_permissions' => 'Dodatkowe uprawnienia',
            'password_method' => 'Hasło użytkownika',
            'password_change_required' => 'Zmiana hasła',
        ],

        'status' => [
            'active' => 'Aktywne',
            'inactive' => 'konto nieaktywne',
        ],

        'markers' => [
            'password_change_required' => 'wymaga zmiany hasła',
        ],

        'password_methods' => [
            'invitation' => 'Wyślij zaproszenie (użytkownik sam ustawi hasło)',
            'temporary' => 'Ustaw hasło tymczasowe (użytkownik zmieni je po zalogowaniu)',
        ],

        'actions' => [
            'deactivate' => [
                'label' => 'Dezaktywuj',
                'heading' => 'Dezaktywować to konto?',
                'description' => 'Użytkownik zostanie wylogowany i nie zaloguje się, dopóki konto nie zostanie ponownie aktywowane. Dane, dziennik i historia zostają.',
                'done' => 'Konto zostało dezaktywowane.',
            ],
            'impersonate' => [
                'label' => 'Wejdź na konto',
                'heading' => 'Wejść na to konto?',
                'description' => 'Będziesz widzieć i robić wszystko jako ten użytkownik, dopóki nie wrócisz na swoje konto. Początek i koniec zostaną zapisane.',
            ],
            'reactivate' => [
                'label' => 'Aktywuj ponownie',
                'done' => 'Konto zostało ponownie aktywowane.',
            ],
            'group' => 'Hasło',
            'send_invitation' => 'Wyślij ponownie zaproszenie',
            'send_reset' => 'Wyślij link do resetu hasła',
            'invitation_sent' => 'Zaproszenie zostało wysłane',
            'reset_sent' => 'Link do resetu hasła został wysłany',
            'require_password_change' => 'Wymagaj zmiany hasła',
            'clear_password_change' => 'Nie wymagaj zmiany hasła',
        ],

        'helpers' => [
            'password_edit' => 'Zostaw puste, aby zachować obecne hasło. Nowe hasło wymusi jego zmianę przez użytkownika.',
            'password_create' => 'Użytkownik zaloguje się tym hasłem i będzie musiał je zmienić. Hasło może wygenerować przycisk obok pola.',
            'extra_permissions' => 'Uprawnienia nadane temu użytkownikowi ponad jego rolę. Widoczne tylko dla Super Adminów.',
            'shipping_text' => 'Informacja wyświetli się w sekcji WYSYŁKA opisu otomoto dla ofert dotyczących tego użytkownika. Pozostaw puste aby użyć wysyłki z oferty/kategorii',
        ],

        'no_extra_permissions' => 'brak',

        'unknown_role' => 'nieznana rola',

        'filters' => [
            'role' => 'Rola',
            'account_type' => 'Typ konta',
            'status' => 'Status konta',
            'status_all' => 'Wszystkie',
            'status_active' => 'Aktywne',
            'status_inactive' => 'Nieaktywne',
            'password_change_required' => 'Wymaga zmiany hasła',
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
