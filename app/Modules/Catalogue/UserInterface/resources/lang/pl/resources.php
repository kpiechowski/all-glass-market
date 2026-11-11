<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Katalog',
    ],

    'glass_model' => [
        'label' => 'Model szkła',
        'plural_label' => 'Modele szkła',

        'fields' => [
            'main_eurocode' => 'Eurokod główny',
            'position' => 'Rodzaj szyby',
            'colour' => 'Kolor',
            'equipment' => 'Wyposażenie',
            'oem_codes' => 'Kody OEM',
            'state' => 'Stan',
            'created_at' => 'Utworzono',
            'updated_at' => 'Ostatnia zmiana',
        ],

        'helpers' => [
            'main_eurocode' => 'Litery i cyfry, do 17 znaków; spacje i inne znaki zostaną usunięte.',
            'main_eurocode_locked' => 'Eurokodami zarządza się na stronie modelu.',
            'oem_codes' => 'Wpisz kod i zatwierdź klawiszem Enter.',
        ],

        'state' => [
            'active' => 'Aktywny',
            'withdrawn' => 'Wycofany',
        ],

        'sections' => [
            'identity' => 'Szkło',
            'codes' => 'Kody',
            'status' => 'Stan',
        ],

        'tabs' => [
            'details' => 'Szczegóły',
        ],

        'empty' => [
            'heading' => 'Nie ma jeszcze modeli szkła',
            'description' => 'Dodaj pierwszy model, aby oferty mogły się do niego odwoływać.',
        ],

        'actions' => [
            'create' => [
                'label' => 'Dodaj model szkła',
                'done' => 'Dodano model szkła',
            ],
            'edit' => [
                'done' => 'Zapisano zmiany',
            ],
            'withdraw' => [
                'label' => 'Wycofaj',
                'heading' => 'Wycofać ten model szkła?',
                'description' => 'Model zniknie z wyboru i wyszukiwania dla nowych zastosowań, ale pozostanie widoczny tam, gdzie jest już użyty. Możesz go później przywrócić.',
                'done' => 'Wycofano model szkła',
            ],
            'restore' => [
                'label' => 'Przywróć',
                'done' => 'Przywrócono model szkła',
            ],
            'delete' => [
                'label' => 'Usuń',
                'heading' => 'Usunąć ten model szkła?',
                'description' => 'Usunięcie jest trwałe. Model, który jest gdzieś używany, nie zostanie usunięty.',
                'done' => 'Usunięto model szkła',
            ],
        ],
    ],

    'dictionary_value' => [
        'label' => 'Wartość słownika',
        'plural_label' => 'Słowniki',

        'fields' => [
            'dictionary' => 'Słownik',
            'name' => 'Nazwa',
            'state' => 'Stan',
        ],

        'state' => [
            'active' => 'Aktywna',
            'withdrawn' => 'Wycofana',
        ],

        'filters' => [
            'state' => 'Stan',
            'all' => 'Wszystkie',
            'active' => 'Aktywne',
            'withdrawn' => 'Wycofane',
        ],

        'sections' => [
            'value' => 'Wartość',
        ],

        'tabs' => [
            'details' => 'Szczegóły',
        ],

        'empty' => [
            'heading' => 'Ten słownik jest pusty',
            'description' => 'Dodaj pierwszą wartość, aby można ją było wybierać w katalogu.',
        ],

        'actions' => [
            'create' => [
                'label' => 'Dodaj wartość',
                'heading' => 'Nowa wartość słownika',
                'submit' => 'Dodaj wartość',
                'done' => 'Dodano wartość',
            ],
            'rename' => [
                'label' => 'Zmień nazwę',
                'heading' => 'Zmień nazwę wartości',
                'submit' => 'Zapisz nazwę',
                'done' => 'Zmieniono nazwę',
            ],
            'withdraw' => [
                'label' => 'Wycofaj',
                'heading' => 'Wycofać tę wartość?',
                'description' => 'Wartość nie pojawi się przy wyborze nowych zastosowań, ale pozostanie widoczna tam, gdzie jest już użyta. Możesz ją później przywrócić.',
                'done' => 'Wycofano wartość',
            ],
            'restore' => [
                'label' => 'Przywróć',
                'done' => 'Przywrócono wartość',
            ],
            'delete' => [
                'label' => 'Usuń',
                'heading' => 'Usunąć tę wartość?',
                'description' => 'Usunięcie jest trwałe. Wartość, która jest gdzieś używana, nie zostanie usunięta.',
                'done' => 'Usunięto wartość',
            ],
        ],
    ],

    'vehicle' => [
        'label' => 'Pojazd',
        'plural_label' => 'Pojazdy',

        'fields' => [
            'name' => 'Nazwa',
            'make' => 'Marka',
            'model' => 'Model',
            'body' => 'Nadwozie',
            'generation' => 'Generacja',
            'year_from' => 'Rok produkcji od',
            'year_to' => 'Rok produkcji do',
            'years' => 'Lata produkcji',
            'argic_code' => 'Kod pojazdu ARGIC',
        ],

        'helpers' => [
            'year_to' => 'Zostaw puste, jeśli produkcja trwa.',
        ],

        'filters' => [
            'make' => 'Marka',
            'body' => 'Nadwozie',
        ],

        'sections' => [
            'details' => 'Dane pojazdu',
            'name' => 'Nazwa',
            'vehicle' => 'Pojazd',
        ],

        'descriptions' => [
            'name' => 'Nazwa jest proponowana z pól powyżej, ale możesz ją zmienić. Po zapisaniu system jej nie zmienia.',
        ],

        'tabs' => [
            'details' => 'Szczegóły',
        ],

        'empty' => [
            'heading' => 'Brak pojazdów',
            'description' => 'Dodaj pierwszy pojazd, aby można go było przypisać do szyb.',
        ],

        'actions' => [
            'delete' => [
                'label' => 'Usuń',
                'heading' => 'Usunąć ten pojazd?',
                'description' => 'Usunięcie jest trwałe.',
                'done' => 'Usunięto pojazd',
            ],
        ],
    ],
];
