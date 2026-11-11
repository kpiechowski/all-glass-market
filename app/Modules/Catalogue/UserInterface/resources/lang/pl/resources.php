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
            'eurocode' => 'Eurokod',
            'eurocodes' => 'Eurokody',
            'further_eurocodes' => 'Dalsze eurokody',
            'main_marker' => 'główny',
            'target_model' => 'Model docelowy',
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
            'further_eurocodes' => 'Opcjonalnie. Wpisz eurokod i zatwierdź klawiszem Enter; wszystkie eurokody znajdują model tak samo.',
            'target_model' => 'Wpisz fragment dowolnego eurokodu modelu, także wycofanego. Eurokod dołączy do niego jako dodatkowy.',
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

        'fitment' => [
            'title' => 'Dopasowanie',
            'empty' => [
                'heading' => 'Brak dopasowanych pojazdów',
                'description' => 'Dopasuj pojazdy, do których ten model szkła pasuje.',
            ],
            'fit' => [
                'label' => 'Dopasuj pojazdy',
                'heading' => 'Dopasować pojazdy do tego modelu?',
                'done' => 'Dopasowano pojazdy',
            ],
            'remove' => [
                'label' => 'Usuń dopasowanie',
                'heading' => 'Usunąć to dopasowanie?',
                'description' => 'Pojazd pozostanie w katalogu, model przestanie do niego pasować.',
                'done' => 'Usunięto dopasowanie',
            ],
        ],

        'card' => [
            'parameters' => 'Rodzaj i kolor',
            'vehicles' => 'Pasuje do pojazdów',
        ],

        'search' => [
            'placeholder' => 'Eurokod, kod OEM lub pojazd (min. 3 znaki)',
        ],

        'filters' => [
            'position' => 'Rodzaj szyby',
            'colour' => 'Kolor',
            'make' => 'Marka pojazdu',
            'with_withdrawn' => 'Pokaż także wycofane',
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
            'eurocodes' => [
                'label' => 'Eurokody',
            ],
            'add_eurocode' => [
                'label' => 'Dodaj eurokod',
                'heading' => 'Dodaj eurokod do modelu',
                'done' => 'Dodano eurokod',
            ],
            'change_main_eurocode' => [
                'label' => 'Zmień główny',
                'heading' => 'Który eurokod ma być główny?',
                'done' => 'Zmieniono eurokod główny',
            ],
            'move_eurocode' => [
                'label' => 'Przenieś eurokod',
                'heading' => 'Przenieś eurokod do innego modelu',
                'description' => 'Eurokod główny i jedyny eurokod modelu nie mogą zostać przeniesione.',
                'done' => 'Przeniesiono eurokod',
            ],
            'remove_eurocode' => [
                'label' => 'Usuń eurokod',
                'heading' => 'Usunąć eurokod?',
                'description' => 'Eurokod zniknie z katalogu. Eurokod główny i jedyny eurokod modelu nie mogą zostać usunięte.',
                'done' => 'Usunięto eurokod',
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
            'glass_models_count' => 'Pasuje do modeli szkła',
        ],

        'fitment' => [
            'title' => 'Pasujące modele szkła',
            'empty' => 'Żaden model szkła nie pasuje do tego pojazdu',
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
                'description_fitted' => 'Usunięcie jest trwałe. Pojazd pasuje do modeli szkła: :count. Dopasowania zostaną usunięte, a modele pozostaną.',
                'done' => 'Usunięto pojazd',
            ],
        ],
    ],
];
