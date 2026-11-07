<?php

declare(strict_types=1);

return [
    'user' => [
        'label' => 'Użytkownik',
        'plural_label' => 'Użytkownicy',

        'sections' => [
            'account_details' => 'Dane konta',
            'account' => 'Konto',
            'password' => 'Hasło',
            'company_details' => 'Dane firmy',
            'pickup_address' => 'Adres odbioru',
            'shipping' => 'Wysyłka',
            'offers' => 'Oferty',
            'history' => 'Historia zmian',
            'extra_permissions' => 'Dodatkowe uprawnienia',
        ],

        'descriptions' => [
            'extra_permissions' => 'Uprawnienia ponad rolę użytkownika. Widzą je tylko Super Admini.',
            'shipping' => 'Tekst trafi do sekcji „Wysyłka” w opisie ofert Otomoto tego użytkownika. Puste pole oznacza wysyłkę z oferty lub kategorii.',
        ],

        'tabs' => [
            'details' => 'Szczegóły',
            'active' => 'Aktywni',
            'inactive' => 'Nieaktywni',
            'all' => 'Wszyscy',
        ],

        'fields' => [
            'role' => 'Rola',
            'name' => 'Imię i nazwisko',
            'email' => 'Adres e-mail',
            'phone' => 'Telefon',
            'password' => 'Hasło tymczasowe',
            'password_new' => 'Nowe hasło',
            'password_confirmation' => 'Potwierdzenie hasła',
            'company_name' => 'Nazwa firmy',
            'company_nip' => 'NIP',
            'company_address' => 'Adres firmy',
            'street' => 'Ulica i numer',
            'city' => 'Miasto',
            'postal_code' => 'Kod pocztowy',
            'account_type' => 'Typ konta',
            'created_at' => 'Data dołączenia',
            'status' => 'Status',
            'declaration_accepted_at' => 'Deklaracja sprzedawcy: data akceptacji',
            'declaration_version' => 'Deklaracja sprzedawcy: wersja',
            'deactivated_at' => 'Data dezaktywacji',
            'extra_permissions' => 'Dodatkowe uprawnienia',
            'password_method' => 'Sposób ustawienia hasła',
        ],

        'status' => [
            'active' => 'Aktywne',
            'inactive' => 'Nieaktywne',
        ],

        'markers' => [
            'password_change_required' => 'Wymaga zmiany hasła',
        ],

        'card' => [
            'joined' => 'od :date',
        ],

        'empty' => [
            'heading' => 'Brak użytkowników',
            'description' => 'Utwórz konto, aby nowa osoba mogła zalogować się do panelu.',
        ],

        'password_methods' => [
            'invitation' => 'Wyślij zaproszenie (użytkownik sam ustawi hasło)',
            'temporary' => 'Ustaw hasło tymczasowe (użytkownik zmieni je po zalogowaniu)',
        ],

        'actions' => [
            'deactivate' => [
                'label' => 'Dezaktywuj',
                'heading' => 'Dezaktywować to konto?',
                'description' => 'Użytkownik zostanie wylogowany i nie zaloguje się, dopóki go nie aktywujesz. Dane, dziennik i historia zostają.',
                'done' => 'Dezaktywowano konto',
            ],
            'impersonate' => [
                'label' => 'Wejdź na konto',
                'heading' => 'Wejść na to konto?',
                'description' => 'Będziesz widzieć i robić wszystko jako ten użytkownik, dopóki nie wrócisz na swoje konto. Początek i koniec zostaną zapisane.',
            ],
            'reactivate' => [
                'label' => 'Aktywuj ponownie',
                'done' => 'Aktywowano konto',
            ],
            'set_password' => [
                'label' => 'Ustaw nowe hasło',
                'description' => 'Użytkownik zaloguje się tym hasłem i będzie musiał je zmienić.',
                'submit' => 'Ustaw hasło',
                'done' => 'Ustawiono nowe hasło',
            ],
            'send' => 'Wyślij',
            'generate_password' => 'Wygeneruj hasło',
            'send_invitation' => 'Wyślij ponownie zaproszenie',
            'send_reset' => 'Wyślij link do resetu hasła',
            'invitation_sent' => 'Wysłano zaproszenie',
            'reset_sent' => 'Wysłano link do resetu hasła',
            'require_password_change' => 'Wymagaj zmiany hasła',
            'require_password_change_done' => 'Ustawiono wymóg zmiany hasła',
            'clear_password_change' => 'Nie wymagaj zmiany hasła',
            'clear_password_change_done' => 'Zdjęto wymóg zmiany hasła',
        ],

        'helpers' => [
            'password_create' => 'Użytkownik zaloguje się tym hasłem i będzie musiał je zmienić.',
        ],

        'titles' => [
            'create' => 'Utwórz użytkownika',
        ],

        'unknown_role' => 'nieznana rola',

        'filters' => [
            'role' => 'Rola',
            'account_type' => 'Typ konta',
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
