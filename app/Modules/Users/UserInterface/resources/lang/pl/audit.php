<?php

declare(strict_types=1);

return [
    'type' => 'Użytkownik',

    'fields' => [
        'name' => 'Imię i nazwisko',
        'email' => 'Adres e-mail',
        'phone' => 'Telefon',
        'company_name' => 'Nazwa firmy',
        'company_nip' => 'NIP',
        'company_address' => 'Adres firmy',
        'shipment_address' => 'Adres odbioru',
        'city' => 'Miasto',
        'city_code' => 'Kod pocztowy',
        'shipping_note' => 'Wysyłka',
        'role' => 'Rola',
        'account_type' => 'Typ konta',
        'seller_declaration_accepted_at' => 'Deklaracja sprzedawcy: data akceptacji',
        'seller_declaration_version' => 'Deklaracja sprzedawcy: wersja',
        'deactivated_at' => 'Dezaktywowano',
        'password_change_required' => 'Wymagana zmiana hasła',
    ],

    'relations' => [
        'extra_permissions' => 'Dodatkowe uprawnienia',
    ],
];
