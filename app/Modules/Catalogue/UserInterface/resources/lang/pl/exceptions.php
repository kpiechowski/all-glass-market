<?php

declare(strict_types=1);

return [
    'glass_model' => [
        'eurocode_is_blank' => 'Podaj eurokod: litery i cyfry, bez spacji.',
        'eurocode_is_too_long' => 'Eurokod może mieć najwyżej :max znaków (liter i cyfr). Skróć go.',
        'eurocode_is_taken' => 'Eurokod :code należy już do innego modelu szkła: :holder. Wybierz inny eurokod albo zmień tamten model.',
        'eurocode_is_not_held' => 'Ten model szkła nie ma eurokodu :code.',
        'eurocode_already_held' => 'Ten model szkła ma już eurokod :code.',
        'vehicle_is_not_fitted' => 'Ten model szkła nie jest dopasowany do tego pojazdu.',
        'last_eurocode' => 'Eurokod :code jest jedynym eurokodem tego modelu, więc nie można go usunąć ani przenieść. Najpierw dodaj inny eurokod.',
        'main_eurocode' => 'Eurokod :code jest eurokodem głównym. Najpierw wskaż inny eurokod jako główny.',
        'cannot_move_to_itself' => 'Wybierz inny model szkła, do którego przenieść eurokod.',
        'oem_code_is_too_long' => 'Kod OEM może mieć najwyżej :max znaków. Skróć go.',
        'position_is_required' => 'Wybierz rodzaj szyby.',
        'value_does_not_exist' => 'Wybrana wartość słownika już nie istnieje. Wybierz inną.',
        'value_is_of_another_dictionary' => 'Wybrana wartość pochodzi z innego słownika. Wybierz ją z właściwej listy.',
        'value_is_withdrawn' => 'Wartość „:name” jest wycofana i nie można jej wybrać. Wybierz inną.',
        'already_withdrawn' => 'Ten model szkła jest już wycofany.',
        'not_withdrawn' => 'Ten model szkła nie jest wycofany.',
        'in_use' => 'Model szkła jest używany (:places), więc nie można go usunąć. Wycofaj go zamiast tego.',
    ],

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
