<?php

declare(strict_types=1);

return [
    'title' => 'Mój profil',
    'navigation_label' => 'Mój profil',

    'tabs' => [
        'general' => 'Ogólne',
        'change_password' => 'Zmiana hasła',
    ],

    'sections' => [
        'personal_details' => 'Dane osobowe',
        'personal_details_description' => 'Twoje podstawowe informacje o koncie',
        'company_account' => 'Konto firmowe',
        'company_account_description' => 'Zarejestruj się jako firma, aby korzystać z cen B2B i fakturowania',
        'change_password' => 'Zmiana hasła',
        'change_password_description' => 'Wybierz silne hasło zawierające co najmniej 8 znaków',
    ],

    'fields' => [
        'name' => 'Imię i nazwisko',
        'email' => 'Adres e-mail',
        'phone' => 'Telefon',
        'company_account' => 'Reprezentuję firmę',
        'has_accepted_terms' => 'Akceptuję regulamin i warunki',
        'company_name' => 'Nazwa firmy',
        'company_nip' => 'NIP',
        'company_address' => 'Adres firmy',
        'shipment_address' => 'Adres dostawy',
        'city' => 'Miasto',
        'city_code' => 'Kod pocztowy',
        'shipping_information' => 'Szczegóły wysyłki',
        'shipping_information_helper' => 'Wyświetlane na stronach Twoich ofert. Pozostaw puste, aby użyć ustawień domyślnych kategorii.',
        'password' => 'Nowe hasło',
        'password_confirmation' => 'Potwierdzenie hasła',
    ],

    'actions' => [
        'save' => 'Zapisz profil',
        'change_password' => 'Zmień hasło',
        'generate_password' => 'Wygeneruj hasło',
    ],

    'notifications' => [
        'saved' => 'Profil został zapisany',
        'password_changed' => 'Hasło zostało zmienione',
    ],
];
