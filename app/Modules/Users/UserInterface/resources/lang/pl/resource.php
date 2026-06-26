<?php

declare(strict_types=1);

return [
    'label' => 'Użytkownik',
    'plural_label' => 'Użytkownicy',

    'sections' => [
        'user_details' => 'Dane użytkownika',
        'user_details_description' => 'Podstawowe informacje o koncie',
        'security' => 'Bezpieczeństwo',
        'security_description' => 'Zarządzanie hasłem',
        'company_details' => 'Dane firmy',
        'company_details_description' => 'Informacje o firmie i adres rozliczeniowy',
        'shipping_information' => 'Informacje o wysyłce',
        'shipping_information_description' => 'Wyświetlane w opisach ofert Otomoto dla tego użytkownika. Pozostaw puste, aby użyć ustawień domyślnych oferty/kategorii.',
        'timestamps' => 'Daty',
    ],

    'fields' => [
        'name' => 'Imię i nazwisko',
        'email' => 'Adres e-mail',
        'phone' => 'Telefon',
        'password' => 'Hasło',
        'password_confirmation' => 'Potwierdzenie hasła',
        'company_account' => 'Konto firmowe',
        'has_accepted_terms' => 'Zaakceptowano regulamin',
        'company_name' => 'Nazwa firmy',
        'company_nip' => 'NIP',
        'company_address' => 'Adres firmy',
        'shipment_address' => 'Adres dostawy',
        'city' => 'Miasto',
        'city_code' => 'Kod pocztowy',
        'shipping_information' => 'Informacje o wysyłce',
        'email_verified' => 'Zweryfikowany e-mail',
        'account_type' => 'Typ konta',
        'accepted_terms' => 'Zaakceptowano regulamin',
        'created_at' => 'Data utworzenia',
        'updated_at' => 'Data modyfikacji',
    ],

    'account_types' => [
        'company' => 'Firmowe',
        'private' => 'Prywatne',
    ],

    'filters' => [
        'verified' => 'Zweryfikowany e-mail',
        'unverified' => 'Niezweryfikowany e-mail',
        'company' => 'Konta firmowe',
    ],

    'actions' => [
        'generate_password' => 'Wygeneruj hasło',
    ],
];
