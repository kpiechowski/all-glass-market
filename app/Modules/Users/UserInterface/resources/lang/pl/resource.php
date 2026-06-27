<?php

declare(strict_types=1);

return [
    'label' => 'Użytkownik',
    'plural_label' => 'Użytkownicy',

    'sections' => [
        'account_details' => 'Dane konta',
        'security' => 'Bezpieczeństwo',
        'company_account' => 'Konto firmowe',
        'company_details' => 'Dane firmy',
        'timestamps' => 'Daty',
    ],

    'tabs' => [
        'general' => 'Ogólne',
        'password' => 'Hasło',
        'timestamps' => 'Daty',
    ],

    'fields' => [
        'role' => 'Rola',
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
        'shipping_note' => 'Notatka wysyłkowa',
        'email_verified' => 'Zweryfikowany e-mail',
        'account_type' => 'Typ konta',
        'accepted_terms' => 'Zaakceptowano regulamin',
        'created_at' => 'Data utworzenia',
        'updated_at' => 'Data modyfikacji',
    ],

    'roles' => [
        'root' => 'Root',
        'admin' => 'Administrator',
        'client' => 'Klient',
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
