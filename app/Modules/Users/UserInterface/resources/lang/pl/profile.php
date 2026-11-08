<?php

declare(strict_types=1);

return [
    'title' => 'Mój profil',
    'navigation_label' => 'Mój profil',
    'user_menu_label' => 'Edytuj profil',
    'navigation_badge_tooltip' => 'Konto prywatne: do cen ofert doliczamy VAT. Sprzedajesz jako firma? Zmień rodzaj konta.',

    'sections' => [
        'personal_details' => 'Dane osobowe',
        'company' => 'Dane firmy',
        'company_description' => 'Konto firmowe wymaga NIP i adresu firmy.',
        'shipment_address' => 'Adres do wysyłki',
        'shipment_address_description' => 'Jeżeli inny niż adres firmy.',
        'account_type' => 'Rodzaj konta',
        'account_type_description' => 'Konto firmowe wymaga NIP, adresu firmy i oświadczenia sprzedawcy.',
    ],

    'fields' => [
        'name' => 'Imię i nazwisko',
        'email' => 'Adres e-mail',
        'phone' => 'Telefon',
        'account_type' => 'Rodzaj konta',
        'company_name' => 'Nazwa firmy',
        'company_nip' => 'NIP',
        'company_address' => 'Adres firmy',
        'street' => 'Ulica i numer',
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

    'helpers' => [
        'password' => 'Co najmniej 8 znaków.',
    ],

    'actions' => [
        'save' => 'Zapisz',
        'change_password' => 'Zmień hasło',
        'generate_password' => 'Wygeneruj hasło',
    ],

    'notifications' => [
        'saved' => 'Zapisano dane profilu',
        'save_failed' => 'Nie udało się zapisać profilu. Spróbuj ponownie za chwilę.',
        'password_changed' => 'Zmieniono hasło',
        'password_change_failed' => 'Nie udało się zmienić hasła. Spróbuj ponownie za chwilę.',
    ],
];
