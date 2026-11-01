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
        'company_account_description' => 'Dane firmy i adres do wysyłki',
        'account_type' => 'Rodzaj konta',
        'account_type_description' => 'Konto firmowe wymaga NIP, adresu firmy i oświadczenia sprzedawcy',
        'change_password' => 'Zmiana hasła',
        'change_password_description' => 'Wybierz silne hasło zawierające co najmniej 8 znaków',
    ],

    'fields' => [
        'name' => 'Imię i nazwisko',
        'email' => 'Adres e-mail',
        'phone' => 'Telefon',
        'account_type' => 'Rodzaj konta',
        'company_name' => 'Nazwa firmy',
        'company_nip' => 'NIP',
        'company_address' => 'Adres firmy',
        'shipment_address' => 'Adres do wysyłki',
        'city' => 'Miasto',
        'city_code' => 'Kod pocztowy',
        'password' => 'Nowe hasło',
        'password_confirmation' => 'Potwierdzenie hasła',
    ],

    'private_notice' => 'Do cen Twoich ofert przed publikacją zostanie doliczony podatek VAT.',
    'seller_declaration' => 'Oświadczam, że jestem zarejestrowanym czynnym podatnikiem podatku VAT. Jednocześnie oświadczam, że dokonuje sprzedaży oferowanych towarów w ramach prowadzonej przeze mnie działalności gospodarczej.',
    'account_type_locked' => 'Zmiana rodzaju konta jest zablokowana do: :time',

    'private_notice_modal' => [
        'heading' => 'Pamiętaj!',
        'description' => 'Do cen Twoich ofert przed publikacją zostanie doliczony podatek VAT.',
        'confirm' => 'Rozumiem',
    ],

    'actions' => [
        'save' => 'Zapisz',
        'change_password' => 'Zmień hasło',
        'generate_password' => 'Wygeneruj hasło',
    ],

    'notifications' => [
        'saved' => 'Zapisano dane profilu',
        'save_failed' => 'Nie udało się zapisać profilu',
        'password_changed' => 'Hasło zostało zmienione',
        'password_change_failed' => 'Nie udało się zmienić hasła',
    ],
];
