<?php

declare(strict_types=1);

return [
    'dictionary_value' => [
        'name_is_blank' => 'Podaj nazwę wartości.',
        'name_is_too_long' => 'Nazwa może mieć najwyżej :max znaków. Skróć ją.',
        'name_is_taken' => 'W tym słowniku jest już wartość o takiej nazwie (wielkość liter nie ma znaczenia). Wybierz inną nazwę.',
        'already_withdrawn' => 'Ta wartość jest już wycofana.',
        'not_withdrawn' => 'Ta wartość nie jest wycofana.',
        'in_use' => 'Wartość jest używana (:places), więc nie można jej usunąć. Wycofaj ją zamiast tego.',
    ],

    'vehicle' => [
        'name_is_blank' => 'Podaj nazwę pojazdu.',
        'name_is_too_long' => 'Nazwa może mieć najwyżej :max znaków. Skróć ją.',
        'name_is_taken' => 'Istnieje już pojazd o takiej nazwie (wielkość liter nie ma znaczenia). Zmień nazwę.',
        'make_is_not_a_make' => 'Wybrana wartość nie jest marką pojazdu. Wybierz markę z listy.',
        'body_is_not_a_body' => 'Wybrana wartość nie jest nadwoziem pojazdu. Wybierz nadwozie z listy.',
        'make_is_withdrawn' => 'Ta marka jest wycofana i nie można jej wybrać dla pojazdu. Wybierz inną markę.',
        'body_is_withdrawn' => 'To nadwozie jest wycofane i nie można go wybrać dla pojazdu. Wybierz inne nadwozie.',
        'model_is_too_long' => 'Model może mieć najwyżej :max znaków. Skróć go.',
        'generation_is_too_long' => 'Generacja może mieć najwyżej :max znaków. Skróć ją.',
        'year_is_invalid' => 'Rok musi mieć cztery cyfry. Popraw rok produkcji.',
        'years_are_out_of_order' => 'Rok produkcji "od" nie może być późniejszy niż "do". Popraw lata.',
        'argic_code_is_invalid' => 'Kod ARGIC to dokładnie cztery cyfry. Popraw kod.',
    ],
];
