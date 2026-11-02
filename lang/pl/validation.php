<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Poniższe linie zawierają domyślne komunikaty błędów używane przez
    | klasę walidatora. Niektóre z tych reguł mają wiele wersji, na przykład
    | reguły rozmiaru. Możesz je dowolnie zmieniać.
    |
    */

    'accepted' => 'Pole :attribute musi zostać zaakceptowane.',
    'accepted_if' => 'Pole :attribute musi zostać zaakceptowane, gdy :other ma wartość :value.',
    'active_url' => 'Pole :attribute musi być poprawnym adresem URL.',
    'after' => 'Pole :attribute musi być datą późniejszą niż :date.',
    'after_or_equal' => 'Pole :attribute musi być datą późniejszą lub równą :date.',
    'alpha' => 'Pole :attribute może zawierać wyłącznie litery.',
    'alpha_dash' => 'Pole :attribute może zawierać wyłącznie litery, cyfry, myślniki i podkreślenia.',
    'alpha_num' => 'Pole :attribute może zawierać wyłącznie litery i cyfry.',
    'any_of' => 'Pole :attribute jest nieprawidłowe.',
    'array' => 'Pole :attribute musi być tablicą.',
    'array_keys' => 'Pole :attribute może zawierać wyłącznie następujące klucze: :values.',
    'ascii' => 'Pole :attribute może zawierać wyłącznie jednobajtowe znaki alfanumeryczne i symbole.',
    'base64' => 'Pole :attribute musi być poprawnym ciągiem Base64.',
    'before' => 'Pole :attribute musi być datą wcześniejszą niż :date.',
    'before_or_equal' => 'Pole :attribute musi być datą wcześniejszą lub równą :date.',
    'between' => [
        'array' => 'Pole :attribute musi zawierać od :min do :max elementów.',
        'file' => 'Rozmiar pliku w polu :attribute musi mieścić się w przedziale od :min do :max kilobajtów.',
        'numeric' => 'Pole :attribute musi mieścić się w przedziale od :min do :max.',
        'string' => 'Pole :attribute musi mieć od :min do :max znaków.',
    ],
    'boolean' => 'Pole :attribute musi mieć wartość prawda lub fałsz.',
    'can' => 'Pole :attribute zawiera nieautoryzowaną wartość.',
    'confirmed' => 'Potwierdzenie pola :attribute nie jest zgodne.',
    'contains' => 'W polu :attribute brakuje wymaganej wartości.',
    'current_password' => 'Hasło jest nieprawidłowe.',
    'date' => 'Pole :attribute musi być poprawną datą.',
    'date_equals' => 'Pole :attribute musi być datą równą :date.',
    'date_format' => 'Pole :attribute musi mieć format :format.',
    'decimal' => 'Pole :attribute musi mieć :decimal miejsc po przecinku.',
    'declined' => 'Pole :attribute musi zostać odrzucone.',
    'declined_if' => 'Pole :attribute musi zostać odrzucone, gdy :other ma wartość :value.',
    'different' => 'Pole :attribute i :other muszą się różnić.',
    'digits' => 'Pole :attribute musi mieć :digits cyfr.',
    'digits_between' => 'Pole :attribute musi mieć od :min do :max cyfr.',
    'dimensions' => 'Pole :attribute ma nieprawidłowe wymiary obrazu.',
    'distinct' => 'Pole :attribute zawiera zduplikowaną wartość.',
    'doesnt_contain' => 'Pole :attribute nie może zawierać żadnej z następujących wartości: :values.',
    'doesnt_end_with' => 'Pole :attribute nie może kończyć się żadną z następujących wartości: :values.',
    'doesnt_start_with' => 'Pole :attribute nie może zaczynać się żadną z następujących wartości: :values.',
    'email' => 'Pole :attribute musi być poprawnym adresem e-mail.',
    'encoding' => 'Pole :attribute musi być zakodowane w :encoding.',
    'ends_with' => 'Pole :attribute musi kończyć się jedną z następujących wartości: :values.',
    'enum' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'exists' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'extensions' => 'Pole :attribute musi mieć jedno z następujących rozszerzeń: :values.',
    'file' => 'Pole :attribute musi być plikiem.',
    'filled' => 'Pole :attribute musi mieć wartość.',
    'gt' => [
        'array' => 'Pole :attribute musi mieć więcej niż :value elementów.',
        'file' => 'Rozmiar pliku w polu :attribute musi być większy niż :value kilobajtów.',
        'numeric' => 'Pole :attribute musi być większe niż :value.',
        'string' => 'Pole :attribute musi mieć więcej niż :value znaków.',
    ],
    'gte' => [
        'array' => 'Pole :attribute musi mieć :value lub więcej elementów.',
        'file' => 'Rozmiar pliku w polu :attribute musi być równy lub większy niż :value kilobajtów.',
        'numeric' => 'Pole :attribute musi być większe lub równe :value.',
        'string' => 'Pole :attribute musi mieć co najmniej :value znaków.',
    ],
    'hex_color' => 'Pole :attribute musi być poprawnym kolorem w zapisie szesnastkowym.',
    'image' => 'Pole :attribute musi być obrazem.',
    'in' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'in_array' => 'Pole :attribute musi istnieć w :other.',
    'in_array_keys' => 'Pole :attribute musi zawierać co najmniej jeden z następujących kluczy: :values.',
    'integer' => 'Pole :attribute musi być liczbą całkowitą.',
    'ip' => 'Pole :attribute musi być poprawnym adresem IP.',
    'ipv4' => 'Pole :attribute musi być poprawnym adresem IPv4.',
    'ipv6' => 'Pole :attribute musi być poprawnym adresem IPv6.',
    'json' => 'Pole :attribute musi być poprawnym ciągiem JSON.',
    'list' => 'Pole :attribute musi być listą.',
    'lowercase' => 'Pole :attribute musi być zapisane małymi literami.',
    'lt' => [
        'array' => 'Pole :attribute musi mieć mniej niż :value elementów.',
        'file' => 'Rozmiar pliku w polu :attribute musi być mniejszy niż :value kilobajtów.',
        'numeric' => 'Pole :attribute musi być mniejsze niż :value.',
        'string' => 'Pole :attribute musi mieć mniej niż :value znaków.',
    ],
    'lte' => [
        'array' => 'Pole :attribute nie może mieć więcej niż :value elementów.',
        'file' => 'Rozmiar pliku w polu :attribute musi być mniejszy lub równy :value kilobajtów.',
        'numeric' => 'Pole :attribute musi być mniejsze lub równe :value.',
        'string' => 'Pole :attribute może mieć najwyżej :value znaków.',
    ],
    'mac_address' => 'Pole :attribute musi być poprawnym adresem MAC.',
    'max' => [
        'array' => 'Pole :attribute nie może mieć więcej niż :max elementów.',
        'file' => 'Rozmiar pliku w polu :attribute nie może być większy niż :max kilobajtów.',
        'numeric' => 'Pole :attribute nie może być większe niż :max.',
        'string' => 'Pole :attribute nie może mieć więcej niż :max znaków.',
    ],
    'max_digits' => 'Pole :attribute nie może mieć więcej niż :max cyfr.',
    'mimes' => 'Pole :attribute musi być plikiem jednego z typów: :values.',
    'mimetypes' => 'Pole :attribute musi być plikiem jednego z typów: :values.',
    'min' => [
        'array' => 'Pole :attribute musi mieć co najmniej :min elementów.',
        'file' => 'Rozmiar pliku w polu :attribute musi wynosić co najmniej :min kilobajtów.',
        'numeric' => 'Pole :attribute musi być równe co najmniej :min.',
        'string' => 'Pole :attribute musi mieć co najmniej :min znaków.',
    ],
    'min_digits' => 'Pole :attribute musi mieć co najmniej :min cyfr.',
    'missing' => 'Pole :attribute musi być nieobecne.',
    'missing_if' => 'Pole :attribute musi być nieobecne, gdy :other ma wartość :value.',
    'missing_unless' => 'Pole :attribute musi być nieobecne, chyba że :other ma wartość :value.',
    'missing_with' => 'Pole :attribute musi być nieobecne, gdy obecne jest :values.',
    'missing_with_all' => 'Pole :attribute musi być nieobecne, gdy obecne są :values.',
    'multiple_of' => 'Pole :attribute musi być wielokrotnością liczby :value.',
    'not_in' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'not_regex' => 'Format pola :attribute jest nieprawidłowy.',
    'numeric' => 'Pole :attribute musi być liczbą.',
    'password' => [
        'letters' => 'Pole :attribute musi zawierać co najmniej jedną literę.',
        'mixed' => 'Pole :attribute musi zawierać co najmniej jedną wielką i jedną małą literę.',
        'numbers' => 'Pole :attribute musi zawierać co najmniej jedną cyfrę.',
        'symbols' => 'Pole :attribute musi zawierać co najmniej jeden symbol.',
        'uncompromised' => 'Podana wartość pola :attribute pojawiła się w wycieku danych. Wybierz inne :attribute.',
    ],
    'present' => 'Pole :attribute musi być obecne.',
    'present_if' => 'Pole :attribute musi być obecne, gdy :other ma wartość :value.',
    'present_unless' => 'Pole :attribute musi być obecne, chyba że :other ma wartość :value.',
    'present_with' => 'Pole :attribute musi być obecne, gdy obecne jest :values.',
    'present_with_all' => 'Pole :attribute musi być obecne, gdy obecne są :values.',
    'prohibited' => 'Pole :attribute jest niedozwolone.',
    'prohibited_if' => 'Pole :attribute jest niedozwolone, gdy :other ma wartość :value.',
    'prohibited_if_accepted' => 'Pole :attribute jest niedozwolone, gdy :other zostało zaakceptowane.',
    'prohibited_if_declined' => 'Pole :attribute jest niedozwolone, gdy :other zostało odrzucone.',
    'prohibited_unless' => 'Pole :attribute jest niedozwolone, chyba że :other ma jedną z wartości: :values.',
    'prohibits' => 'Pole :attribute uniemożliwia obecność pola :other.',
    'regex' => 'Format pola :attribute jest nieprawidłowy.',
    'required' => 'Pole :attribute jest wymagane.',
    'required_array_keys' => 'Pole :attribute musi zawierać wpisy dla: :values.',
    'required_if' => 'Pole :attribute jest wymagane, gdy :other ma wartość :value.',
    'required_if_accepted' => 'Pole :attribute jest wymagane, gdy :other zostało zaakceptowane.',
    'required_if_declined' => 'Pole :attribute jest wymagane, gdy :other zostało odrzucone.',
    'required_unless' => 'Pole :attribute jest wymagane, chyba że :other ma jedną z wartości: :values.',
    'required_with' => 'Pole :attribute jest wymagane, gdy obecne jest :values.',
    'required_with_all' => 'Pole :attribute jest wymagane, gdy obecne są :values.',
    'required_without' => 'Pole :attribute jest wymagane, gdy nie jest obecne :values.',
    'required_without_all' => 'Pole :attribute jest wymagane, gdy żadne z pól :values nie jest obecne.',
    'same' => 'Pole :attribute musi być zgodne z :other.',
    'size' => [
        'array' => 'Pole :attribute musi zawierać :size elementów.',
        'file' => 'Rozmiar pliku w polu :attribute musi wynosić :size kilobajtów.',
        'numeric' => 'Pole :attribute musi być równe :size.',
        'string' => 'Pole :attribute musi mieć :size znaków.',
    ],
    'starts_with' => 'Pole :attribute musi zaczynać się jedną z następujących wartości: :values.',
    'string' => 'Pole :attribute musi być tekstem.',
    'timezone' => 'Pole :attribute musi być poprawną strefą czasową.',
    'unique' => 'Ta wartość w polu :attribute jest już zajęta.',
    'uploaded' => 'Nie udało się przesłać pliku w polu :attribute.',
    'uppercase' => 'Pole :attribute musi być zapisane wielkimi literami.',
    'url' => 'Pole :attribute musi być poprawnym adresem URL.',
    'ulid' => 'Pole :attribute musi być poprawnym identyfikatorem ULID.',
    'uuid' => 'Pole :attribute musi być poprawnym identyfikatorem UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Tutaj możesz określić własne komunikaty walidacji dla atrybutów,
    | używając konwencji "attribute.rule" w nazwie wiersza.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'własny-komunikat',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Poniższe wiersze zastępują nazwy atrybutów czytelnymi nazwami, na
    | przykład "e-mail" zamiast "email". Formularze Filament podają własne
    | etykiety pól, więc lista pozostaje pusta.
    |
    */

    'attributes' => [],
];
